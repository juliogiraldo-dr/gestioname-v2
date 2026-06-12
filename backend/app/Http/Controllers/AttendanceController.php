<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\Attendance\ClockRequest;
use App\Http\Requests\Attendance\CorrectAttendanceRequest;
use App\Http\Requests\Attendance\DeleteAttendanceRequest;
use App\Http\Requests\Attendance\ManualAttendanceRequest;
use App\Http\Resources\AttendanceCorrectionResource;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $service) {}

    /** Fichaje por PIN (kiosk/web/móvil). No requiere usuario autenticado. */
    public function clock(ClockRequest $request): JsonResponse
    {
        $attendance = $this->service->clock(
            $request->validated('clock_code'),
            $request->validated('milestone_id'),
            $request->validated('lat'),
            $request->validated('lng'),
            $request->ip(),
            $request->validated('method') ?? 'kiosk',
            $request->validated('work_mode'),
        );

        return AttendanceResource::make($attendance->load(['employee', 'milestone']))
            ->response()
            ->setStatusCode(201);
    }

    /** Identifica al empleado por su código de fichaje (kiosk: muestra el nombre antes de fichar). */
    public function identify(Request $request): JsonResponse
    {
        $request->validate(['clock_code' => ['required', 'string']]);

        $employee = Employee::query()
            ->where('clock_code', $request->string('clock_code'))
            ->where('active', true)
            ->with('workCenter:id,location_required')
            ->first();

        if ($employee === null) {
            throw new BusinessException('Código de fichaje no encontrado.', 'INVALID_CLOCK_CODE', 422);
        }

        return response()->json(['data' => [
            'name' => $employee->fullName(),
            'location_required' => (bool) $employee->workCenter?->location_required,
        ]]);
    }

    /** Informe diario de fichajes por empresa/centro. */
    public function daily(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'company_id' => ['nullable', 'uuid'],
            'work_center_id' => ['nullable', 'uuid'],
        ]);

        $attendances = Attendance::query()
            ->whereDate('clocked_at', $validated['date'])
            ->when(isset($validated['company_id']), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('company_id', $validated['company_id'])))
            ->when(isset($validated['work_center_id']), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('work_center_id', $validated['work_center_id'])))
            ->with(['employee', 'milestone'])
            ->orderBy('clocked_at')
            ->paginate();

        return AttendanceResource::collection($attendances);
    }

    public function manual(ManualAttendanceRequest $request): JsonResponse
    {
        $attendance = $this->service->manual(
            $request->validated('employee_id'),
            $request->validated('milestone_id'),
            $request->validated('clocked_at'),
        );

        return AttendanceResource::make($attendance->load(['employee', 'milestone']))
            ->response()
            ->setStatusCode(201);
    }

    public function correct(CorrectAttendanceRequest $request, Attendance $attendance): AttendanceResource
    {
        $attendance = $this->service->correct(
            $attendance,
            $request->validated('new_clocked_at'),
            $request->validated('reason'),
            $request->user()?->id,
        );

        return AttendanceResource::make($attendance->load(['employee', 'milestone']));
    }

    public function destroy(DeleteAttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        $this->service->delete($attendance, $request->validated('reason'), $request->user()?->id);

        return response()->json(['message' => 'Fichaje eliminado con auditoría.']);
    }

    public function corrections(Attendance $attendance): AnonymousResourceCollection
    {
        return AttendanceCorrectionResource::collection(
            $attendance->corrections()->orderBy('created_at')->get()
        );
    }
}
