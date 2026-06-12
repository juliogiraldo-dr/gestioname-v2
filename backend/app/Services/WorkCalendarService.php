<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Holiday;
use App\Models\WorkCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de calendarios laborales: CRUD y operaciones de llenado/borrado/
 * clonado/simulación de horas.
 */
final class WorkCalendarService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WorkCalendar
    {
        return WorkCalendar::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkCalendar $calendar, array $data): WorkCalendar
    {
        $calendar->update($data);

        return $calendar;
    }

    public function delete(WorkCalendar $calendar): void
    {
        $calendar->delete();
    }

    /**
     * Llenado rápido: asigna la plantilla a todos los días del año del calendario que
     * caen en los días de la semana y meses indicados.
     *
     * @param  array<int, int>  $weekdays  1=lunes ... 7=domingo
     * @param  array<int, int>  $months  1..12
     */
    public function fillQuick(
        WorkCalendar $calendar,
        array $weekdays,
        array $months,
        string $templateId,
        bool $includeHolidays,
    ): int {
        $holidays = $includeHolidays ? [] : $this->holidayDates($calendar->year);

        $cursor = Carbon::create($calendar->year, 1, 1)->startOfDay();
        $end = Carbon::create($calendar->year, 12, 31)->startOfDay();
        $count = 0;

        $rows = [];
        for (; $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            if (! in_array($cursor->isoWeekday(), $weekdays, true)) {
                continue;
            }
            if (! in_array($cursor->month, $months, true)) {
                continue;
            }
            if (! $includeHolidays && isset($holidays[$cursor->toDateString()])) {
                continue;
            }

            $rows[] = $cursor->toDateString();
            $count++;
        }

        $this->assignDates($calendar, $rows, $templateId);

        return $count;
    }

    /**
     * Llenado manual: asigna la plantilla a un conjunto de fechas.
     *
     * @param  array<int, string>  $dates
     */
    public function fillManual(WorkCalendar $calendar, array $dates, string $templateId): int
    {
        $dates = array_values(array_unique($dates));
        $this->assignDates($calendar, $dates, $templateId);

        return count($dates);
    }

    /**
     * Borra días del calendario. Sin filtros = todos. Devuelve los días borrados.
     *
     * @param  array<int, string>|null  $dates
     */
    public function clear(WorkCalendar $calendar, ?string $dateFrom, ?string $dateTo, ?array $dates): int
    {
        $query = $calendar->days();

        if ($dateFrom !== null && $dateTo !== null) {
            $query->whereBetween('date', [$dateFrom, $dateTo]);
        } elseif (! empty($dates)) {
            $query->whereIn('date', $dates);
        }

        return $query->delete();
    }

    /**
     * Clona el calendario a otro año: copia metadatos y desplaza cada día al mismo
     * mes/día del año destino (el 29-feb se omite si el destino no es bisiesto).
     */
    public function clone(WorkCalendar $calendar, int $targetYear, ?string $name): WorkCalendar
    {
        return DB::transaction(function () use ($calendar, $targetYear, $name): WorkCalendar {
            $clone = WorkCalendar::create([
                'company_id' => $calendar->company_id,
                'name' => $name ?? ($calendar->name.' '.$targetYear),
                'color' => $calendar->color,
                'year' => $targetYear,
                'country' => $calendar->country,
                'province' => $calendar->province,
                'locality' => $calendar->locality,
                'description' => $calendar->description,
            ]);

            foreach ($calendar->days()->get() as $day) {
                $source = $day->date;
                if ($source->month === 2 && $source->day === 29 && ! Carbon::create($targetYear, 1, 1)->isLeapYear()) {
                    continue;
                }
                $clone->days()->create([
                    'date' => Carbon::create($targetYear, $source->month, $source->day)->toDateString(),
                    'schedule_template_id' => $day->schedule_template_id,
                ]);
            }

            return $clone;
        });
    }

    /**
     * Simula las horas de trabajo previstas entre dos fechas (las que se "perderían" si
     * ese período fuese de vacaciones). Devuelve el total de horas y los días afectados.
     *
     * @return array{working_days: int, scheduled_hours: float}
     */
    public function simulateVacation(WorkCalendar $calendar, string $dateFrom, string $dateTo): array
    {
        $days = $calendar->days()
            ->with('template.timeRanges')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get();

        $hours = $days->sum(fn ($day) => $day->template?->dailyHours() ?? 0.0);

        return [
            'working_days' => $days->count(),
            'scheduled_hours' => round((float) $hours, 2),
        ];
    }

    /**
     * Asigna una plantilla a varias fechas (idempotente por día).
     *
     * @param  array<int, string>  $dates
     */
    private function assignDates(WorkCalendar $calendar, array $dates, string $templateId): void
    {
        foreach ($dates as $date) {
            $calendar->days()->updateOrCreate(
                ['date' => $date],
                ['schedule_template_id' => $templateId],
            );
        }
    }

    /**
     * Fechas festivas del año, indexadas por 'Y-m-d' para búsqueda O(1).
     *
     * @return array<string, true>
     */
    private function holidayDates(int $year): array
    {
        $dates = [];

        foreach (Holiday::all() as $holiday) {
            if ($holiday->repeatable && $holiday->day_of_year !== null) {
                $date = Carbon::create($year, 1, 1)->addDays($holiday->day_of_year - 1);
                if ($date->year === $year) {
                    $dates[$date->toDateString()] = true;
                }
            } elseif ($holiday->date !== null && $holiday->date->year === $year) {
                $dates[$holiday->date->toDateString()] = true;
            }
        }

        return $dates;
    }
}
