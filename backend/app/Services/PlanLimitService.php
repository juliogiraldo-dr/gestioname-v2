<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Entity;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Resuelve los límites y módulos efectivos de un tenant: plan base + override individual.
 * Cachea la resolución (no los recuentos) 5 minutos.
 */
final class PlanLimitService
{
    private const TTL = 300; // 5 min

    /** Recurso → modelo para contar el uso actual dentro del schema del tenant. */
    private const COUNTERS = [
        'companies' => Company::class,
        'employees' => Employee::class,
        'entities' => Entity::class,
        'members' => Member::class,
        'users' => User::class,
    ];

    /**
     * Límites efectivos. Un valor null = ilimitado.
     *
     * @return array<string, int|null>
     */
    public function limits(Tenant $tenant): array
    {
        return $this->resolve($tenant)['limits'];
    }

    /**
     * Módulos permitidos por el plan (+ override).
     *
     * @return list<string>
     */
    public function modules(Tenant $tenant): array
    {
        return $this->resolve($tenant)['modules'];
    }

    /**
     * @return array{limits: array<string, int|null>, modules: list<string>}
     */
    public function resolve(Tenant $tenant): array
    {
        return Cache::remember("plan_limits:{$tenant->id}", self::TTL, function () use ($tenant): array {
            $plan = $tenant->subscriptionPlan ?? Plan::query()->where('slug', 'free')->first();
            $base = $plan?->limits ?? [];
            $modules = $plan?->modules_allowed ?? [];

            $override = $tenant->override;
            if ($override?->limits) {
                foreach ($override->limits as $key => $value) {
                    if ($value !== null) {
                        $base[$key] = $value;
                    }
                }
            }
            if ($override?->modules_allowed) {
                $modules = $override->modules_allowed;
            }

            return ['limits' => $base, 'modules' => array_values($modules)];
        });
    }

    /** Invalida la caché de un tenant (al cambiar plan u override). */
    public function flush(Tenant $tenant): void
    {
        Cache::forget("plan_limits:{$tenant->id}");
    }

    /** Uso actual de un recurso dentro del schema del tenant activo. */
    public function usage(string $resource): int
    {
        $model = self::COUNTERS[$resource] ?? null;

        return $model ? $model::query()->count() : 0;
    }

    /**
     * ¿Se puede crear un recurso más sin superar el límite? Debe llamarse con el schema
     * del tenant activo (para que el recuento sea correcto).
     *
     * @return array{allowed: bool, limit: int|null, current: int}
     */
    public function check(Tenant $tenant, string $resource): array
    {
        $limit = $this->limits($tenant)[$resource] ?? null;

        if ($limit === null) {
            return ['allowed' => true, 'limit' => null, 'current' => 0];
        }

        $current = $this->usage($resource);

        return ['allowed' => $current < $limit, 'limit' => $limit, 'current' => $current];
    }
}
