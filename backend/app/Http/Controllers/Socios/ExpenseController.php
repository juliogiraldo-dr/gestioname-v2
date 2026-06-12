<?php

declare(strict_types=1);

namespace App\Http\Controllers\Socios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Entity;
use App\Models\Expense;
use App\Services\Socios\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $service) {}

    public function index(Request $request, Entity $entity): AnonymousResourceCollection
    {
        $expenses = $entity->expenses()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->string('category_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date', '<=', $request->string('date_to')))
            ->with('category')
            ->orderByDesc('date')
            ->paginate();

        return ExpenseResource::collection($expenses);
    }

    public function store(StoreExpenseRequest $request, Entity $entity): JsonResponse
    {
        $expense = $this->service->create($entity, $request->validated(), $request->user()?->id);

        return ExpenseResource::make($expense->load('category'))->response()->setStatusCode(201);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        return ExpenseResource::make($this->service->update($expense, $request->validated())->load('category'));
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->service->delete($expense);

        return response()->json(['message' => 'Gasto eliminado correctamente.']);
    }
}
