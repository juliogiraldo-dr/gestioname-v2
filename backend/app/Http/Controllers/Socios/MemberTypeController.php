<?php

declare(strict_types=1);

namespace App\Http\Controllers\Socios;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberType\StoreMemberTypeRequest;
use App\Http\Requests\MemberType\UpdateMemberTypeRequest;
use App\Http\Resources\MemberTypeResource;
use App\Models\Entity;
use App\Models\MemberType;
use App\Services\Socios\MemberTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberTypeController extends Controller
{
    public function __construct(private readonly MemberTypeService $service) {}

    public function index(Entity $entity): AnonymousResourceCollection
    {
        return MemberTypeResource::collection($entity->memberTypes()->orderBy('name')->get());
    }

    public function store(StoreMemberTypeRequest $request, Entity $entity): JsonResponse
    {
        $type = $this->service->create($entity, $request->validated());

        return MemberTypeResource::make($type)->response()->setStatusCode(201);
    }

    public function update(UpdateMemberTypeRequest $request, MemberType $memberType): MemberTypeResource
    {
        return MemberTypeResource::make($this->service->update($memberType, $request->validated()));
    }

    public function destroy(MemberType $memberType): JsonResponse
    {
        $this->service->delete($memberType);

        return response()->json(['message' => 'Tipo de socio eliminado correctamente.']);
    }
}
