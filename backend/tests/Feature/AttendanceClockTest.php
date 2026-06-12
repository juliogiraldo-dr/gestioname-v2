<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceMilestone;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Testing\TestResponse;
use Tests\TenantTestCase;

class AttendanceClockTest extends TenantTestCase
{
    private Employee $employee;

    private AttendanceMilestone $entrada;

    private AttendanceMilestone $salida;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $this->employee = Employee::create([
            'company_id' => $company->id, 'first_name' => 'Juan', 'last_name' => 'García',
            'clock_code' => '12345678',
        ]);
        $this->entrada = $company->milestones()->create(['name' => 'ENTRADA', 'type' => 'entrada', 'color' => '#90cbe8']);
        $this->salida = $company->milestones()->create(['name' => 'SALIDA', 'type' => 'salida', 'color' => '#f08080']);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function clock(string $milestoneId, string $code = '12345678', array $extra = []): TestResponse
    {
        return $this->postJson($this->url('/attendance/clock'), array_merge([
            'clock_code' => $code,
            'milestone_id' => $milestoneId,
        ], $extra));
    }

    public function test_fichaje_de_entrada_ok(): void
    {
        $this->clock($this->entrada->id)
            ->assertCreated()
            ->assertJsonPath('data.milestone.type', 'entrada');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'milestone_id' => $this->entrada->id,
        ]);
    }

    public function test_doble_entrada_devuelve_409(): void
    {
        $this->clock($this->entrada->id)->assertCreated();
        $this->clock($this->entrada->id)
            ->assertStatus(409)
            ->assertJsonPath('code', 'DOUBLE_ENTRY');
    }

    public function test_salida_sin_entrada_devuelve_409(): void
    {
        $this->clock($this->salida->id)
            ->assertStatus(409)
            ->assertJsonPath('code', 'NO_OPEN_ENTRY');
    }

    public function test_entrada_y_luego_salida_ok(): void
    {
        $this->clock($this->entrada->id)->assertCreated();
        $this->clock($this->salida->id)->assertCreated();

        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_codigo_invalido_devuelve_422(): void
    {
        $this->clock($this->entrada->id, code: '00000000')
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_CLOCK_CODE');
    }

    public function test_ip_no_permitida_devuelve_403(): void
    {
        // El empleado solo puede fichar desde una IP concreta (no la del test).
        $this->employee->allowedIps()->create(['ip_address' => '203.0.113.5', 'description' => 'Oficina']);

        $this->clock($this->entrada->id)
            ->assertStatus(403)
            ->assertJsonPath('code', 'IP_NOT_ALLOWED');
    }
}
