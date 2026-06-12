<?php

declare(strict_types=1);

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeQualification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeQualificationController extends Controller
{
    public function index(Employee $employee): JsonResponse
    {
        return response()->json(['data' => $employee->qualifications()->orderByDesc('date_obtained')->get()]);
    }

    public function store(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:titulacion,curso,certificado,conocimiento,experiencia'],
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'date_obtained' => ['nullable', 'date_format:Y-m-d'],
            'expiry_date' => ['nullable', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string'],
        ]);

        $q = $employee->qualifications()->create($data);

        return response()->json(['data' => $q], 201);
    }

    public function destroy(EmployeeQualification $qualification): JsonResponse
    {
        $qualification->delete();

        return response()->json(['message' => 'Eliminado.']);
    }
}
