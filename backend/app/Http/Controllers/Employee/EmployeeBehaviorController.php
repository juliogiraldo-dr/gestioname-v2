<?php

declare(strict_types=1);

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeBehaviorRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeBehaviorController extends Controller
{
    public function index(Employee $employee): JsonResponse
    {
        return response()->json(['data' => $employee->behaviorRecords()->orderByDesc('date')->get()]);
    }

    public function store(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:felicitacion,amonestacion,sancion'],
            'date' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string'],
        ]);
        $data['created_by'] = $request->user()?->id;

        $record = $employee->behaviorRecords()->create($data);

        return response()->json(['data' => $record], 201);
    }

    public function destroy(EmployeeBehaviorRecord $behaviorRecord): JsonResponse
    {
        $behaviorRecord->delete();

        return response()->json(['message' => 'Eliminado.']);
    }
}
