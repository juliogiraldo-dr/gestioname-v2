<?php

declare(strict_types=1);

namespace App\Services\Socios;

use App\Models\Entity;
use App\Models\Expense;

/**
 * Lógica de negocio de gastos.
 */
final class ExpenseService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Entity $entity, array $data, ?string $userId): Expense
    {
        $data['created_by'] = $userId;

        return $entity->expenses()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);

        return $expense;
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }
}
