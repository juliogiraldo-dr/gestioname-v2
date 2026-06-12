<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyController extends Controller
{
    public function __construct(private readonly CompanyService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return CompanyResource::collection(
            Company::query()->with('group')->orderBy('name')->paginate()
        );
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = $this->service->create($request->validated());

        return CompanyResource::make($company->load('milestones'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Company $company): CompanyResource
    {
        return CompanyResource::make($company->load(['group', 'workCenters', 'milestones']));
    }

    public function update(UpdateCompanyRequest $request, Company $company): CompanyResource
    {
        return CompanyResource::make($this->service->update($company, $request->validated()));
    }

    public function destroy(Company $company): JsonResponse
    {
        $this->service->delete($company);

        return response()->json(['message' => 'Empresa eliminada correctamente.']);
    }
}
