<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CompanyGroup\StoreCompanyGroupRequest;
use App\Http\Requests\CompanyGroup\UpdateCompanyGroupRequest;
use App\Http\Resources\CompanyGroupResource;
use App\Models\Company;
use App\Models\CompanyGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyGroupController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CompanyGroupResource::collection(
            CompanyGroup::query()->withCount('companies')->orderBy('name')->get()
        );
    }

    public function store(StoreCompanyGroupRequest $request): JsonResponse
    {
        $group = CompanyGroup::create($request->validated());

        return CompanyGroupResource::make($group)->response()->setStatusCode(201);
    }

    public function update(UpdateCompanyGroupRequest $request, CompanyGroup $companyGroup): CompanyGroupResource
    {
        $companyGroup->update($request->validated());

        return CompanyGroupResource::make($companyGroup);
    }

    public function destroy(CompanyGroup $companyGroup): JsonResponse
    {
        // Las empresas del grupo quedan sin grupo (no se borran).
        Company::query()->where('company_group_id', $companyGroup->id)->update(['company_group_id' => null]);
        $companyGroup->delete();

        return response()->json(['message' => 'Grupo eliminado correctamente.']);
    }
}
