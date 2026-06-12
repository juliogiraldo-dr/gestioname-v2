<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class MePortalTest extends TenantTestCase
{
    private Employee $employee;

    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);

        $this->employeeUser = User::create(['name' => 'Juan', 'email' => 'juan@demo.gestioname.app']);
        $this->employeeUser->assignRole('employee');

        $this->employee = Employee::create([
            'company_id' => $company->id, 'user_id' => $this->employeeUser->id,
            'first_name' => 'Juan', 'last_name' => 'García', 'job_position' => 'Dev',
        ]);

        Sanctum::actingAs($this->employeeUser);
    }

    public function test_perfil_incluye_ficha_de_empleado(): void
    {
        $this->getJson($this->url('/me'))
            ->assertOk()
            ->assertJsonPath('data.email', 'juan@demo.gestioname.app')
            ->assertJsonPath('data.roles', ['employee'])
            ->assertJsonPath('data.employee.full_name', 'Juan García');
    }

    public function test_solicita_ausencia_a_su_propio_nombre(): void
    {
        $agreement = Agreement::create([
            'company_id' => $this->employee->company_id, 'name' => 'Oficinas', 'annual_hours' => 1780,
            'vacation_days' => 22, 'vacation_type' => 'laborables',
        ]);
        $type = $agreement->leaveTypes()->create([
            'name' => 'Vacaciones', 'type' => 'ausencia', 'count_in' => 'dias', 'subtracts_vacation' => true,
        ]);

        $this->postJson($this->url('/me/leave-requests'), [
            'leave_type_id' => $type->id,
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-03',
        ])->assertCreated()->assertJsonPath('data.total_days', 3);

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->employee->id,
            'total_days' => 3,
        ]);
    }

    public function test_lista_mis_fichajes(): void
    {
        $this->getJson($this->url('/me/attendances'))->assertOk()->assertJsonStructure(['data', 'meta']);
    }

    public function test_usuario_sin_ficha_recibe_404(): void
    {
        $orphan = User::create(['name' => 'Orphan', 'email' => 'orphan@demo.gestioname.app']);
        $orphan->assignRole('employee');
        Sanctum::actingAs($orphan);

        $this->getJson($this->url('/me/attendances'))
            ->assertStatus(404)
            ->assertJsonPath('code', 'NO_EMPLOYEE_PROFILE');
    }
}
