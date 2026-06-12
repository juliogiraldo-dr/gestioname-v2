<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LeaveType\StoreLeaveTypeRequest;
use App\Http\Requests\LeaveType\UpdateLeaveTypeRequest;
use App\Http\Resources\AgreementLeaveTypeResource;
use App\Models\Agreement;
use App\Models\AgreementLeaveType;
use App\Services\AgreementLeaveTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgreementLeaveTypeController extends Controller
{
    public function __construct(private readonly AgreementLeaveTypeService $service) {}

    public function index(Agreement $agreement): AnonymousResourceCollection
    {
        return AgreementLeaveTypeResource::collection(
            $agreement->leaveTypes()->orderBy('name')->get()
        );
    }

    public function store(StoreLeaveTypeRequest $request, Agreement $agreement): JsonResponse
    {
        $leaveType = $this->service->create($agreement, $request->validated());

        return AgreementLeaveTypeResource::make($leaveType)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateLeaveTypeRequest $request, AgreementLeaveType $leaveType): AgreementLeaveTypeResource
    {
        return AgreementLeaveTypeResource::make($this->service->update($leaveType, $request->validated()));
    }

    public function destroy(AgreementLeaveType $leaveType): JsonResponse
    {
        $this->service->delete($leaveType);

        return response()->json(['message' => 'Tipo de ausencia/presencia eliminado correctamente.']);
    }
}
