<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Tenant;
use Tests\TenantTestCase;

class PlanLimitTest extends TenantTestCase
{
    /**
     * @param  array<string, int|null>  $limits
     */
    private function assignPlan(array $limits): void
    {
        $plan = Plan::create([
            'name' => 'Test', 'slug' => 'test', 'price_monthly' => 0, 'is_public' => true,
            'limits' => $limits, 'modules_allowed' => ['rrhh', 'socios', 'tesoreria'],
        ]);
        Tenant::query()->where('subdomain', 'demo')->update(['plan_id' => $plan->id]);
    }

    public function test_bloquea_creacion_al_alcanzar_limite(): void
    {
        $this->assignPlan(['employees' => 1]);
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);

        $this->postJson($this->url('/employees'), ['company_id' => $company->id, 'first_name' => 'Ana', 'last_name' => 'Uno'])
            ->assertCreated();

        $this->postJson($this->url('/employees'), ['company_id' => $company->id, 'first_name' => 'Beto', 'last_name' => 'Dos'])
            ->assertStatus(402)
            ->assertJsonPath('code', 'PLAN_LIMIT_REACHED')
            ->assertJsonPath('resource', 'employees')
            ->assertJsonPath('limit', 1);
    }

    public function test_limite_nulo_es_ilimitado(): void
    {
        $this->assignPlan(['employees' => null]);
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);

        foreach (['Ana', 'Beto', 'Cris'] as $name) {
            $this->postJson($this->url('/employees'), ['company_id' => $company->id, 'first_name' => $name, 'last_name' => 'X'])
                ->assertCreated();
        }
    }

    public function test_override_amplia_el_limite_del_plan(): void
    {
        $this->assignPlan(['companies' => 1]);
        $tenant = Tenant::query()->where('subdomain', 'demo')->first();
        $tenant->override()->create(['limits' => ['companies' => 5], 'modules_allowed' => null]);

        // Con override de 5 empresas, la segunda empresa se permite.
        Company::create(['name' => 'Una', 'cif' => 'B11111111']);
        $this->postJson($this->url('/companies'), ['name' => 'Dos', 'cif' => 'B22222222'])->assertCreated();
    }
}
