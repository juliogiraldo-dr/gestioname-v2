<?php

declare(strict_types=1);

namespace App\Http\Controllers\Socios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entity\StoreEntityRequest;
use App\Http\Requests\Entity\UpdateEntityRequest;
use App\Http\Resources\EntityResource;
use App\Models\Entity;
use App\Services\Socios\EntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EntityController extends Controller
{
    public function __construct(private readonly EntityService $service) {}

    public function index(): AnonymousResourceCollection
    {
        $entities = Entity::query()
            ->withCount('members')
            ->orderBy('name')
            ->paginate();

        return EntityResource::collection($entities);
    }

    public function store(StoreEntityRequest $request): JsonResponse
    {
        $entity = $this->service->create($request->validated());

        return EntityResource::make($entity)->response()->setStatusCode(201);
    }

    public function show(Entity $entity): EntityResource
    {
        return EntityResource::make($entity->load('memberTypes')->loadCount('members'));
    }

    public function update(UpdateEntityRequest $request, Entity $entity): EntityResource
    {
        return EntityResource::make($this->service->update($entity, $request->validated()));
    }

    public function destroy(Entity $entity): JsonResponse
    {
        $this->service->delete($entity);

        return response()->json(['message' => 'Entidad eliminada correctamente.']);
    }
}
