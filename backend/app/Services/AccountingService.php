<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lógica del módulo de contabilidad: alta de asientos cuadrados e informes
 * (balance de comprobación, balance de situación, cuenta de resultados, mayor).
 */
class AccountingService
{
    /**
     * Crea un asiento con sus líneas. Valida cuadre y ejercicio abierto.
     *
     * @param  array<string, mixed>  $data
     * @param  list<array{account_id: int, debit?: float, credit?: float, description?: string}>  $lines
     *
     * @throws BusinessException UNBALANCED_ENTRY | PERIOD_CLOSED
     */
    public function createEntry(array $data, array $lines, ?string $userId): JournalEntry
    {
        $this->assertBalanced($lines);
        $this->assertPeriodOpen(Carbon::parse($data['date'])->year, $data['entity_id'] ?? null, $data['company_id'] ?? null);

        return DB::transaction(function () use ($data, $lines, $userId): JournalEntry {
            $entry = JournalEntry::create([
                'date' => $data['date'],
                'description' => $data['description'],
                'reference' => $data['reference'] ?? null,
                'entity_id' => $data['entity_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    /**
     * Reemplaza las líneas de un asiento existente (re-valida cuadre).
     *
     * @param  array<string, mixed>  $data
     * @param  list<array{account_id: int, debit?: float, credit?: float, description?: string}>  $lines
     */
    public function updateEntry(JournalEntry $entry, array $data, array $lines): JournalEntry
    {
        $this->assertBalanced($lines);

        return DB::transaction(function () use ($entry, $data, $lines): JournalEntry {
            $entry->update([
                'date' => $data['date'],
                'description' => $data['description'],
                'reference' => $data['reference'] ?? null,
            ]);
            $entry->lines()->delete();
            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    /**
     * @param  list<array{debit?: float, credit?: float}>  $lines
     */
    private function assertBalanced(array $lines): void
    {
        $debit = array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $lines));
        $credit = array_sum(array_map(fn ($l) => (float) ($l['credit'] ?? 0), $lines));

        if (round($debit, 2) !== round($credit, 2)) {
            throw new BusinessException('El asiento no cuadra: el debe y el haber deben ser iguales.', 'UNBALANCED_ENTRY', 422);
        }
        if (round($debit, 2) === 0.0) {
            throw new BusinessException('El asiento no tiene importe.', 'EMPTY_ENTRY', 422);
        }
    }

    private function assertPeriodOpen(int $year, ?string $entityId, ?string $companyId): void
    {
        $closed = FiscalPeriod::query()
            ->where('year', $year)
            ->where('status', 'closed')
            ->when($entityId, fn ($q) => $q->where('entity_id', $entityId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->exists();

        if ($closed) {
            throw new BusinessException("El ejercicio {$year} está cerrado.", 'PERIOD_CLOSED', 422);
        }
    }

    /**
     * Sumas por cuenta (debe, haber, saldo) para un año/ámbito. Base de los informes.
     *
     * @return Collection<int, object>
     */
    private function accountTotals(?int $year, ?string $entityId, ?string $companyId): Collection
    {
        return DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->when($year, fn ($q) => $q->whereYear('journal_entries.date', $year))
            ->when($entityId, fn ($q) => $q->where('journal_entries.entity_id', $entityId))
            ->when($companyId, fn ($q) => $q->where('journal_entries.company_id', $companyId))
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('SUM(journal_lines.debit) as debit'),
                DB::raw('SUM(journal_lines.credit) as credit'),
            )
            ->get();
    }

    /**
     * Balance de comprobación: todas las cuentas con movimiento.
     *
     * @return array<int, array<string, mixed>>
     */
    public function trialBalance(?int $year, ?string $entityId, ?string $companyId): array
    {
        return $this->accountTotals($year, $entityId, $companyId)->map(fn ($r) => [
            'code' => $r->code,
            'name' => $r->name,
            'type' => $r->type,
            'debit' => round((float) $r->debit, 2),
            'credit' => round((float) $r->credit, 2),
            'balance' => round((float) $r->debit - (float) $r->credit, 2),
        ])->values()->all();
    }

    /**
     * Balance de situación: activo vs. pasivo + patrimonio (+ resultado del ejercicio).
     *
     * @return array<string, mixed>
     */
    public function balanceSheet(?int $year, ?string $entityId, ?string $companyId): array
    {
        $totals = $this->accountTotals($year, $entityId, $companyId);

        $activo = $this->group($totals, 'activo', debitNatural: true);
        $pasivo = $this->group($totals, 'pasivo', debitNatural: false);
        $patrimonio = $this->group($totals, 'patrimonio', debitNatural: false);

        $ingresos = $this->sum($totals, 'ingreso', debitNatural: false);
        $gastos = $this->sum($totals, 'gasto', debitNatural: true);
        $resultado = round($ingresos - $gastos, 2);

        $totalActivo = round((float) array_sum(array_column($activo, 'amount')), 2);
        $totalPasivoPatrimonio = round(
            (float) array_sum(array_column($pasivo, 'amount'))
            + (float) array_sum(array_column($patrimonio, 'amount'))
            + $resultado,
            2,
        );

        return [
            'activo' => ['accounts' => $activo, 'total' => $totalActivo],
            'pasivo' => ['accounts' => $pasivo, 'total' => round((float) array_sum(array_column($pasivo, 'amount')), 2)],
            'patrimonio' => [
                'accounts' => $patrimonio,
                'resultado_ejercicio' => $resultado,
                'total' => round((float) array_sum(array_column($patrimonio, 'amount')) + $resultado, 2),
            ],
            'total_activo' => $totalActivo,
            'total_pasivo_patrimonio' => $totalPasivoPatrimonio,
            'balanced' => $totalActivo === $totalPasivoPatrimonio,
        ];
    }

    /**
     * Cuenta de resultados: ingresos, gastos y resultado.
     *
     * @return array<string, mixed>
     */
    public function incomeStatement(?int $year, ?string $entityId, ?string $companyId): array
    {
        $totals = $this->accountTotals($year, $entityId, $companyId);

        $ingresos = $this->group($totals, 'ingreso', debitNatural: false);
        $gastos = $this->group($totals, 'gasto', debitNatural: true);
        $totalIngresos = round((float) array_sum(array_column($ingresos, 'amount')), 2);
        $totalGastos = round((float) array_sum(array_column($gastos, 'amount')), 2);

        return [
            'ingresos' => ['accounts' => $ingresos, 'total' => $totalIngresos],
            'gastos' => ['accounts' => $gastos, 'total' => $totalGastos],
            'resultado' => round($totalIngresos - $totalGastos, 2),
        ];
    }

    /**
     * Libro mayor de una cuenta: movimientos con saldo acumulado.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ledger(int $accountId, ?int $year, ?string $entityId, ?string $companyId): array
    {
        $lines = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.account_id', $accountId)
            ->when($year, fn ($q) => $q->whereYear('journal_entries.date', $year))
            ->when($entityId, fn ($q) => $q->where('journal_entries.entity_id', $entityId))
            ->when($companyId, fn ($q) => $q->where('journal_entries.company_id', $companyId))
            ->orderBy('journal_entries.date')
            ->orderBy('journal_entries.created_at')
            ->select('journal_entries.date', 'journal_entries.description', 'journal_lines.debit', 'journal_lines.credit')
            ->get();

        $running = 0.0;
        $out = [];
        foreach ($lines as $l) {
            $running += (float) $l->debit - (float) $l->credit;
            $out[] = [
                'date' => $l->date,
                'description' => $l->description,
                'debit' => round((float) $l->debit, 2),
                'credit' => round((float) $l->credit, 2),
                'balance' => round($running, 2),
            ];
        }

        return $out;
    }

    /**
     * @param  Collection<int, object>  $totals
     * @return array<int, array{code: string, name: string, amount: float}>
     */
    private function group(Collection $totals, string $type, bool $debitNatural): array
    {
        return $totals->where('type', $type)->map(fn ($r) => [
            'code' => $r->code,
            'name' => $r->name,
            'amount' => round($debitNatural
                ? (float) $r->debit - (float) $r->credit
                : (float) $r->credit - (float) $r->debit, 2),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, object>  $totals
     */
    private function sum(Collection $totals, string $type, bool $debitNatural): float
    {
        return $totals->where('type', $type)->sum(fn ($r) => $debitNatural
            ? (float) $r->debit - (float) $r->credit
            : (float) $r->credit - (float) $r->debit);
    }
}
