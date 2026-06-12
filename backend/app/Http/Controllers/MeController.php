<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Portal del empleado (prefijo /me). Opera siempre sobre el empleado vinculado al
 * usuario autenticado.
 */
class MeController extends Controller
{
    public function __construct(private readonly LeaveRequestService $leave) {}

    /** Perfil + rol + empresa del usuario autenticado. */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $this->employee($request, required: false);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->all(),
                'employee' => $employee === null ? null : [
                    'id' => $employee->id,
                    'full_name' => $employee->fullName(),
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'company_id' => $employee->company_id,
                    'work_center_id' => $employee->work_center_id,
                    'job_position' => $employee->job_position,
                    'phone_personal' => $employee->phone_personal,
                    'address' => $employee->address,
                    'postal_code' => $employee->postal_code,
                    'city' => $employee->city,
                    'province' => $employee->province,
                    'has_avatar' => $employee->photo_path !== null,
                ],
            ],
        ]);
    }

    public function attendances(Request $request): AnonymousResourceCollection
    {
        $employee = $this->employee($request);

        $attendances = Attendance::where('employee_id', $employee->id)
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('clocked_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('clocked_at', '<=', $request->string('date_to')))
            ->with('milestone')
            ->orderByDesc('clocked_at')
            ->paginate();

        return AttendanceResource::collection($attendances);
    }

    public function schedule(Request $request): JsonResponse
    {
        $employee = $this->employee($request);
        $year = $request->integer('year') ?: (int) now()->year;

        $calendar = $employee->calendars()->where('year', $year)->with('days.template')->first();

        return response()->json([
            'data' => [
                'year' => $year,
                'calendar' => $calendar === null ? null : [
                    'id' => $calendar->id,
                    'name' => $calendar->name,
                    'days' => $calendar->days->map(fn ($d) => [
                        'date' => $d->date->toDateString(),
                        'template' => $d->template?->name,
                        'color' => $d->template?->color,
                        'hours' => $d->template?->dailyHours(),
                    ])->values(),
                ],
            ],
        ]);
    }

    /** Tipos de ausencia/presencia del convenio del empleado (para el formulario). */
    public function leaveTypes(Request $request): JsonResponse
    {
        $employee = $this->employee($request);
        $employee->loadMissing('agreement.leaveTypes');

        $types = ($employee->agreement?->leaveTypes ?? collect())
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'type' => $t->type,
                'count_in' => $t->count_in,
            ])
            ->values();

        return response()->json(['data' => $types]);
    }

    public function leaveRequests(Request $request): AnonymousResourceCollection
    {
        $employee = $this->employee($request);

        return LeaveRequestResource::collection(
            LeaveRequest::where('employee_id', $employee->id)->orderByDesc('date_start')->paginate()
        );
    }

    public function storeLeaveRequest(Request $request): JsonResponse
    {
        $employee = $this->employee($request);

        $data = $request->validate([
            'leave_type_id' => ['required', 'uuid', 'exists:agreement_leave_types,id'],
            'date_start' => ['required', 'date_format:Y-m-d'],
            'date_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_start'],
            'time_start' => ['nullable', 'date_format:H:i'],
            'time_end' => ['nullable', 'date_format:H:i'],
            'description' => ['nullable', 'string'],
        ]);

        // employee_id siempre el del usuario autenticado.
        $leaveRequest = $this->leave->create(array_merge($data, ['employee_id' => $employee->id]));

        return LeaveRequestResource::make($leaveRequest)->response()->setStatusCode(201);
    }

    public function vacations(Request $request): JsonResponse
    {
        $employee = $this->employee($request);
        $year = $request->integer('year') ?: (int) now()->year;

        return response()->json(['data' => $this->leave->vacationSummary($employee, $year)]);
    }

    /** Nóminas del empleado autenticado (sin el fichero; la descarga es aparte). */
    public function payslips(Request $request): JsonResponse
    {
        $employee = $this->employee($request);

        $payslips = $employee->payslips()
            ->orderByDesc('year')->orderByDesc('month')
            ->get()
            ->map(fn (Payslip $p) => [
                'id' => $p->id,
                'month' => $p->month,
                'year' => $p->year,
                'period' => $p->periodLabel(),
                'created_at' => $p->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $payslips]);
    }

    /** Descarga de una nómina propia (comprueba la propiedad). */
    public function downloadPayslip(Request $request, Payslip $payslip): StreamedResponse
    {
        $employee = $this->employee($request);
        abort_unless($payslip->employee_id === $employee->id, 403);
        abort_unless(Storage::exists($payslip->file_path), 404);

        return Storage::download($payslip->file_path, $payslip->original_name);
    }

    /** Datos editables por el propio empleado (contacto). No toca datos sensibles ni laborales. */
    public function updateProfile(Request $request): JsonResponse
    {
        $employee = $this->employee($request);

        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:120'],
            'last_name' => ['sometimes', 'string', 'max:120'],
            'phone_personal' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
        ]);

        $employee->update($data);

        return response()->json(['data' => [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'phone_personal' => $employee->phone_personal,
            'address' => $employee->address,
            'postal_code' => $employee->postal_code,
            'city' => $employee->city,
            'province' => $employee->province,
        ]]);
    }

    /** Sube/reemplaza la foto de avatar del propio empleado. */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $employee = $this->employee($request);

        $request->validate(['file' => ['required', 'image', 'max:4096', 'mimes:jpg,jpeg,png,webp']]);

        if ($employee->photo_path) {
            Storage::delete($employee->photo_path);
        }
        $path = $request->file('file')->store("avatars/{$employee->id}");
        $employee->update(['photo_path' => $path]);

        return response()->json(['message' => 'Foto actualizada.']);
    }

    /** Devuelve la foto de avatar del propio empleado (stream autenticado). */
    public function avatar(Request $request): StreamedResponse
    {
        $employee = $this->employee($request);
        abort_if($employee->photo_path === null || ! Storage::exists($employee->photo_path), 404);

        return Storage::response($employee->photo_path);
    }

    /** Datos laborales del empleado: contrato, convenio y horario asignado (solo lectura). */
    public function laborData(Request $request): JsonResponse
    {
        $employee = $this->employee($request);
        $employee->loadMissing(['company', 'workCenter', 'agreement']);
        $year = (int) now()->year;
        $calendar = $employee->calendars()->where('year', $year)->first();

        return response()->json(['data' => [
            'contract' => [
                'company' => $employee->company?->name,
                'work_center' => $employee->workCenter?->name,
                'department' => $employee->department,
                'job_position' => $employee->job_position,
                'job_category' => $employee->job_category,
                'employment_status' => $employee->employment_status,
                'hire_date' => $employee->hire_date?->toDateString(),
            ],
            'agreement' => $employee->agreement === null ? null : [
                'name' => $employee->agreement->name,
                'annual_hours' => $employee->agreement->annual_hours,
                'vacation_days' => $employee->agreement->vacation_days,
                'vacation_type' => $employee->agreement->vacation_type,
            ],
            'schedule' => $calendar === null ? null : [
                'year' => $year,
                'calendar' => $calendar->name,
            ],
        ]]);
    }

    /**
     * Empleado vinculado al usuario autenticado.
     *
     * @throws BusinessException si el usuario no tiene ficha de empleado.
     */
    private function employee(Request $request, bool $required = true): ?Employee
    {
        $employee = Employee::where('user_id', $request->user()->id)->first();

        if ($employee === null && $required) {
            throw new BusinessException('El usuario no tiene ficha de empleado.', 'NO_EMPLOYEE_PROFILE', 404);
        }

        return $employee;
    }
}
