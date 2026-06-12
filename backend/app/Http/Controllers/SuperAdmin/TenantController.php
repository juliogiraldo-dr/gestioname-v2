<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Models\Employee;
use App\Models\Member;
use App\Models\PlanOverride;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\PlanLimitService;
use App\Services\SuperAdmin\AuditLogger;
use App\Services\TenantRegistrationService;
use App\Support\TenantSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use RuntimeException;

class TenantController extends Controller
{
    private const RESERVED = ['www', 'admin', 'api', 'app', 'mail', 'staging', 'static', 'assets', 'superadmin'];

    public function __construct(
        private readonly TenantRegistrationService $registration,
        private readonly PlanLimitService $limits,
        private readonly AuthService $auth,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $tenants = Tenant::query()
            ->with('subscriptionPlan')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('plan'), fn ($q) => $q->whereHas('subscriptionPlan', fn ($p) => $p->where('slug', $request->string('plan'))))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('subdomain', 'like', '%'.$request->string('search').'%')))
            ->orderByDesc('created_at')
            ->get();

        // Contadores por tenant (recorriendo su schema). Un schema roto no rompe la lista.
        foreach ($tenants as $tenant) {
            $tenant->employees_count = 0;
            $tenant->members_count = 0;
            try {
                $this->inTenant($tenant, function () use ($tenant): void {
                    $tenant->employees_count = Employee::query()->count();
                    $tenant->members_count = Member::query()->count();
                });
            } catch (\Throwable) {
                // Schema ausente/roto: se dejan los contadores a 0.
            }
        }

        return TenantResource::collection($tenants);
    }

    public function show(Tenant $tenant): TenantResource
    {
        $tenant->load(['subscriptionPlan', 'override']);
        $this->inTenant($tenant, function () use ($tenant): void {
            $tenant->employees_count = Employee::query()->count();
            $tenant->members_count = Member::query()->count();
        });

        return TenantResource::make($tenant);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required', 'string', 'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/',
                Rule::notIn(self::RESERVED), Rule::unique('tenants', 'subdomain'),
            ],
            'admin_email' => ['required', 'email', 'max:255'],
            'plan' => ['nullable', 'string', 'exists:plans,slug'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'type' => ['nullable', 'in:empresa,entidad,ambas'],
        ]);

        try {
            $result = $this->registration->register(
                name: $data['name'], subdomain: $data['subdomain'], adminEmail: $data['admin_email'],
                planSlug: $data['plan'] ?? 'free', trialDays: $data['trial_days'] ?? 30, type: $data['type'] ?? 'ambas',
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'TENANT_PROVISION_FAILED'], 422);
        }

        $this->audit->record('create_tenant', $result['tenant'], ['subdomain' => $data['subdomain'], 'plan' => $data['plan'] ?? 'free']);

        return TenantResource::make($result['tenant']->load('subscriptionPlan'))
            ->additional(['url' => $result['url']])->response()->setStatusCode(201);
    }

    public function update(Request $request, Tenant $tenant): TenantResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,suspended,trial,cancelled'],
            'plan_id' => ['sometimes', 'nullable', 'integer', 'exists:plans,id'],
        ]);

        $tenant->update($data);
        $this->limits->flush($tenant);

        if (array_key_exists('status', $data)) {
            $this->audit->record('change_status', $tenant, ['status' => $data['status']]);
        }
        if (array_key_exists('plan_id', $data)) {
            $this->audit->record('change_plan', $tenant, ['plan_id' => $data['plan_id']]);
        }

        return TenantResource::make($tenant->load(['subscriptionPlan', 'override']));
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->audit->record('delete_tenant', $tenant, ['subdomain' => $tenant->subdomain, 'name' => $tenant->name]);

        TenantSchema::drop($tenant->subdomain);
        $tenant->delete();

        return response()->json(['message' => 'Tenant eliminado.']);
    }

    /** Genera un magic link (5 min) para el primer administrador del tenant. */
    public function impersonate(Tenant $tenant): JsonResponse
    {
        $link = $this->inTenant($tenant, function () use ($tenant) {
            $admin = User::query()->whereHas('roles', fn ($r) => $r->where('name', 'admin'))->first()
                ?? User::query()->first();
            if ($admin === null) {
                return null;
            }

            return ['email' => $admin->email, 'link' => $this->auth->issueMagicLink($admin->email, $tenant->subdomain, 5)];
        });

        abort_if($link === null, 404, 'El tenant no tiene usuarios.');

        $this->audit->record('impersonate', $tenant, ['target_email' => $link['email']]);

        return response()->json(['data' => [
            'email' => $link['email'],
            'magic_link' => $link['link']['url'] ?? null,
            'expires_at' => $link['link']['expires_at'] ?? null,
        ]]);
    }

    public function modules(Tenant $tenant): JsonResponse
    {
        $data = $this->inTenant($tenant, function () {
            TenantModule::syncCatalog();
            $enabled = TenantModule::query()->pluck('enabled', 'key');

            return collect(TenantModule::CATALOG)->map(fn (array $meta, string $key) => [
                'key' => $key, 'label' => $meta['label'], 'enabled' => (bool) ($enabled[$key] ?? $meta['default']),
            ])->values();
        });

        return response()->json(['data' => $data]);
    }

    public function toggleModule(Request $request, Tenant $tenant, string $key): JsonResponse
    {
        abort_unless(array_key_exists($key, TenantModule::CATALOG), 404);
        $enabled = $request->validate(['enabled' => ['required', 'boolean']])['enabled'];

        $this->inTenant($tenant, function () use ($key, $enabled): void {
            $module = TenantModule::query()->firstOrNew(['key' => $key]);
            $module->enabled = $enabled;
            $module->save();
        });

        $this->audit->record('toggle_module', $tenant, ['module' => $key, 'enabled' => $enabled]);

        return response()->json(['data' => ['key' => $key, 'enabled' => $enabled]]);
    }

    public function overrideShow(Tenant $tenant): JsonResponse
    {
        $override = $tenant->override;

        return response()->json(['data' => [
            'limits' => $override?->limits,
            'modules_allowed' => $override?->modules_allowed,
            'effective' => $this->limits->resolve($tenant),
        ]]);
    }

    public function overrideUpdate(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'limits' => ['nullable', 'array'],
            'modules_allowed' => ['nullable', 'array'],
            'modules_allowed.*' => ['string'],
        ]);

        PlanOverride::updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['limits' => $data['limits'] ?? null, 'modules_allowed' => $data['modules_allowed'] ?? null],
        );

        $this->limits->flush($tenant);
        $this->audit->record('update_override', $tenant, $data);

        return $this->overrideShow($tenant->refresh());
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function inTenant(Tenant $tenant, callable $callback): mixed
    {
        TenantSchema::use($tenant->subdomain);
        try {
            return $callback();
        } finally {
            TenantSchema::usePublic();
        }
    }
}
