<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ScheduleTemplate;
use Tests\TenantTestCase;

class ScheduleTemplateApiTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
    }

    public function test_crea_plantilla_fija_con_tramos_y_calcula_horas(): void
    {
        $response = $this->postJson($this->url('/schedule-templates'), [
            'company_id' => $this->company->id,
            'name' => 'Jornada partida',
            'color' => '#5EB8D0',
            'type' => 'fijo',
            'year' => 2026,
            'tolerance_minutes' => 10,
            'time_ranges' => [
                ['time_start' => '09:00', 'time_end' => '13:00'],
                ['time_start' => '14:00', 'time_end' => '18:00'],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.type', 'fijo')
            ->assertJsonPath('data.daily_hours', 8)
            ->assertJsonCount(2, 'data.time_ranges');

        $this->assertDatabaseHas('schedule_templates', ['id' => $response->json('data.id')]);
        $this->assertDatabaseCount('schedule_time_ranges', 2);
    }

    public function test_crea_plantilla_flexible(): void
    {
        $this->postJson($this->url('/schedule-templates'), [
            'company_id' => $this->company->id,
            'name' => 'Flexible mañanas',
            'color' => '#68DFB9',
            'type' => 'flexible',
            'year' => 2026,
            'flex_start_min' => '07:30',
            'flex_start_max' => '09:30',
            'flex_hours_day' => 7.5,
        ])->assertCreated()->assertJsonPath('data.flex_hours_day', 7.5);
    }

    public function test_fijo_requiere_tramos(): void
    {
        $this->postJson($this->url('/schedule-templates'), [
            'company_id' => $this->company->id,
            'name' => 'Sin tramos',
            'color' => '#5EB8D0',
            'type' => 'fijo',
            'year' => 2026,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('time_ranges');
    }

    public function test_actualizar_reemplaza_los_tramos(): void
    {
        $template = ScheduleTemplate::create([
            'company_id' => $this->company->id, 'name' => 'X', 'color' => '#5EB8D0',
            'type' => 'fijo', 'year' => 2026,
        ]);
        $template->timeRanges()->create(['time_start' => '08:00', 'time_end' => '15:00']);

        $this->putJson($this->url("/schedule-templates/{$template->id}"), [
            'time_ranges' => [
                ['time_start' => '09:00', 'time_end' => '17:00'],
            ],
        ])->assertOk()->assertJsonCount(1, 'data.time_ranges');

        $this->assertDatabaseCount('schedule_time_ranges', 1);
    }

    public function test_elimina_plantilla(): void
    {
        $template = ScheduleTemplate::create([
            'company_id' => $this->company->id, 'name' => 'X', 'color' => '#5EB8D0',
            'type' => 'libre', 'year' => 2026, 'free_hours_daily' => 8,
        ]);

        $this->deleteJson($this->url("/schedule-templates/{$template->id}"))->assertOk();
        $this->assertDatabaseMissing('schedule_templates', ['id' => $template->id]);
    }
}
