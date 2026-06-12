<?php

declare(strict_types=1);

namespace App\Services\Socios;

use App\Exceptions\BusinessException;
use App\Models\Entity;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de entidades/asociaciones.
 */
final class EntityService
{
    /** Categorías de gasto que se crean junto con cada entidad. */
    private const DEFAULT_EXPENSE_CATEGORIES = [
        ['name' => 'Alquiler local', 'color' => '#5eb8d0'],
        ['name' => 'Material', 'color' => '#68dfb9'],
        ['name' => 'Actos y eventos', 'color' => '#f4978e'],
        ['name' => 'Seguros', 'color' => '#0f2756'],
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Entity
    {
        return DB::transaction(function () use ($data): Entity {
            $entity = Entity::create($data);
            $entity->expenseCategories()->createMany(self::DEFAULT_EXPENSE_CATEGORIES);

            return $entity;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Entity $entity, array $data): Entity
    {
        $entity->update($data);

        return $entity;
    }

    /**
     * @throws BusinessException si la entidad tiene socios.
     */
    public function delete(Entity $entity): void
    {
        if ($entity->members()->exists()) {
            throw new BusinessException(
                'No se puede eliminar una entidad con socios.',
                'ENTITY_HAS_MEMBERS',
                409,
            );
        }

        $entity->delete();
    }
}
