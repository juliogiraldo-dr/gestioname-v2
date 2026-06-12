<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Agreement\StoreAgreementRequest;
use App\Http\Requests\Agreement\UpdateAgreementRequest;
use App\Http\Resources\AgreementResource;
use App\Models\Agreement;
use App\Services\AgreementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgreementController extends Controller
{
    public function __construct(private readonly AgreementService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $agreements = Agreement::query()
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')))
            ->orderBy('name')
            ->paginate();

        return AgreementResource::collection($agreements);
    }

    public function store(StoreAgreementRequest $request): JsonResponse
    {
        $agreement = $this->service->create($request->validated());

        return AgreementResource::make($agreement)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Agreement $agreement): AgreementResource
    {
        return AgreementResource::make($agreement->load('leaveTypes'));
    }

    public function update(UpdateAgreementRequest $request, Agreement $agreement): AgreementResource
    {
        return AgreementResource::make($this->service->update($agreement, $request->validated()));
    }

    public function destroy(Agreement $agreement): JsonResponse
    {
        $this->service->delete($agreement);

        return response()->json(['message' => 'Convenio eliminado correctamente.']);
    }
}
