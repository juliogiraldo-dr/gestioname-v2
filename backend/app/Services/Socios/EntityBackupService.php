<?php

declare(strict_types=1);

namespace App\Services\Socios;

use App\Models\Entity;
use Illuminate\Support\Facades\DB;

/**
 * Backup completo de una entidad en JSON: tipos de socio, socios, pagos, categorías y gastos.
 */
final class EntityBackupService
{
    /**
     * @return array<string, mixed>
     */
    public function export(Entity $entity): array
    {
        $entity->loadMissing(['memberTypes', 'members.payments', 'expenseCategories', 'expenses']);

        return [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'entity' => $entity->only(['name', 'type', 'cif', 'address', 'phone', 'email', 'opening_balance', 'fiscal_year']),
            'member_types' => $entity->memberTypes->map->only(['id', 'name', 'description', 'fee_amount', 'fee_periodicity', 'active'])->all(),
            'members' => $entity->members->map(fn ($m) => array_merge(
                $m->only(['id', 'member_type_id', 'member_number', 'first_name', 'last_name', 'dni', 'email', 'phone', 'status', 'date_join']),
                ['payments' => $m->payments->map->only(['year', 'amount', 'status', 'payment_date', 'payment_method', 'reference'])->all()],
            ))->all(),
            'expense_categories' => $entity->expenseCategories->map->only(['id', 'name', 'color'])->all(),
            'expenses' => $entity->expenses->map->only(['category_id', 'amount', 'date', 'description'])->all(),
        ];
    }

    /**
     * Restaura una entidad nueva a partir de un backup. Devuelve la entidad creada.
     *
     * @param  array<string, mixed>  $data
     */
    public function import(array $data): Entity
    {
        return DB::transaction(function () use ($data): Entity {
            $entity = Entity::create($data['entity']);

            $typeMap = [];
            foreach ($data['member_types'] ?? [] as $t) {
                $new = $entity->memberTypes()->create(collect($t)->except('id')->all());
                $typeMap[$t['id']] = $new->id;
            }

            $catMap = [];
            foreach ($data['expense_categories'] ?? [] as $c) {
                $new = $entity->expenseCategories()->create(collect($c)->except('id')->all());
                $catMap[$c['id']] = $new->id;
            }

            foreach ($data['members'] ?? [] as $m) {
                $member = $entity->members()->create([
                    'member_type_id' => isset($m['member_type_id']) ? ($typeMap[$m['member_type_id']] ?? null) : null,
                    'member_number' => $m['member_number'] ?? null,
                    'first_name' => $m['first_name'], 'last_name' => $m['last_name'] ?? null,
                    'dni' => $m['dni'] ?? null, 'email' => $m['email'] ?? null, 'phone' => $m['phone'] ?? null,
                    'status' => $m['status'] ?? 'activo', 'date_join' => $m['date_join'] ?? null,
                ]);
                foreach ($m['payments'] ?? [] as $p) {
                    $member->payments()->create(array_merge($p, ['entity_id' => $entity->id]));
                }
            }

            foreach ($data['expenses'] ?? [] as $e) {
                $entity->expenses()->create([
                    'category_id' => isset($e['category_id']) ? ($catMap[$e['category_id']] ?? null) : null,
                    'amount' => $e['amount'], 'date' => $e['date'], 'description' => $e['description'],
                ]);
            }

            return $entity;
        });
    }
}
