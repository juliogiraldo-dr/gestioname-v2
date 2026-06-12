<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lógica de negocio de empresas.
 */
final class CompanyService
{
    /**
     * Hitos de fichaje por defecto que se crean junto con cada empresa.
     *
     * @var list<array{name: string, type: string, color: string}>
     */
    private const DEFAULT_MILESTONES = [
        ['name' => 'ENTRADA', 'type' => 'entrada', 'color' => '#90cbe8'],
        ['name' => 'SALIDA', 'type' => 'salida', 'color' => '#f4978e'],
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company
    {
        return DB::transaction(function () use ($data): Company {
            $company = Company::create($data);

            $company->milestones()->createMany(self::DEFAULT_MILESTONES);

            return $company;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company;
    }

    /**
     * @throws BusinessException si la empresa tiene empleados.
     */
    public function delete(Company $company): void
    {
        // La tabla `employees` llega en el Sprint 4; comprobamos su existencia para que
        // el guard funcione ahora (sin empleados) y cuando exista.
        if (Schema::hasTable('employees')
            && DB::table('employees')->where('company_id', $company->id)->exists()) {
            throw new BusinessException(
                'No se puede eliminar una empresa con empleados asignados.',
                'COMPANY_HAS_EMPLOYEES',
                409,
            );
        }

        $company->delete();
    }
}
