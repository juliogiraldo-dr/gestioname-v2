<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gestoria;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payslip;
use App\Notifications\PayslipAvailableNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Nóminas para el panel de gestoría. Accesible por admin y gestoría.
 * Nunca expone datos sensibles del empleado (DNI/IBAN quedan ocultos por el modelo).
 */
class PayslipController extends Controller
{
    /** Empleados activos con sus nóminas (solo datos no sensibles). */
    public function index(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%'.$request->string('search')->toString().'%';
                $q->where(fn ($w) => $w->where('first_name', 'ilike', $s)->orWhere('last_name', 'ilike', $s));
            })
            ->orderBy('first_name')
            ->with(['payslips' => fn ($q) => $q->orderByDesc('year')->orderByDesc('month')])
            ->paginate(20);

        $employees->getCollection()->transform(fn (Employee $e) => [
            'id' => $e->id,
            'full_name' => $e->fullName(),
            'email' => $e->contactEmail(),
            'company_id' => $e->company_id,
            'job_position' => $e->job_position,
            'payslips' => $e->payslips->map(fn (Payslip $p) => [
                'id' => $p->id,
                'month' => $p->month,
                'year' => $p->year,
                'period' => $p->periodLabel(),
                'original_name' => $p->original_name,
                'notified_at' => $p->notified_at?->toIso8601String(),
                'created_at' => $p->created_at?->toIso8601String(),
            ])->values(),
        ]);

        return response()->json($employees);
    }

    /** Sube (o reemplaza) la nómina de un empleado y le avisa por email. */
    public function store(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ]);

        $file = $request->file('file');
        $path = $file->store("payslips/{$employee->id}");

        // Reemplaza la nómina existente del mismo periodo (borra el fichero anterior).
        $existing = $employee->payslips()
            ->where('year', (int) $data['year'])
            ->where('month', (int) $data['month'])
            ->first();
        if ($existing !== null) {
            Storage::delete($existing->file_path);
            $existing->delete();
        }

        $payslip = $employee->payslips()->create([
            'month' => (int) $data['month'],
            'year' => (int) $data['year'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => $request->user()?->id,
        ]);

        // Aviso automático al empleado «Tu nómina de [mes/año] está disponible».
        $email = $employee->contactEmail();
        if ($email !== null) {
            Notification::route('mail', $email)->notify(
                new PayslipAvailableNotification($payslip->periodLabel(), app('tenant')->subdomain)
            );
            $payslip->update(['notified_at' => now()]);
        }

        return response()->json(['data' => [
            'id' => $payslip->id,
            'month' => $payslip->month,
            'year' => $payslip->year,
            'period' => $payslip->periodLabel(),
            'original_name' => $payslip->original_name,
            'notified' => $email !== null,
        ]], 201);
    }

    /** Descarga directa (gestoría/admin autenticados). */
    public function download(Payslip $payslip): StreamedResponse
    {
        abort_unless(Storage::exists($payslip->file_path), 404);

        return Storage::download($payslip->file_path, $payslip->original_name);
    }

    public function destroy(Payslip $payslip): JsonResponse
    {
        Storage::delete($payslip->file_path);
        $payslip->delete();

        return response()->json(['message' => 'Nómina eliminada.']);
    }
}
