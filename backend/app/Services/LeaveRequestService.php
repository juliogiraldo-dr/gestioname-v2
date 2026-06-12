<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\AgreementLeaveType;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestReviewedNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Flujo de solicitudes de ausencia/presencia y cálculo de vacaciones.
 */
final class LeaveRequestService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LeaveRequest
    {
        $leaveType = AgreementLeaveType::findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['date_start']);
        $end = Carbon::parse($data['date_end']);

        $this->assertNoOverlap($data['employee_id'], $start, $end);

        if ($leaveType->count_in === 'dias') {
            $data['total_days'] = $start->diffInDays($end) + 1;
        } elseif (! empty($data['time_start']) && ! empty($data['time_end'])) {
            $data['total_hours'] = round(
                Carbon::parse($data['time_start'])->diffInMinutes(Carbon::parse($data['time_end'])) / 60,
                2,
            );
        }

        $data['status'] = 'pendiente';
        $leaveRequest = LeaveRequest::create($data);

        $this->notifyReviewers($leaveRequest);

        return $leaveRequest;
    }

    public function approve(LeaveRequest $leaveRequest, ?string $userId): LeaveRequest
    {
        return $this->resolve($leaveRequest, 'aprobada', $userId, null);
    }

    public function reject(LeaveRequest $leaveRequest, ?string $userId, ?string $note): LeaveRequest
    {
        return $this->resolve($leaveRequest, 'rechazada', $userId, $note);
    }

    /**
     * Cancela (elimina) una solicitud pendiente del propio empleado.
     *
     * @throws BusinessException si ya no está pendiente.
     */
    public function cancel(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status !== 'pendiente') {
            throw new BusinessException('Solo se pueden cancelar solicitudes pendientes.', 'LEAVE_NOT_PENDING', 409);
        }

        $leaveRequest->delete();
    }

    /**
     * Resumen de vacaciones del año: disponibles (convenio), solicitadas, aprobadas y restantes.
     *
     * @return array{year: int, available: int, requested: float, approved: float, remaining: float}
     */
    public function vacationSummary(Employee $employee, int $year): array
    {
        $employee->loadMissing('agreement');
        $available = (int) ($employee->agreement->vacation_days ?? 0);

        $base = LeaveRequest::where('employee_id', $employee->id)
            ->whereHas('leaveType', fn ($q) => $q->where('subtracts_vacation', true))
            ->whereYear('date_start', $year);

        $requested = (float) (clone $base)->where('status', 'pendiente')->sum('total_days');
        $approved = (float) (clone $base)->where('status', 'aprobada')->sum('total_days');

        return [
            'year' => $year,
            'available' => $available,
            'requested' => $requested,
            'approved' => $approved,
            'remaining' => $available - $approved,
        ];
    }

    private function resolve(LeaveRequest $leaveRequest, string $status, ?string $userId, ?string $note): LeaveRequest
    {
        if ($leaveRequest->status !== 'pendiente') {
            throw new BusinessException('La solicitud ya ha sido resuelta.', 'LEAVE_NOT_PENDING', 409);
        }

        $leaveRequest->update([
            'status' => $status,
            'reviewed_by' => $userId,
            'reviewed_at' => Carbon::now(),
            'review_note' => $note,
        ]);

        $user = $leaveRequest->employee?->user;
        if ($user !== null) {
            $user->notify(new LeaveRequestReviewedNotification($leaveRequest));
        }

        return $leaveRequest;
    }

    private function assertNoOverlap(string $employeeId, Carbon $start, Carbon $end): void
    {
        $overlap = LeaveRequest::where('employee_id', $employeeId)
            ->whereIn('status', ['pendiente', 'aprobada'])
            ->where('date_start', '<=', $end->toDateString())
            ->where('date_end', '>=', $start->toDateString())
            ->exists();

        if ($overlap) {
            throw new BusinessException('La ausencia se solapa con otra existente.', 'LEAVE_OVERLAP', 409);
        }
    }

    private function notifyReviewers(LeaveRequest $leaveRequest): void
    {
        $reviewers = User::role(['admin', 'rrhh-coordinator'])->get();

        if ($reviewers->isNotEmpty()) {
            Notification::send($reviewers, new LeaveRequestSubmittedNotification($leaveRequest));
        }
    }
}
