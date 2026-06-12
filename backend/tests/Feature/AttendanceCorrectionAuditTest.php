<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceMilestone;
use App\Models\Company;
use App\Models\Employee;
use Tests\TenantTestCase;

class AttendanceCorrectionAuditTest extends TenantTestCase
{
    private Employee $employee;

    private AttendanceMilestone $entrada;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $this->employee = Employee::create([
            'company_id' => $company->id, 'first_name' => 'Juan', 'last_name' => 'García', 'clock_code' => '12345678',
        ]);
        $this->entrada = $company->milestones()->create(['name' => 'ENTRADA', 'type' => 'entrada', 'color' => '#90cbe8']);
    }

    private function attendance(string $at = '2026-03-10 09:00:00'): Attendance
    {
        return Attendance::create([
            'employee_id' => $this->employee->id,
            'milestone_id' => $this->entrada->id,
            'clocked_at' => $at,
            'method' => 'manual',
        ]);
    }

    public function test_fichaje_manual(): void
    {
        $this->postJson($this->url('/attendance/manual'), [
            'employee_id' => $this->employee->id,
            'milestone_id' => $this->entrada->id,
            'clocked_at' => '2026-03-10 08:55:00',
        ])->assertCreated()->assertJsonPath('data.method', 'manual');
    }

    public function test_correccion_crea_registro_inmutable_y_actualiza(): void
    {
        $attendance = $this->attendance('2026-03-10 09:00:00');

        $this->putJson($this->url("/attendance/{$attendance->id}"), [
            'new_clocked_at' => '2026-03-10 09:05:00',
            'reason' => 'El empleado fichó tarde por error del lector',
        ])->assertOk();

        // El registro se actualiza...
        $this->assertSame('2026-03-10T09:05:00+00:00', $attendance->fresh()->clocked_at->toIso8601String());

        // ...y queda traza inmutable del valor anterior y el autor.
        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'corrected_by' => $this->admin->id,
        ]);
        $correction = $attendance->corrections()->first();
        $this->assertSame('2026-03-10T09:00:00+00:00', $correction->old_clocked_at->toIso8601String());
        $this->assertSame('2026-03-10T09:05:00+00:00', $correction->new_clocked_at->toIso8601String());
    }

    public function test_borrado_es_logico_y_deja_auditoria(): void
    {
        $attendance = $this->attendance();

        $this->deleteJson($this->url("/attendance/{$attendance->id}"), [
            'reason' => 'Fichaje duplicado',
        ])->assertOk();

        // Soft delete: la fila permanece en BD con deleted_at.
        $this->assertSoftDeleted('attendances', ['id' => $attendance->id]);
        // Auditoría con new_clocked_at nulo (eliminación).
        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'new_clocked_at' => null,
            'corrected_by' => $this->admin->id,
        ]);
    }

    public function test_historial_de_correcciones(): void
    {
        $attendance = $this->attendance();
        $this->putJson($this->url("/attendance/{$attendance->id}"), [
            'new_clocked_at' => '2026-03-10 09:05:00', 'reason' => 'ajuste 1',
        ])->assertOk();

        $this->getJson($this->url("/attendance/{$attendance->id}/corrections"))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_informe_diario_filtra_por_fecha(): void
    {
        $this->attendance('2026-03-10 09:00:00');
        $this->attendance('2026-03-11 09:00:00');

        $this->getJson($this->url('/attendance/daily?date=2026-03-10'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
