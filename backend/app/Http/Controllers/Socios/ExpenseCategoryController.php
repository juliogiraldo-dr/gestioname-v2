<?php

declare(strict_types=1);

namespace App\Http\Controllers\Socios;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseCategory\StoreExpenseCategoryRequest;
use App\Http\Requests\ExpenseCategory\UpdateExpenseCategoryRequest;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\Entity;
use App\Models\ExpenseCategory;
use App\Services\Socios\ExpenseCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseCategoryController extends Controller
{
    public function __construct(private readonly ExpenseCategoryService $service) {}

    public function index(Entity $entity): AnonymousResourceCollection
    {
        return ExpenseCategoryResource::collection($entity->expenseCategories()->orderBy('name')->get());
    }

    public function store(StoreExpenseCategoryRequest $request, Entity $entity): JsonResponse
    {
        $category = $this->service->create($entity, $request->validated());

        return ExpenseCategoryResource::make($category)->response()->setStatusCode(201);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): ExpenseCategoryResource
    {
        return ExpenseCategoryResource::make($this->service->update($expenseCategory, $request->validated()));
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->service->delete($expenseCategory);

        return response()->json(['message' => 'Categoría eliminada correctamente.']);
    }
}
