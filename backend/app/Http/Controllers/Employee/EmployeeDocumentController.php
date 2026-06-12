<?php

declare(strict_types=1);

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function index(Employee $employee): JsonResponse
    {
        return response()->json(['data' => $employee->documents()->orderByDesc('created_at')->get()]);
    }

    public function store(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
            'name' => ['nullable', 'string', 'max:255'],
            'visible_to_employee' => ['boolean'],
        ]);

        $file = $request->file('file');
        $path = $file->store("employee-documents/{$employee->id}");

        $doc = $employee->documents()->create([
            'name' => $request->string('name')->value() ?: $file->getClientOriginalName(),
            'type' => $file->getClientOriginalExtension(),
            'file_path' => $path,
            'visible_to_employee' => $request->boolean('visible_to_employee', true),
        ]);

        return response()->json(['data' => $doc], 201);
    }

    public function download(EmployeeDocument $document): StreamedResponse
    {
        abort_unless(Storage::exists($document->file_path), 404);

        return Storage::download($document->file_path, $document->name);
    }

    public function destroy(EmployeeDocument $document): JsonResponse
    {
        Storage::delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Documento eliminado.']);
    }
}
