<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FiscalPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Ejercicios fiscales (módulo contabilidad).
 */
class FiscalPeriodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $periods = FiscalPeriod::query()
            ->when($request->filled('entity_id'), fn ($q) => $q->where('entity_id', $request->string('entity_id')))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')))
            ->orderByDesc('year')
            ->get();

        return response()->json(['data' => $periods]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'entity_id' => ['nullable', 'uuid', 'exists:entities,id'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
        ]);

        $period = FiscalPeriod::firstOrCreate(
            ['year' => $data['year'], 'entity_id' => $data['entity_id'] ?? null, 'company_id' => $data['company_id'] ?? null],
            ['status' => 'open'],
        );

        return response()->json(['data' => $period], 201);
    }

    /** Cierra el ejercicio (no admite más asientos en ese año/ámbito). */
    public function close(FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $fiscalPeriod->update(['status' => 'closed', 'closed_at' => Carbon::now()]);

        return response()->json(['data' => $fiscalPeriod]);
    }

    /** Reabre un ejercicio cerrado. */
    public function reopen(FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $fiscalPeriod->update(['status' => 'open', 'closed_at' => null]);

        return response()->json(['data' => $fiscalPeriod]);
    }
}
