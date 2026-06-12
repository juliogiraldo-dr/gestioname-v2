<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceMilestone;
use App\Models\CalendarDay;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ScheduleTemplate;
use App\Models\WorkCalendar;
use App\Services\Reports\WorkTimeRecordService;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class RegistroHorarioExportTest extends TenantTestCase
{
    private Company $company;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);

        // Plantilla fija 09:00–17:00 (8h), sin tolerancia.
        $template = ScheduleTemplate::create([
            'company_id' => $this->company->id,
            'name' => 'Jornada partida',
            'type' => 'fijo',
            'year' => 2026,
            'tolerance_minutes' => 0,
        ]);
        $template->timeRanges()->create(['time_start' => '09:00:00', 'time_end' => '17:00:00', 'sort_order' => 1]);

        $calendar = WorkCalendar::create(['company_id' => $this->company->id, 'name' => 'General', 'year' => 2026]);
        CalendarDay::create(['calendar_id' => $calendar->id, 'date' => '2026-06-01', 'schedule_template_id' => $template->id]);

        $this->employee = Employee::create([
            'company_id' => $this->company->id, 'first_name' => 'Juan', 'last_name' => 'García',
            'clock_code' => '12345678',
        ]);
        $calendar->employees()->attach($this->employee->id);

        $entrada = AttendanceMilestone::create(['company_id' => $this->company->id, 'name' => 'ENTRADA', 'type' => 'entrada', 'color' => '#90cbe8']);
        $salida = AttendanceMilestone::create(['company_id' => $this->company->id, 'name' => 'SALIDA', 'type' => 'salida', 'color' => '#f08080']);

        // Entra a las 09:15 (15 min tarde) y sale a las 17:30 → 8h15m trabajadas.
        Attendance::create(['employee_id' => $this->employee->id, 'milestone_id' => $entrada->id, 'clocked_at' => '2026-06-01 09:15:00', 'method' => 'kiosk']);
        Attendance::create(['employee_id' => $this->employee->id, 'milestone_id' => $salida->id, 'clocked_at' => '2026-06-01 17:30:00', 'method' => 'kiosk']);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'company_id' => $this->company->id,
            'format' => 'excel',
        ], $extra);
    }

    public function test_calculo_previstas_realizadas_sobretiempo_y_retraso(): void
    {
        $data = app(WorkTimeRecordService::class)->compute($this->payload());

        $this->assertCount(1, $data['employees']);
        $day = $data['employees'][0]['days'][0];

        $this->assertSame('2026-06-01', $day['date']);
        $this->assertSame(8.0, $day['expected']);
        $this->assertSame(8.25, $day['worked']);
        $this->assertSame(0.25, $day['overtime']);
        $this->assertSame(15, $day['delay_minutes']);

        $this->assertSame(8.0, $data['totals']['expected']);
        $this->assertSame(8.25, $data['totals']['worked']);
        $this->assertSame(15, $data['totals']['delay_minutes']);
    }

    public function test_exporta_excel(): void
    {
        $response = $this->postJson($this->url('/reports/work-time-record'), $this->payload());

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Un .xlsx es un ZIP: empieza por la firma "PK".
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_exporta_pdf(): void
    {
        $response = $this->postJson($this->url('/reports/work-time-record'), $this->payload(['format' => 'pdf']));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }

    public function test_split_by_employee_devuelve_zip(): void
    {
        $response = $this->postJson($this->url('/reports/work-time-record'), $this->payload([
            'options' => ['split_by_employee' => true],
        ]));

        $response->assertOk()->assertHeader('Content-Type', 'application/zip');
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_valida_rango_de_fechas(): void
    {
        $this->postJson($this->url('/reports/work-time-record'), $this->payload(['date_to' => '2026-05-01']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_to');
    }

    public function test_operator_no_puede_generar_informes(): void
    {
        Sanctum::actingAs($this->makeUser('operator'));

        $this->postJson($this->url('/reports/work-time-record'), $this->payload())
            ->assertForbidden();
    }

    public function test_resumen_ausencias_excel(): void
    {
        $response = $this->postJson($this->url('/reports/leave-summary'), [
            'year' => 2026,
            'company_id' => $this->company->id,
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_informe_diario_pdf(): void
    {
        $response = $this->postJson($this->url('/reports/daily-attendance'), [
            'date' => '2026-06-01',
            'company_id' => $this->company->id,
            'format' => 'pdf',
        ]);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }
}
