<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use Tests\TenantTestCase;

class WorkCenterApiTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
    }

    public function test_lista_centros_de_la_empresa(): void
    {
        $this->company->workCenters()->create(['name' => 'Sede Madrid']);
        $this->company->workCenters()->create(['name' => 'Sede Sevilla']);

        $this->getJson($this->url("/companies/{$this->company->id}/work-centers"))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_crea_un_centro(): void
    {
        $response = $this->postJson($this->url("/companies/{$this->company->id}/work-centers"), [
            'name' => 'Sede Madrid',
            'address' => 'Calle Mayor 1',
            'lat' => 40.4168,
            'lng' => -3.7038,
            'timezone' => 'Europe/Madrid',
        ])->assertCreated()->assertJsonPath('data.name', 'Sede Madrid');

        $this->assertDatabaseHas('work_centers', [
            'id' => $response->json('data.id'),
            'company_id' => $this->company->id,
            'name' => 'Sede Madrid',
        ]);
    }

    public function test_valida_coordenadas_y_timezone(): void
    {
        $this->postJson($this->url("/companies/{$this->company->id}/work-centers"), [
            'name' => 'Mal',
            'lat' => 200,
            'lng' => -3.7,
            'timezone' => 'Marte/Olympus',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'timezone']);
    }

    public function test_actualiza_un_centro(): void
    {
        $center = $this->company->workCenters()->create(['name' => 'Sede Madrid']);

        $this->putJson($this->url("/work-centers/{$center->id}"), ['name' => 'Sede Central'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Sede Central');
    }

    public function test_elimina_un_centro(): void
    {
        $center = $this->company->workCenters()->create(['name' => 'Sede Madrid']);

        $this->deleteJson($this->url("/work-centers/{$center->id}"))->assertOk();

        $this->assertDatabaseMissing('work_centers', ['id' => $center->id]);
    }

    public function test_borrar_empresa_elimina_sus_centros_en_cascada(): void
    {
        $center = $this->company->workCenters()->create(['name' => 'Sede Madrid']);

        $this->deleteJson($this->url("/companies/{$this->company->id}"))->assertOk();

        $this->assertDatabaseMissing('work_centers', ['id' => $center->id]);
    }
}
