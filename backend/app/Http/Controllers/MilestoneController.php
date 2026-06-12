<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Milestone\StoreMilestoneRequest;
use App\Http\Requests\Milestone\UpdateMilestoneRequest;
use App\Http\Resources\AttendanceMilestoneResource;
use App\Models\AttendanceMilestone;
use App\Services\MilestoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MilestoneController extends Controller
{
    public function __construct(private readonly MilestoneService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $milestones = AttendanceMilestone::query()
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')))
            ->with('workCenters')
            ->orderBy('name')
            ->paginate();

        return AttendanceMilestoneResource::collection($milestones);
    }

    public function store(StoreMilestoneRequest $request): JsonResponse
    {
        $milestone = $this->service->create($request->validated());

        return AttendanceMilestoneResource::make($milestone->load('workCenters'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateMilestoneRequest $request, AttendanceMilestone $milestone): AttendanceMilestoneResource
    {
        $milestone = $this->service->update($milestone, $request->validated());

        return AttendanceMilestoneResource::make($milestone->load('workCenters'));
    }

    public function destroy(AttendanceMilestone $milestone): JsonResponse
    {
        $this->service->delete($milestone);

        return response()->json(['message' => 'Hito eliminado correctamente.']);
    }
}
