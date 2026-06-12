<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índice de Bradford: B = S² × D
 *   S = número de episodios (spells) de ausencia
 *   D = total de días de ausencia
 * Mide el impacto del absentismo penalizando muchas ausencias cortas frente a pocas largas.
 */
final class BradfordIndexCalculator
{
    /** Ventana de cálculo por defecto (52 semanas). */
    private const WINDOW_DAYS = 364;

    /**
     * Calcula el índice a partir de la duración (en días) de cada episodio de ausencia.
     *
     * @param  array<int, int|float>  $spellDays
     */
    public function fromSpells(array $spellDays): int
    {
        $s = count($spellDays);
        $d = (int) round(array_sum($spellDays));

        return $s * $s * $d;
    }

    /**
     * Índice de un empleado a partir de sus ausencias aprobadas en la ventana reciente.
     * Usa `leave_requests` (Sprint 6); si la tabla no existe aún, devuelve 0.
     *
     * @return array{index: int, spells: int, total_days: float, since: string}
     */
    public function forEmployee(Employee $employee): array
    {
        $since = Carbon::now()->subDays(self::WINDOW_DAYS)->toDateString();

        if (! Schema::hasTable('leave_requests')) {
            return ['index' => 0, 'spells' => 0, 'total_days' => 0.0, 'since' => $since];
        }

        $spells = DB::table('leave_requests')
            ->where('employee_id', $employee->id)
            ->where('status', 'aprobada')
            ->where('date_start', '>=', $since)
            ->pluck('total_days')
            ->map(fn ($d) => (float) $d)
            ->all();

        return [
            'index' => $this->fromSpells($spells),
            'spells' => count($spells),
            'total_days' => array_sum($spells),
            'since' => $since,
        ];
    }
}
