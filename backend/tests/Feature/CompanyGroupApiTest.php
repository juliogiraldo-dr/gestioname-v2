<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyGroup;
use Tests\TenantTestCase;

class CompanyGroupApiTest extends TenantTestCase
{
    public function test_crea_un_grupo(): void
    {
        $this->postJson($this->url('/company-groups'), ['name' => 'Grupo Datarecover'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Grupo Datarecover');
    }

    public function test_lista_grupos_con_conteo_de_empresas(): void
    {
        $group = CompanyGroup::create(['name' => 'Grupo Datarecover']);
        Company::create(['company_group_id' => $group->id, 'name' => 'Datarecover S.L.', 'cif' => 'B1']);
        Company::create(['company_group_id' => $group->id, 'name' => 'Datarecover Cloud S.L.', 'cif' => 'B2']);

        $this->getJson($this->url('/company-groups'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.companies_count', 2);
    }

    public function test_asigna_empresa_a_grupo_y_se_expone_en_el_listado(): void
    {
        $group = CompanyGroup::create(['name' => 'Grupo Datarecover']);
        $company = Company::create(['name' => 'Datarecover S.L.', 'cif' => 'B1']);

        $this->putJson($this->url("/companies/{$company->id}"), ['company_group_id' => $group->id])
            ->assertOk()
            ->assertJsonPath('data.company_group_id', $group->id);

        $this->getJson($this->url('/companies'))
            ->assertOk()
            ->assertJsonPath('data.0.group.name', 'Grupo Datarecover');
    }

    public function test_borrar_grupo_desvincula_sus_empresas(): void
    {
        $group = CompanyGroup::create(['name' => 'Grupo Datarecover']);
        $company = Company::create(['company_group_id' => $group->id, 'name' => 'Datarecover S.L.', 'cif' => 'B1']);

        $this->deleteJson($this->url("/company-groups/{$group->id}"))->assertOk();

        $this->assertDatabaseMissing('company_groups', ['id' => $group->id]);
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'company_group_id' => null]);
    }
}
