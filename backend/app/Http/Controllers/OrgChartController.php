<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\OrgChart\StoreOrgChartNodeRequest;
use App\Http\Requests\OrgChart\UpdateOrgChartNodeRequest;
use App\Http\Resources\OrgChartNodeResource;
use App\Models\OrgChartNode;
use App\Models\WorkCenter;
use App\Services\OrgChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrgChartController extends Controller
{
    public function __construct(private readonly OrgChartService $service) {}

    public function show(WorkCenter $workCenter): AnonymousResourceCollection
    {
        return OrgChartNodeResource::collection($this->service->tree($workCenter));
    }

    public function store(StoreOrgChartNodeRequest $request): JsonResponse
    {
        $node = $this->service->create($request->validated());

        return OrgChartNodeResource::make($node->load('employee'))->response()->setStatusCode(201);
    }

    public function update(UpdateOrgChartNodeRequest $request, OrgChartNode $node): OrgChartNodeResource
    {
        return OrgChartNodeResource::make($this->service->update($node, $request->validated())->load('employee'));
    }

    public function destroy(OrgChartNode $node): JsonResponse
    {
        $this->service->delete($node);

        return response()->json(['message' => 'Nodo eliminado.']);
    }

    public function notifications(Request $request, OrgChartNode $node): OrgChartNodeResource
    {
        $value = $request->validate(['receives_notifications' => ['required', 'boolean']]);

        return OrgChartNodeResource::make($this->service->setNotifications($node, (bool) $value['receives_notifications']));
    }
}
