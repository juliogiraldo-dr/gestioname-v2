<?php

declare(strict_types=1);

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeMaterialController extends Controller
{
    public function index(Employee $employee): JsonResponse
    {
        return response()->json(['data' => $employee->materials()->orderByDesc('delivery_date')->get()]);
    }

    public function store(Request $request, Employee $employee): JsonResponse
    {
        $material = $employee->materials()->create($this->validateData($request));

        return response()->json(['data' => $material], 201);
    }

    public function update(Request $request, EmployeeMaterial $material): JsonResponse
    {
        $material->update($this->validateData($request));

        return response()->json(['data' => $material]);
    }

    public function destroy(EmployeeMaterial $material): JsonResponse
    {
        $material->delete();

        return response()->json(['message' => 'Eliminado.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d'],
            'return_date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:entregado,devuelto,perdido'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
