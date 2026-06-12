<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use Tests\TenantTestCase;

class MilestoneApiTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
    }

    public function test_crea_un_hito(): void
    {
        $response = $this->postJson($this->url('/milestones'), [
            'company_id' => $this->company->id,
            'name' => 'Salida a comer',
            'color' => '#ffcc00',
            'type' => 'salida',
        ])->assertCreated()->assertJsonPath('data.name', 'Salida a comer');

        $this->assertDatabaseHas('attendance_milestones', [
            'id' => $response->json('data.id'),
            'company_id' => $this->company->id,
            'type' => 'salida',
        ]);
    }

    public function test_asigna_centros_del_mismo_company(): void
    {
        $center = $this->company->workCenters()->create(['name' => 'Sede Madrid']);

        $response = $this->postJson($this->url('/milestones'), [
            'company_id' => $this->company->id,
            'name' => 'Entrada turno',
            'color' => '#90cbe8',
            'type' => 'entrada',
            'work_center_ids' => [$center->id],
        ])->assertCreated();

        $this->assertDatabaseHas('milestone_work_centers', [
            'milestone_id' => $response->json('data.id'),
            'work_center_id' => $center->id,
        ]);
    }

    public function test_rechaza_centro_de_otra_empresa(): void
    {
        $other = Company::create(['name' => 'Globex', 'cif' => 'B22222222']);
        $otherCenter = $other->workCenters()->create(['name' => 'Sede ajena']);

        $this->postJson($this->url('/milestones'), [
            'company_id' => $this->company->id,
            'name' => 'Entrada',
            'color' => '#90cbe8',
            'type' => 'entrada',
            'work_center_ids' => [$otherCenter->id],
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'WORK_CENTER_COMPANY_MISMATCH');
    }

    public function test_valida_color_y_tipo(): void
    {
        $this->postJson($this->url('/milestones'), [
            'company_id' => $this->company->id,
            'name' => 'Mal',
            'color' => 'rojo',
            'type' => 'pausa',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['color', 'type']);
    }

    public function test_lista_filtrando_por_empresa(): void
    {
        $other = Company::create(['name' => 'Globex', 'cif' => 'B22222222']);
        $this->company->milestones()->create(['name' => 'E', 'color' => '#90cbe8', 'type' => 'entrada']);
        $other->milestones()->create(['name' => 'X', 'color' => '#90cbe8', 'type' => 'entrada']);

        $this->getJson($this->url("/milestones?company_id={$this->company->id}"))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_elimina_un_hito(): void
    {
        $milestone = $this->company->milestones()->create(['name' => 'E', 'color' => '#90cbe8', 'type' => 'entrada']);

        $this->deleteJson($this->url("/milestones/{$milestone->id}"))->assertOk();

        $this->assertDatabaseMissing('attendance_milestones', ['id' => $milestone->id]);
    }
}
