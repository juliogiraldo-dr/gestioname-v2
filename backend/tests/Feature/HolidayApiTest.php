<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Holiday;
use Tests\TenantTestCase;

class HolidayApiTest extends TenantTestCase
{
    public function test_crea_festivo_con_fecha(): void
    {
        $response = $this->postJson($this->url('/holidays'), [
            'name' => 'Fiesta local',
            'type' => 'local',
            'repeatable' => false,
            'date' => '2026-09-15',
            'locality' => 'Majadahonda',
        ])->assertCreated()->assertJsonPath('data.date', '2026-09-15');

        $this->assertDatabaseHas('holidays', [
            'id' => $response->json('data.id'),
            'type' => 'local',
            'repeatable' => false,
        ]);
    }

    public function test_crea_festivo_repetible(): void
    {
        $response = $this->postJson($this->url('/holidays'), [
            'name' => 'Año Nuevo',
            'type' => 'nacional',
            'repeatable' => true,
            'day_of_year' => 1,
        ])->assertCreated()
            ->assertJsonPath('data.repeatable', true)
            ->assertJsonPath('data.day_of_year', 1)
            ->assertJsonPath('data.date', null);

        $this->assertDatabaseHas('holidays', ['id' => $response->json('data.id'), 'day_of_year' => 1]);
    }

    public function test_repetible_exige_day_of_year(): void
    {
        $this->postJson($this->url('/holidays'), [
            'name' => 'Mal',
            'type' => 'nacional',
            'repeatable' => true,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('day_of_year');
    }

    public function test_no_repetible_exige_fecha(): void
    {
        $this->postJson($this->url('/holidays'), [
            'name' => 'Mal',
            'type' => 'local',
            'repeatable' => false,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }

    public function test_lista_filtra_por_anio_y_tipo(): void
    {
        Holiday::create(['name' => 'Fijo 2025', 'type' => 'local', 'repeatable' => false, 'date' => '2025-05-10']);
        Holiday::create(['name' => 'Fijo 2026', 'type' => 'local', 'repeatable' => false, 'date' => '2026-05-10']);
        Holiday::create(['name' => 'Repetible', 'type' => 'nacional', 'repeatable' => true, 'day_of_year' => 1]);

        // Año 2026: incluye el de 2026 y todos los repetibles (no el de 2025).
        $this->getJson($this->url('/holidays?year=2026'))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // Filtro por tipo.
        $this->getJson($this->url('/holidays?type=nacional'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_asigna_centros_de_trabajo(): void
    {
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $center = $company->workCenters()->create(['name' => 'Sede Madrid']);

        $response = $this->postJson($this->url('/holidays'), [
            'name' => 'Fiesta local',
            'type' => 'local',
            'repeatable' => false,
            'date' => '2026-09-15',
            'work_center_ids' => [$center->id],
        ])->assertCreated();

        $this->assertDatabaseHas('holiday_work_centers', [
            'holiday_id' => $response->json('data.id'),
            'work_center_id' => $center->id,
        ]);
    }

    public function test_actualiza_y_elimina(): void
    {
        $holiday = Holiday::create(['name' => 'Fiesta', 'type' => 'local', 'repeatable' => false, 'date' => '2026-09-15']);

        $this->putJson($this->url("/holidays/{$holiday->id}"), ['name' => 'Fiesta Mayor'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Fiesta Mayor');

        $this->deleteJson($this->url("/holidays/{$holiday->id}"))->assertOk();
        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }
}
