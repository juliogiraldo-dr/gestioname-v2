<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Plan de cuentas (módulo contabilidad).
 */
class AccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $accounts = Account::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('parent_id'), fn ($q) => $q->where('parent_id', $request->integer('parent_id')))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('active', true))
            ->orderBy('code')
            ->get();

        return response()->json(['data' => $accounts]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);
        $account = Account::create($data);

        return response()->json(['data' => $account], 201);
    }

    public function update(Request $request, Account $account): JsonResponse
    {
        $data = $this->validateData($request, $account->id);
        $account->update($data);

        return response()->json(['data' => $account]);
    }

    public function destroy(Account $account): JsonResponse
    {
        if ($account->lines()->exists()) {
            throw new BusinessException('No se puede borrar una cuenta con movimientos.', 'ACCOUNT_HAS_MOVEMENTS', 422);
        }
        $account->delete();

        return response()->json(['message' => 'Cuenta eliminada.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('accounts', 'code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:activo,pasivo,patrimonio,ingreso,gasto'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'active' => ['boolean'],
        ]);
    }
}
