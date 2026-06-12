<?php

declare(strict_types=1);

namespace App\Services\Socios;

use App\Exceptions\BusinessException;
use App\Models\Entity;
use App\Models\MemberType;

/**
 * Lógica de negocio de tipos de socio.
 */
final class MemberTypeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Entity $entity, array $data): MemberType
    {
        return $entity->memberTypes()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MemberType $memberType, array $data): MemberType
    {
        $memberType->update($data);

        return $memberType;
    }

    /**
     * @throws BusinessException si hay socios con este tipo.
     */
    public function delete(MemberType $memberType): void
    {
        if ($memberType->members()->exists()) {
            throw new BusinessException(
                'No se puede eliminar un tipo de socio en uso.',
                'MEMBER_TYPE_IN_USE',
                409,
            );
        }

        $memberType->delete();
    }
}
