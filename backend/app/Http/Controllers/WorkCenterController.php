<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WorkCenter\StoreWorkCenterRequest;
use App\Http\Requests\WorkCenter\UpdateWorkCenterRequest;
use App\Http\Resources\WorkCenterResource;
use App\Models\Company;
use App\Models\WorkCenter;
use App\Services\WorkCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkCenterController extends Controller
{
    public function __construct(private readonly WorkCenterService $service) {}

    public function index(Company $company): AnonymousResourceCollection
    {
        return WorkCenterResource::collection(
            $company->workCenters()->with(['milestones', 'agreements'])->orderBy('name')->paginate()
        );
    }

    public function store(StoreWorkCenterRequest $request, Company $company): JsonResponse
    {
        $workCenter = $this->service->create($company, $request->validated());

        return WorkCenterResource::make($workCenter)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateWorkCenterRequest $request, WorkCenter $workCenter): WorkCenterResource
    {
        return WorkCenterResource::make($this->service->update($workCenter, $request->validated()));
    }

    public function destroy(WorkCenter $workCenter): JsonResponse
    {
        $this->service->delete($workCenter);

        return response()->json(['message' => 'Centro de trabajo eliminado correctamente.']);
    }
}
