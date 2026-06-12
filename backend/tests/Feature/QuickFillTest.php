<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Holiday;
use App\Models\ScheduleTemplate;
use App\Models\WorkCalendar;
use Illuminate\Support\Carbon;
use Tests\TenantTestCase;

class QuickFillTest extends TenantTestCase
{
    private WorkCalendar $calendar;

    private ScheduleTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $this->template = ScheduleTemplate::create([
            'company_id' => $company->id, 'name' => 'Jornada', 'color' => '#5EB8D0',
            'type' => 'fijo', 'year' => 2026,
        ]);
        $this->calendar = WorkCalendar::create([
            'company_id' => $company->id, 'name' => 'General 2026', 'year' => 2026,
        ]);
    }

    public function test_fill_quick_excluye_festivos(): void
    {
        $holiday = '2026-07-15';
        $weekday = Carbon::parse($holiday)->isoWeekday();
        Holiday::create(['name' => 'Fiesta', 'type' => 'local', 'repeatable' => false, 'date' => $holiday]);

        $this->postJson($this->url("/calendars/{$this->calendar->id}/fill-quick"), [
            'weekdays' => [$weekday],
            'months' => [7],
            'schedule_template_id' => $this->template->id,
            'include_holidays' => false,
        ])->assertOk();

        // El festivo (mismo día de semana y mes) NO se rellenó.
        $this->assertDatabaseMissing('calendar_days', ['calendar_id' => $this->calendar->id, 'date' => $holiday]);
        // Pero sí otros días del mismo día de la semana en julio.
        $this->assertGreaterThan(0, $this->calendar->days()->count());
    }

    public function test_fill_quick_incluye_festivos_con_flag(): void
    {
        $holiday = '2026-07-15';
        $weekday = Carbon::parse($holiday)->isoWeekday();
        Holiday::create(['name' => 'Fiesta', 'type' => 'local', 'repeatable' => false, 'date' => $holiday]);

        $this->postJson($this->url("/calendars/{$this->calendar->id}/fill-quick"), [
            'weekdays' => [$weekday],
            'months' => [7],
            'schedule_template_id' => $this->template->id,
            'include_holidays' => true,
        ])->assertOk();

        $this->assertDatabaseHas('calendar_days', ['calendar_id' => $this->calendar->id, 'date' => $holiday]);
    }

    public function test_fill_quick_solo_dias_laborables(): void
    {
        // Lunes a viernes, todo el año.
        $response = $this->postJson($this->url("/calendars/{$this->calendar->id}/fill-quick"), [
            'weekdays' => [1, 2, 3, 4, 5],
            'months' => range(1, 12),
            'schedule_template_id' => $this->template->id,
            'include_holidays' => true,
        ])->assertOk();

        // 2026 tiene 261 días laborables (L-V).
        $this->assertSame(261, $response->json('days_filled'));
        $this->assertDatabaseCount('calendar_days', 261);
    }

    public function test_fill_manual(): void
    {
        $this->postJson($this->url("/calendars/{$this->calendar->id}/fill-manual"), [
            'dates' => ['2026-01-07', '2026-01-08'],
            'schedule_template_id' => $this->template->id,
        ])->assertOk()->assertJsonPath('days_filled', 2);

        $this->assertDatabaseHas('calendar_days', ['calendar_id' => $this->calendar->id, 'date' => '2026-01-07']);
    }
}
