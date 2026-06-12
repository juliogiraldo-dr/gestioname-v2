<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica el límite del plan antes de crear un recurso. Uso: `plan.limit:employees`.
 * Si se alcanza el límite responde 402 con código PLAN_LIMIT_REACHED.
 */
class EnforcePlanLimit
{
    public function __construct(private readonly PlanLimitService $service) {}

    public function handle(Request $request, Closure $next, string $resource): Response
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');
        $check = $this->service->check($tenant, $resource);

        if (! $check['allowed']) {
            return response()->json([
                'message' => 'Has alcanzado el límite de tu plan para este recurso.',
                'code' => 'PLAN_LIMIT_REACHED',
                'resource' => $resource,
                'limit' => $check['limit'],
                'current' => $check['current'],
                'plan' => $tenant->subscriptionPlan?->slug ?? 'free',
            ], 402);
        }

        return $next($request);
    }
}
