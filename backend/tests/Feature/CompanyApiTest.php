<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class CompanyApiTest extends TenantTestCase
{
    public function test_lista_empresas(): void
    {
        Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        Company::create(['name' => 'Globex', 'cif' => 'B22222222']);

        $this->getJson($this->url('/companies'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'cif']], 'meta' => ['current_page', 'total']]);
    }

    public function test_crea_empresa_con_hitos_por_defecto(): void
    {
        $response = $this->postJson($this->url('/companies'), [
            'name' => 'Acme',
            'cif' => 'B11111111',
            'email' => 'info@acme.test',
        ])->assertCreated()->assertJsonPath('data.name', 'Acme');

        $id = $response->json('data.id');
        $this->assertDatabaseHas('companies', ['id' => $id, 'cif' => 'B11111111']);

        // ENTRADA + SALIDA creados automáticamente.
        $this->assertDatabaseCount('attendance_milestones', 2);
        $this->assertDatabaseHas('attendance_milestones', ['company_id' => $id, 'name' => 'ENTRADA', 'type' => 'entrada']);
        $this->assertDatabaseHas('attendance_milestones', ['company_id' => $id, 'name' => 'SALIDA', 'type' => 'salida']);
    }

    public function test_muestra_una_empresa(): void
    {
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);

        $this->getJson($this->url("/companies/{$company->id}"))
            ->assertOk()
            ->assertJsonPath('data.id', $company->id)
            ->assertJsonPath('data.cif', 'B11111111');
    }

    public function test_actualiza_una_empresa(): void
    {
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);

        $this->putJson($this->url("/companies/{$company->id}"), ['name' => 'Acme Corp'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Acme Corp');

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Acme Corp']);
    }

    public function test_elimina_una_empresa(): void
    {
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);

        $this->deleteJson($this->url("/companies/{$company->id}"))->assertOk();

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_cif_debe_ser_unico(): void
    {
        Company::create(['name' => 'Acme', 'cif' => 'B11111111']);

        $this->postJson($this->url('/companies'), ['name' => 'Otra', 'cif' => 'B11111111'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cif');
    }

    public function test_requiere_rol_admin(): void
    {
        Sanctum::actingAs($this->makeUser('employee'));

        $this->getJson($this->url('/companies'))->assertForbidden();
    }
}
