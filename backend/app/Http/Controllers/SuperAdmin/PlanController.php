<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function __construct(private readonly PlanLimitService $limits) {}

    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection(Plan::query()->orderBy('price_monthly')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $plan = Plan::create($this->validateData($request));

        return PlanResource::make($plan)->response()->setStatusCode(201);
    }

    public function update(Request $request, Plan $plan): PlanResource
    {
        $plan->update($this->validateData($request, $plan->id));

        // Invalida la caché de límites de los tenants de este plan.
        Tenant::query()->where('plan_id', $plan->id)->each(fn (Tenant $t) => $this->limits->flush($t));

        return PlanResource::make($plan);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $plan->delete();

        return response()->json(['message' => 'Plan eliminado.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('plans', 'slug')->ignore($ignoreId)],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'is_public' => ['boolean'],
            'limits' => ['required', 'array'],
            'modules_allowed' => ['required', 'array'],
            'modules_allowed.*' => ['string'],
        ]);
    }
}
