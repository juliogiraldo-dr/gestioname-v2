<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use Database\Seeders\PlanSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class SuperAdminApiTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new PlanSeeder)->run();
        Sanctum::actingAs($this->makeUser('super-admin'));
    }

    public function test_lista_los_planes(): void
    {
        $this->getJson($this->url('/superadmin/plans'))
            ->assertOk()
            ->assertJsonCount(4, 'data');
    }

    public function test_crea_y_edita_un_plan(): void
    {
        $id = $this->postJson($this->url('/superadmin/plans'), [
            'name' => 'Custom', 'slug' => 'custom', 'price_monthly' => 49.9,
            'limits' => ['employees' => 200], 'modules_allowed' => ['rrhh'],
        ])->assertCreated()->json('data.id');

        $this->putJson($this->url("/superadmin/plans/{$id}"), [
            'name' => 'Custom Plus', 'slug' => 'custom', 'price_monthly' => 59.9,
            'limits' => ['employees' => 300], 'modules_allowed' => ['rrhh', 'socios'],
        ])->assertOk()->assertJsonPath('data.name', 'Custom Plus');
    }

    public function test_dashboard_devuelve_kpis(): void
    {
        $this->getJson($this->url('/superadmin/dashboard'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['tenants_total', 'tenants_active', 'mrr', 'employees_total', 'members_total']]);
    }

    public function test_personaliza_el_override_de_un_tenant(): void
    {
        $tenant = Tenant::query()->where('subdomain', 'demo')->first();

        $this->putJson($this->url("/superadmin/tenants/{$tenant->id}/override"), [
            'limits' => ['employees' => 999],
        ])->assertOk();

        $this->assertDatabaseHas('plan_overrides', ['tenant_id' => $tenant->id]);
    }

    public function test_cambia_el_plan_de_un_tenant(): void
    {
        $tenant = Tenant::query()->where('subdomain', 'demo')->first();
        $business = Plan::query()->where('slug', 'business')->first();

        $this->putJson($this->url("/superadmin/tenants/{$tenant->id}"), ['plan_id' => $business->id])
            ->assertOk()
            ->assertJsonPath('data.plan.slug', 'business');
    }

    public function test_requiere_rol_super_admin(): void
    {
        Sanctum::actingAs($this->admin); // rol admin, no super-admin

        $this->getJson($this->url('/superadmin/plans'))->assertForbidden();
    }

    public function test_lista_usuarios_del_tenant(): void
    {
        $tenant = Tenant::query()->where('subdomain', 'demo')->first();

        $this->getJson($this->url("/superadmin/tenants/{$tenant->id}/users"))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'roles', 'active', 'last_login_at']]]);
    }

    public function test_reset_password_devuelve_magic_link(): void
    {
        $tenant = Tenant::query()->where('subdomain', 'demo')->first();

        $this->postJson($this->url("/superadmin/tenants/{$tenant->id}/users/{$this->admin->id}/reset-password"))
            ->assertOk()
            ->assertJsonPath('data.email', $this->admin->email)
            ->assertJsonStructure(['data' => ['magic_link', 'expires_at']]);
    }

    public function test_impersonar_genera_magic_link(): void
    {
        $tenant = Tenant::query()->where('subdomain', 'demo')->first();

        $res = $this->postJson($this->url("/superadmin/tenants/{$tenant->id}/impersonate"))->assertOk();
        $this->assertNotNull($res->json('data.magic_link'));
        $this->assertDatabaseHas('superadmin_audit_log', ['action' => 'impersonate', 'tenant_id' => $tenant->id]);
    }

    public function test_cambia_rol_y_desactiva_usuario(): void
    {
        $tenant = Tenant::query()->where('subdomain', 'demo')->first();

        $this->putJson($this->url("/superadmin/tenants/{$tenant->id}/users/{$this->admin->id}/role"), ['role' => 'rrhh-coordinator'])
            ->assertOk()->assertJsonPath('data.role', 'rrhh-coordinator');

        $this->patchJson($this->url("/superadmin/tenants/{$tenant->id}/users/{$this->admin->id}/active"))
            ->assertOk()->assertJsonPath('data.active', false);
    }

    public function test_toggle_modulo_registra_auditoria(): void
    {
        $tenant = Tenant::query()->where('subdomain', 'demo')->first();

        $this->patchJson($this->url("/superadmin/tenants/{$tenant->id}/modules/nominas"), ['enabled' => true])
            ->assertOk()->assertJsonPath('data.enabled', true);

        $this->getJson($this->url('/superadmin/audit'))
            ->assertOk()
            ->assertJsonPath('data.0.action', 'toggle_module');
    }

    public function test_borra_tenant(): void
    {
        $victim = Tenant::create(['name' => 'Bórrame', 'subdomain' => 'borrame', 'plan' => 'free', 'status' => 'active']);

        $this->deleteJson($this->url("/superadmin/tenants/{$victim->id}"))->assertOk();

        $this->assertDatabaseMissing('tenants', ['id' => $victim->id]);
        $this->assertDatabaseHas('superadmin_audit_log', ['action' => 'delete_tenant']);
    }
}
