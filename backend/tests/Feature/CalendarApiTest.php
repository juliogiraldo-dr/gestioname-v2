<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ScheduleTemplate;
use App\Models\WorkCalendar;
use Tests\TenantTestCase;

class CalendarApiTest extends TenantTestCase
{
    private Company $company;

    private ScheduleTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $this->template = ScheduleTemplate::create([
            'company_id' => $this->company->id, 'name' => 'Jornada', 'color' => '#5EB8D0',
            'type' => 'fijo', 'year' => 2026,
        ]);
        $this->template->timeRanges()->create(['time_start' => '09:00', 'time_end' => '17:00']); // 8h
    }

    private function calendar(int $year = 2026): WorkCalendar
    {
        return WorkCalendar::create([
            'company_id' => $this->company->id, 'name' => "Calendario $year", 'year' => $year,
        ]);
    }

    public function test_crea_calendario(): void
    {
        $this->postJson($this->url('/calendars'), [
            'company_id' => $this->company->id,
            'name' => 'General 2026',
            'year' => 2026,
            'province' => 'Madrid',
        ])->assertCreated()->assertJsonPath('data.year', 2026);
    }

    public function test_lista_filtra_por_empresa_y_anio(): void
    {
        $this->calendar(2026);
        $this->calendar(2025);

        $this->getJson($this->url("/calendars?company_id={$this->company->id}&year=2026"))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_clona_calendario_a_otro_anio(): void
    {
        $calendar = $this->calendar(2026);
        $calendar->days()->create(['date' => '2026-03-10', 'schedule_template_id' => $this->template->id]);

        $response = $this->postJson($this->url("/calendars/{$calendar->id}/clone"), ['target_year' => 2027])
            ->assertCreated()
            ->assertJsonPath('data.year', 2027);

        $cloneId = $response->json('data.id');
        $this->assertDatabaseHas('calendar_days', ['calendar_id' => $cloneId, 'date' => '2027-03-10']);
    }

    public function test_borra_dias_del_calendario(): void
    {
        $calendar = $this->calendar(2026);
        $calendar->days()->create(['date' => '2026-03-10', 'schedule_template_id' => $this->template->id]);
        $calendar->days()->create(['date' => '2026-03-11', 'schedule_template_id' => $this->template->id]);

        $this->deleteJson($this->url("/calendars/{$calendar->id}/clear"))
            ->assertOk()
            ->assertJsonPath('days_deleted', 2);

        $this->assertDatabaseCount('calendar_days', 0);
    }

    public function test_simula_horas_de_vacaciones(): void
    {
        $calendar = $this->calendar(2026);
        // 3 días laborables de 8h en el rango.
        foreach (['2026-03-10', '2026-03-11', '2026-03-12'] as $d) {
            $calendar->days()->create(['date' => $d, 'schedule_template_id' => $this->template->id]);
        }

        $this->getJson($this->url("/calendars/{$calendar->id}/simulate-vacation?date_from=2026-03-10&date_to=2026-03-12"))
            ->assertOk()
            ->assertJsonPath('data.working_days', 3)
            ->assertJsonPath('data.scheduled_hours', 24);
    }
}
