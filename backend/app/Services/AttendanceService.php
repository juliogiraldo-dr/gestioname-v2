<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceMilestone;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de fichajes: registro por PIN con validaciones, fichaje manual y correcciones
 * trazadas (ET 34.9).
 */
final class AttendanceService
{
    /**
     * Registra un fichaje a partir del código de 8 dígitos del empleado.
     *
     * @throws BusinessException INVALID_CLOCK_CODE | IP_NOT_ALLOWED | DOUBLE_ENTRY | NO_OPEN_ENTRY
     */
    public function clock(string $clockCode, string $milestoneId, ?float $lat, ?float $lng, ?string $ip, string $method = 'kiosk'): Attendance
    {
        $employee = Employee::where('clock_code', $clockCode)->where('active', true)->first();

        if ($employee === null) {
            throw new BusinessException('Código de fichaje no encontrado.', 'INVALID_CLOCK_CODE', 422);
        }

        $this->assertIpAllowed($employee, $ip);

        $milestone = AttendanceMilestone::findOrFail($milestoneId);
        if ($milestone->company_id !== $employee->company_id) {
            throw new BusinessException('El hito no pertenece a la empresa del empleado.', 'MILESTONE_COMPANY_MISMATCH', 422);
        }

        $this->assertValidTransition($employee, $milestone);

        return Attendance::create([
            'employee_id' => $employee->id,
            'milestone_id' => $milestone->id,
            'clocked_at' => Carbon::now(),
            'lat' => $lat,
            'lng' => $lng,
            'ip_address' => $ip,
            'method' => $method,
        ]);
    }

    /**
     * Fichaje manual creado por un gestor (sin validación de transición).
     */
    public function manual(string $employeeId, string $milestoneId, string $clockedAt): Attendance
    {
        return Attendance::create([
            'employee_id' => $employeeId,
            'milestone_id' => $milestoneId,
            'clocked_at' => $clockedAt,
            'method' => 'manual',
        ]);
    }

    /**
     * Corrige la hora de un fichaje dejando rastro inmutable del valor anterior.
     */
    public function correct(Attendance $attendance, string $newClockedAt, string $reason, ?string $userId): Attendance
    {
        return DB::transaction(function () use ($attendance, $newClockedAt, $reason, $userId): Attendance {
            AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'corrected_by' => $userId,
                'old_clocked_at' => $attendance->clocked_at,
                'new_clocked_at' => $newClockedAt,
                'reason' => $reason,
            ]);

            $attendance->update(['clocked_at' => $newClockedAt]);

            return $attendance;
        });
    }

    /**
     * Borra (lógicamente) un fichaje dejando registro de auditoría (new_clocked_at = null).
     */
    public function delete(Attendance $attendance, string $reason, ?string $userId): void
    {
        DB::transaction(function () use ($attendance, $reason, $userId): void {
            AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'corrected_by' => $userId,
                'old_clocked_at' => $attendance->clocked_at,
                'new_clocked_at' => null,
                'reason' => $reason,
            ]);

            $attendance->delete();
        });
    }

    private function assertIpAllowed(Employee $employee, ?string $ip): void
    {
        if (! $employee->allowedIps()->exists()) {
            return; // sin restricción de IP
        }

        if ($ip === null || ! $employee->allowedIps()->where('ip_address', $ip)->exists()) {
            throw new BusinessException('La IP no está permitida para este empleado.', 'IP_NOT_ALLOWED', 403);
        }
    }

    private function assertValidTransition(Employee $employee, AttendanceMilestone $milestone): void
    {
        $last = Attendance::where('employee_id', $employee->id)
            ->with('milestone')
            ->orderByDesc('clocked_at')
            ->first();

        $lastType = $last?->milestone?->type;

        if ($milestone->type === 'entrada' && $lastType === 'entrada') {
            throw new BusinessException('Ya existe un fichaje de entrada sin salida.', 'DOUBLE_ENTRY', 409);
        }

        if ($milestone->type === 'salida' && $lastType !== 'entrada') {
            throw new BusinessException('No hay un fichaje de entrada previo.', 'NO_OPEN_ENTRY', 409);
        }
    }
}
