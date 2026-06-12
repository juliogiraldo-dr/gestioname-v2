<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de empleados: alta (con generación de código de fichaje), edición,
 * activación/desactivación e IPs permitidas.
 */
final class EmployeeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        $ips = $data['allowed_ips'] ?? null;
        unset($data['allowed_ips']);

        if (empty($data['clock_code']) && empty($data['exempt_from_clock'])) {
            $data['clock_code'] = $this->generateClockCode();
        }

        return DB::transaction(function () use ($data, $ips): Employee {
            $employee = Employee::create($data);
            $this->syncIps($employee, $ips);

            return $employee;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        $ips = array_key_exists('allowed_ips', $data) ? ($data['allowed_ips'] ?? []) : null;
        unset($data['allowed_ips']);

        return DB::transaction(function () use ($employee, $data, $ips): Employee {
            $employee->update($data);

            if ($ips !== null) {
                $employee->allowedIps()->delete();
                $this->syncIps($employee, $ips);
            }

            return $employee;
        });
    }

    public function setActive(Employee $employee, bool $active): Employee
    {
        $employee->update([
            'active' => $active,
            'employment_status' => $active ? 'active' : 'inactive',
        ]);

        return $employee;
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }

    /** Genera un código de fichaje único de 8 dígitos. */
    public function generateClockCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 99_999_999), 8, '0', STR_PAD_LEFT);
        } while (Employee::where('clock_code', $code)->exists());

        return $code;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $ips
     */
    private function syncIps(Employee $employee, ?array $ips): void
    {
        if (empty($ips)) {
            return;
        }

        foreach ($ips as $ip) {
            $employee->allowedIps()->create([
                'ip_address' => $ip['ip_address'],
                'description' => $ip['description'] ?? null,
            ]);
        }
    }
}
