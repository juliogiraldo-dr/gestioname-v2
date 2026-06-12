<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
                    'company_id' => $employee->company_id,
                    'work_center_id' => $employee->work_center_id,
                    'job_position' => $employee->job_position,
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
