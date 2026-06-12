<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agreement;
use App\Models\AgreementLeaveType;

/**
 * Lógica de negocio de tipos de ausencia/presencia.
 */
final class AgreementLeaveTypeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Agreement $agreement, array $data): AgreementLeaveType
    {
        return $agreement->leaveTypes()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AgreementLeaveType $leaveType, array $data): AgreementLeaveType
    {
        $leaveType->update($data);

        return $leaveType;
    }

    public function delete(AgreementLeaveType $leaveType): void
    {
        $leaveType->delete();
    }
}
