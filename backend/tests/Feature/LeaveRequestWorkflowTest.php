<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementLeaveType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestReviewedNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TenantTestCase;

class LeaveRequestWorkflowTest extends TenantTestCase
{
    private Employee $employee;

    private AgreementLeaveType $vacaciones;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);

        $employeeUser = User::create(['name' => 'Juan', 'email' => 'juan@demo.gestioname.app']);
        $employeeUser->assignRole('employee');

        $agreement = Agreement::create([
            'company_id' => $company->id, 'name' => 'Oficinas', 'annual_hours' => 1780,
            'vacation_days' => 22, 'vacation_type' => 'laborables',
        ]);
        $this->vacaciones = $agreement->leaveTypes()->create([
            'name' => 'Vacaciones', 'type' => 'ausencia', 'count_in' => 'dias', 'subtracts_vacation' => true,
        ]);

        $this->employee = Employee::create([
            'company_id' => $company->id, 'agreement_id' => $agreement->id, 'user_id' => $employeeUser->id,
            'first_name' => 'Juan', 'last_name' => 'García',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->vacaciones->id,
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-05',
        ], $overrides);
    }

    public function test_crea_solicitud_calcula_dias_y_notifica_gestores(): void
    {
        Notification::fake();

        $this->postJson($this->url('/leave-requests'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.total_days', 5);

        Notification::assertSentTo($this->admin, LeaveRequestSubmittedNotification::class);
    }

    public function test_aprobar_cambia_estado_y_notifica_al_empleado(): void
    {
        Notification::fake();
        $request = LeaveRequest::create($this->payload(['total_days' => 5, 'status' => 'pendiente']));

        $this->postJson($this->url("/leave-requests/{$request->id}/approve"))
            ->assertOk()
            ->assertJsonPath('data.status', 'aprobada')
            ->assertJsonPath('data.reviewed_by', $this->admin->id);

        Notification::assertSentTo($this->employee->user, LeaveRequestReviewedNotification::class);
    }

    public function test_rechazar_con_nota(): void
    {
        Notification::fake();
        $request = LeaveRequest::create($this->payload(['total_days' => 5, 'status' => 'pendiente']));

        $this->postJson($this->url("/leave-requests/{$request->id}/reject"), ['note' => 'No procede'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rechazada')
            ->assertJsonPath('data.review_note', 'No procede');
    }

    public function test_no_se_puede_resolver_dos_veces(): void
    {
        $request = LeaveRequest::create($this->payload(['total_days' => 5, 'status' => 'aprobada']));

        $this->postJson($this->url("/leave-requests/{$request->id}/approve"))
            ->assertStatus(409)
            ->assertJsonPath('code', 'LEAVE_NOT_PENDING');
    }

    public function test_solapamiento_de_fechas_devuelve_409(): void
    {
        LeaveRequest::create($this->payload(['total_days' => 5, 'status' => 'pendiente']));

        $this->postJson($this->url('/leave-requests'), $this->payload(['date_start' => '2026-07-03', 'date_end' => '2026-07-07']))
            ->assertStatus(409)
            ->assertJsonPath('code', 'LEAVE_OVERLAP');
    }

    public function test_cancelar_solo_si_pendiente(): void
    {
        $pending = LeaveRequest::create($this->payload(['total_days' => 5, 'status' => 'pendiente']));
        $this->deleteJson($this->url("/leave-requests/{$pending->id}"))->assertOk();
        $this->assertDatabaseMissing('leave_requests', ['id' => $pending->id]);

        $approved = LeaveRequest::create($this->payload([
            'date_start' => '2026-09-01', 'date_end' => '2026-09-03', 'total_days' => 3, 'status' => 'aprobada',
        ]));
        $this->deleteJson($this->url("/leave-requests/{$approved->id}"))->assertStatus(409);
    }

    public function test_resumen_de_vacaciones(): void
    {
        LeaveRequest::create($this->payload(['total_days' => 5, 'status' => 'aprobada']));
        LeaveRequest::create($this->payload([
            'date_start' => '2026-08-01', 'date_end' => '2026-08-03', 'total_days' => 3, 'status' => 'pendiente',
        ]));

        $this->getJson($this->url("/employees/{$this->employee->id}/vacations?year=2026"))
            ->assertOk()
            ->assertJsonPath('data.available', 22)
            ->assertJsonPath('data.approved', 5)
            ->assertJsonPath('data.requested', 3)
            ->assertJsonPath('data.remaining', 17);
    }
}
