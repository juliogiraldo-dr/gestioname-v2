<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Agreement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lógica de negocio de convenios.
 */
final class AgreementService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Agreement
    {
        return Agreement::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Agreement $agreement, array $data): Agreement
    {
        $agreement->update($data);

        return $agreement;
    }

    /**
     * @throws BusinessException si el convenio está asignado a empleados.
     */
    public function delete(Agreement $agreement): void
    {
        // La tabla `employees` llega en el Sprint 4; comprobamos su existencia para que
        // el guard funcione ahora (sin empleados) y cuando exista.
        if (Schema::hasTable('employees')
            && DB::table('employees')->where('agreement_id', $agreement->id)->exists()) {
            throw new BusinessException(
                'No se puede eliminar un convenio asignado a empleados.',
                'AGREEMENT_HAS_EMPLOYEES',
                409,
            );
        }

        $agreement->delete();
    }
}
