<?php

declare(strict_types=1);

namespace App\Services\Socios;

use App\Models\Entity;
use App\Models\ExpenseCategory;

/**
 * Lógica de negocio de categorías de gasto.
 */
final class ExpenseCategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Entity $entity, array $data): ExpenseCategory
    {
        return $entity->expenseCategories()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExpenseCategory $category, array $data): ExpenseCategory
    {
        $category->update($data);

        return $category;
    }

    public function delete(ExpenseCategory $category): void
    {
        // Los gastos quedan con category_id = null (FK nullOnDelete).
        $category->delete();
    }
}
