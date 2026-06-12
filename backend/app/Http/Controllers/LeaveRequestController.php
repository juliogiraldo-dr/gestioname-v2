<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Leave\ReviewLeaveRequest;
use App\Http\Requests\Leave\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveRequestController extends Controller
{
    public function __construct(private readonly LeaveRequestService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = LeaveRequest::query()
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->string('employee_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->where('date_start', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->where('date_end', '<=', $request->string('date_to')))
            ->with('employee')
            ->orderByDesc('date_start')
            ->paginate();

        return LeaveRequestResource::collection($requests);
    }

    public function pending(): AnonymousResourceCollection
    {
        return LeaveRequestResource::collection(
            LeaveRequest::where('status', 'pendiente')->with('employee')->orderBy('date_start')->get()
        );
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $leaveRequest = $this->service->create($request->validated());

        return LeaveRequestResource::make($leaveRequest)->response()->setStatusCode(201);
    }

    public function show(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        return LeaveRequestResource::make($leaveRequest->load('employee'));
    }

    public function destroy(LeaveRequest $leaveRequest): JsonResponse
    {
        $this->service->cancel($leaveRequest);

        return response()->json(['message' => 'Solicitud cancelada.']);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        return LeaveRequestResource::make($this->service->approve($leaveRequest, $request->user()?->id));
    }

    public function reject(ReviewLeaveRequest $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        return LeaveRequestResource::make(
            $this->service->reject($leaveRequest, $request->user()?->id, $request->validated('note'))
        );
    }

    public function vacations(Request $request, Employee $employee): JsonResponse
    {
        $year = $request->integer('year') ?: (int) now()->year;

        return response()->json(['data' => $this->service->vacationSummary($employee, $year)]);
    }
}
