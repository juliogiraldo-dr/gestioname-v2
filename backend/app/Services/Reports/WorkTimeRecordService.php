<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\CalendarDay;
use App\Models\Employee;
use App\Models\ScheduleTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cálculo del Registro de Jornada (ET art. 34.9).
 *
 * Para cada empleado y cada día del rango calcula las horas previstas (según el
 * calendario/plantilla asignada), las realizadas (emparejando entrada→salida de los
 * fichajes), el sobretiempo y los retrasos respecto a la hora teórica de entrada.
 *
 * Devuelve una estructura neutra que luego consumen los escritores Excel y PDF.
 */
final class WorkTimeRecordService
{
    /**
     * @param  array{
     *   date_from: string, date_to: string, company_id?: ?string,
     *   work_center_ids?: list<string>, employee_ids?: list<string>,
     *   options?: array<string, mixed>
     * }  $params
     * @return array{
     *   meta: array<string, mixed>,
     *   employees: list<array<string, mixed>>,
     *   totals: array{expected: float, worked: float, overtime: float, delay_minutes: int}
     * }
     */
    public function compute(array $params): array
    {
        $from = Carbon::parse($params['date_from'])->startOfDay();
        $to = Carbon::parse($params['date_to'])->endOfDay();

        $employees = $this->resolveEmployees($params);

        $rows = [];
        $grand = ['expected' => 0.0, 'worked' => 0.0, 'overtime' => 0.0, 'delay_minutes' => 0];

        foreach ($employees as $employee) {
            $built = $this->buildEmployee($employee, $from, $to);
            $rows[] = $built;

            $grand['expected'] += $built['totals']['expected'];
            $grand['worked'] += $built['totals']['worked'];
            $grand['overtime'] += $built['totals']['overtime'];
            $grand['delay_minutes'] += $built['totals']['delay_minutes'];
        }

        return [
            'meta' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'generated_at' => Carbon::now()->toDateTimeString(),
                'options' => $this->normalizeOptions($params['options'] ?? []),
            ],
            'employees' => $rows,
            'totals' => $grand,
        ];
    }

    /**
     * @return Collection<int, Employee>
     */
    private function resolveEmployees(array $params): Collection
    {
        return Employee::query()
            ->when(! empty($params['company_id']), fn ($q) => $q->where('company_id', $params['company_id']))
            ->when(! empty($params['work_center_ids']), fn ($q) => $q->whereIn('work_center_id', $params['work_center_ids']))
            ->when(! empty($params['employee_ids']), fn ($q) => $q->whereIn('id', $params['employee_ids']))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEmployee(Employee $employee, Carbon $from, Carbon $to): array
    {
        $expectedByDate = $this->expectedHoursByDate($employee, $from, $to);
        $attendanceByDate = $this->attendanceByDate($employee, $from, $to);

        $days = [];
        $totals = ['expected' => 0.0, 'worked' => 0.0, 'overtime' => 0.0, 'delay_minutes' => 0];

        // Recorre todas las fechas que tienen previsión o fichajes.
        $dates = collect(array_keys($expectedByDate))
            ->merge(array_keys($attendanceByDate))
            ->unique()
            ->sort()
            ->values();

        foreach ($dates as $date) {
            $expected = $expectedByDate[$date] ?? null;
            $att = $attendanceByDate[$date] ?? null;

            $expectedHours = $expected['hours'] ?? 0.0;
            $worked = $att['worked'] ?? 0.0;
            $overtime = max(0.0, round($worked - $expectedHours, 2));
            $delay = $this->delayMinutes($expected, $att);

            $days[] = [
                'date' => $date,
                'expected' => round($expectedHours, 2),
                'worked' => round($worked, 2),
                'overtime' => $overtime,
                'delay_minutes' => $delay,
                'first_in' => $att['first_in'] ?? null,
                'last_out' => $att['last_out'] ?? null,
                'template' => $expected['template'] ?? null,
                'entries' => $att['entries'] ?? [],
            ];

            $totals['expected'] += $expectedHours;
            $totals['worked'] += $worked;
            $totals['overtime'] += $overtime;
            $totals['delay_minutes'] += $delay;
        }

        $totals = array_map(static fn ($v) => is_float($v) ? round($v, 2) : $v, $totals);

        return [
            'employee' => [
                'id' => $employee->id,
                'full_name' => $employee->fullName(),
                'dni' => $employee->dni,
                'department' => $employee->department,
                'job_position' => $employee->job_position,
                'work_center_id' => $employee->work_center_id,
                'work_center' => $employee->workCenter?->name,
            ],
            'days' => $days,
            'totals' => $totals,
        ];
    }

    /**
     * Horas previstas por fecha (Y-m-d) según los calendarios del empleado en el rango.
     *
     * @return array<string, array{hours: float, template: ?string, start: ?string}>
     */
    private function expectedHoursByDate(Employee $employee, Carbon $from, Carbon $to): array
    {
        $calendarIds = $employee->calendars()->pluck('work_calendars.id');

        if ($calendarIds->isEmpty()) {
            return [];
        }

        $days = CalendarDay::query()
            ->whereIn('calendar_id', $calendarIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->with('template.timeRanges')
            ->get();

        $result = [];

        foreach ($days as $day) {
            $template = $day->template;
            if ($template === null) {
                continue;
            }

            $date = $day->date->toDateString();

            // Si varios calendarios cubren la misma fecha, nos quedamos con el de más horas.
            $hours = $template->dailyHours();
            if (isset($result[$date]) && $result[$date]['hours'] >= $hours) {
                continue;
            }

            $result[$date] = [
                'hours' => $hours,
                'template' => $template->name,
                'start' => $this->expectedStart($template),
            ];
        }

        return $result;
    }

    /**
     * Hora teórica de entrada (H:i) para el cálculo de retrasos, o null si no aplica.
     */
    private function expectedStart(ScheduleTemplate $template): ?string
    {
        return match ($template->type) {
            'fijo' => $template->timeRanges->first()?->time_start
                ? substr((string) $template->timeRanges->first()->time_start, 0, 5)
                : null,
            'flexible' => $template->flex_start_max
                ? substr((string) $template->flex_start_max, 0, 5)
                : null,
            default => null, // 'libre' no genera retrasos
        };
    }

    /**
     * Horas realizadas y primer/último fichaje por fecha, emparejando entrada→salida.
     *
     * @return array<string, array{worked: float, first_in: ?string, last_out: ?string, entries: list<array<string, mixed>>}>
     */
    private function attendanceByDate(Employee $employee, Carbon $from, Carbon $to): array
    {
        $attendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('clocked_at', [$from, $to])
            ->with('milestone')
            ->orderBy('clocked_at')
            ->get();

        $result = [];
        /** @var Attendance|null $openEntry */
        $openEntry = null;

        foreach ($attendances as $att) {
            $type = $att->milestone?->type;
            $date = $att->clocked_at->toDateString();

            $result[$date] ??= ['worked' => 0.0, 'first_in' => null, 'last_out' => null, 'entries' => []];

            $result[$date]['entries'][] = [
                'type' => $type,
                'at' => $att->clocked_at->format('H:i'),
                'method' => $att->method,
                'lat' => $att->lat,
                'lng' => $att->lng,
            ];

            if ($type === 'entrada') {
                $openEntry = $att;
                if ($result[$date]['first_in'] === null) {
                    $result[$date]['first_in'] = $att->clocked_at->format('H:i');
                }
            } elseif ($type === 'salida' && $openEntry !== null) {
                $minutes = $openEntry->clocked_at->diffInMinutes($att->clocked_at);
                // El tramo trabajado se imputa a la fecha de la entrada.
                $entryDate = $openEntry->clocked_at->toDateString();
                $result[$entryDate] ??= ['worked' => 0.0, 'first_in' => null, 'last_out' => null, 'entries' => []];
                $result[$entryDate]['worked'] += $minutes / 60;
                $result[$date]['last_out'] = $att->clocked_at->format('H:i');
                $openEntry = null;
            }
        }

        foreach ($result as $date => $data) {
            $result[$date]['worked'] = round($data['worked'], 2);
        }

        return $result;
    }

    /**
     * Minutos de retraso del primer fichaje de entrada respecto a la hora teórica,
     * aplicando la tolerancia de la plantilla (ya descontada al definir start si procede).
     *
     * @param  array{hours: float, template: ?string, start: ?string}|null  $expected
     * @param  array{worked: float, first_in: ?string, last_out: ?string, entries: list<array<string, mixed>>}|null  $att
     */
    private function delayMinutes(?array $expected, ?array $att): int
    {
        $start = $expected['start'] ?? null;
        $firstIn = $att['first_in'] ?? null;

        if ($start === null || $firstIn === null) {
            return 0;
        }

        $theoretical = Carbon::createFromFormat('H:i', $start);
        $actual = Carbon::createFromFormat('H:i', $firstIn);

        if ($actual->lessThanOrEqualTo($theoretical)) {
            return 0;
        }

        return (int) $theoretical->diffInMinutes($actual);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function normalizeOptions(array $options): array
    {
        return [
            'include_work_center' => (bool) ($options['include_work_center'] ?? true),
            'include_delays' => (bool) ($options['include_delays'] ?? true),
            'include_geolocation' => (bool) ($options['include_geolocation'] ?? false),
            'include_method' => (bool) ($options['include_method'] ?? false),
            'decimal_format' => (bool) ($options['decimal_format'] ?? false),
            'split_by_employee' => (bool) ($options['split_by_employee'] ?? false),
            'password' => $options['password'] ?? null,
        ];
    }
}
