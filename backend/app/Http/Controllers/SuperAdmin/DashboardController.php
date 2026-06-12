<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Member;
use App\Models\Tenant;
use App\Support\TenantSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /** KPIs globales del operador (cacheados 60 s; los conteos por schema son costosos). */
    public function index(): JsonResponse
    {
        $data = Cache::remember('superadmin:dashboard', 60, fn () => $this->compute());

        return response()->json(['data' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function compute(): array
    {
        $tenants = Tenant::query()->with('subscriptionPlan')->get();

        $active = $tenants->where('status', 'active');
        $mrr = $active->sum(fn (Tenant $t) => (float) ($t->subscriptionPlan?->price_monthly ?? 0));

        $employees = 0;
        $members = 0;
        foreach ($tenants as $tenant) {
            try {
                TenantSchema::use($tenant->subdomain);
                $employees += Employee::query()->count();
                $members += Member::query()->count();
            } catch (\Throwable $e) {
                // Un schema ausente o roto no debe tumbar el dashboard global.
                Log::warning("Dashboard: no se pudieron contar datos del tenant {$tenant->subdomain}: {$e->getMessage()}");
            } finally {
                TenantSchema::usePublic();
            }
        }

        return [
            'tenants_total' => $tenants->count(),
            'tenants_active' => $active->count(),
            'tenants_trial' => $tenants->where('status', 'active')->filter(fn (Tenant $t) => $t->trial_ends_at !== null && $t->trial_ends_at->isFuture())->count(),
            'tenants_suspended' => $tenants->where('status', 'suspended')->count(),
            'mrr' => round($mrr, 2),
            'employees_total' => $employees,
            'members_total' => $members,
            'now' => Carbon::now()->toIso8601String(),
        ];
    }
}
