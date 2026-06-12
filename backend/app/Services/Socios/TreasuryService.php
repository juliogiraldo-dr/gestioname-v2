<?php

declare(strict_types=1);

namespace App\Services\Socios;

use App\Models\Entity;
use App\Models\Expense;
use App\Models\MemberPayment;

/**
 * Cálculo de tesorería de una entidad por ejercicio:
 * saldo_banco = saldo_inicial + SUM(ingresos cobrados) - SUM(gastos).
 */
final class TreasuryService
{
    /**
     * @return array{
     *   year: int, opening_balance: float, income: float, pending: float,
     *   expenses: float, balance: float
     * }
     */
    public function compute(Entity $entity, int $year): array
    {
        $payments = MemberPayment::where('entity_id', $entity->id)->where('year', $year);

        // Ingresos cobrados: pagos no pendientes (pagado/parcial).
        $income = (float) (clone $payments)->where('status', '!=', 'pendiente')->sum('amount');
        $pending = (float) (clone $payments)->where('status', 'pendiente')->sum('amount');

        $expenses = (float) Expense::where('entity_id', $entity->id)
            ->whereYear('date', $year)
            ->sum('amount');

        $opening = (float) $entity->opening_balance;

        return [
            'year' => $year,
            'opening_balance' => round($opening, 2),
            'income' => round($income, 2),
            'pending' => round($pending, 2),
            'expenses' => round($expenses, 2),
            'balance' => round($opening + $income - $expenses, 2),
        ];
    }
}
