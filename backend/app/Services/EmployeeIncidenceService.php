<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Carbon;

/**
 * Detecta incidencias de configuración de un empleado (datos que faltan o no cuadran).
 */
final class EmployeeIncidenceService
{
    /**
     * @return list<string> Códigos de incidencia.
     */
    public function for(Employee $employee): array
    {
        $issues = [];

        if ($employee->work_center_id === null) {
            $issues[] = 'no_work_center';
        }

        if ($employee->agreement_id === null) {
            $issues[] = 'no_agreement';
        }

        $year = Carbon::now()->year;
        if (! $employee->calendars()->where('year', $year)->exists()) {
            $issues[] = 'no_calendar';
        }

        // Horas previstas (calendario) vs. horas del convenio.
        $employee->loadMissing('agreement');
        if ($employee->agreement !== null) {
            $scheduled = $this->scheduledAnnualHours($employee, $year);
            if ($scheduled !== null) {
                $target = (float) $employee->agreement->annual_hours;
                if ($scheduled + 0.01 < $target) {
                    $issues[] = 'insufficient_hours';
                } elseif ($scheduled - 0.01 > $target) {
                    $issues[] = 'excess_hours';
                }
            }
        }

        return $issues;
    }

    /**
     * Suma las horas de los días del/los calendario(s) del empleado para el año dado.
     * Devuelve null si el empleado no tiene calendario asignado ese año.
     */
    private function scheduledAnnualHours(Employee $employee, int $year): ?float
    {
        $calendars = $employee->calendars()->where('year', $year)->with('days.template.timeRanges')->get();

        if ($calendars->isEmpty()) {
            return null;
        }

        $hours = 0.0;
        foreach ($calendars as $calendar) {
            foreach ($calendar->days as $day) {
                $hours += $day->template?->dailyHours() ?? 0.0;
            }
        }

        return round($hours, 2);
    }
}
