<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Informes contables: balance de situación, cuenta de resultados, mayor y
 * balance de comprobación (módulo contabilidad).
 */
class AccountingReportsController extends Controller
{
    public function __construct(private readonly AccountingService $service) {}

    public function balanceSheet(Request $request): JsonResponse
    {
        [$year, $entityId, $companyId] = $this->scope($request);

        return response()->json(['data' => $this->service->balanceSheet($year, $entityId, $companyId)]);
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        [$year, $entityId, $companyId] = $this->scope($request);

        return response()->json(['data' => $this->service->incomeStatement($year, $entityId, $companyId)]);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        [$year, $entityId, $companyId] = $this->scope($request);

        return response()->json(['data' => $this->service->trialBalance($year, $entityId, $companyId)]);
    }

    public function ledger(Request $request): JsonResponse
    {
        $request->validate(['account_id' => ['required', 'integer', 'exists:accounts,id']]);
        [$year, $entityId, $companyId] = $this->scope($request);

        return response()->json(['data' => $this->service->ledger($request->integer('account_id'), $year, $entityId, $companyId)]);
    }

    /**
     * @return array{0: ?int, 1: ?string, 2: ?string}
     */
    private function scope(Request $request): array
    {
        return [
            $request->filled('year') ? $request->integer('year') : null,
            $request->string('entity_id')->value() ?: null,
            $request->string('company_id')->value() ?: null,
        ];
    }
}
