<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkCenter;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TenantTestCase;

class EmployeeApiTest extends TenantTestCase
{
    private Company $company;

    private WorkCenter $center;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $this->center = $this->company->workCenters()->create(['name' => 'Sede Madrid']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'work_center_id' => $this->center->id,
            'first_name' => 'Juan',
            'last_name' => 'García',
        ], $overrides);
    }

    public function test_crea_empleado_y_genera_codigo_de_fichaje(): void
    {
        $response = $this->postJson($this->url('/employees'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.first_name', 'Juan');

        $code = $response->json('data.clock_code');
        $this->assertNotNull($code);
        $this->assertMatchesRegularExpression('/^\d{8}$/', $code);
    }

    public function test_crea_empleado_con_ips_permitidas(): void
    {
        $response = $this->postJson($this->url('/employees'), $this->payload([
            'allowed_ips' => [
                ['ip_address' => '80.1.2.3', 'description' => 'Oficina'],
            ],
        ]))->assertCreated();

        $this->assertDatabaseHas('employee_allowed_ips', [
            'employee_id' => $response->json('data.id'),
            'ip_address' => '80.1.2.3',
        ]);
    }

    public function test_dni_se_guarda_cifrado(): void
    {
        $response = $this->postJson($this->url('/employees'), $this->payload(['dni' => '12345678Z']))
            ->assertCreated()
            ->assertJsonPath('data.dni', '12345678Z');

        // En BD el valor está cifrado, no en claro.
        $stored = DB::table('employees')
            ->where('id', $response->json('data.id'))->value('dni');
        $this->assertNotSame('12345678Z', $stored);
    }

    public function test_lista_con_filtros(): void
    {
        $this->postJson($this->url('/employees'), $this->payload(['department' => 'IT']))->assertCreated();
        $this->postJson($this->url('/employees'), $this->payload(['first_name' => 'Ana', 'department' => 'RRHH']))->assertCreated();

        $this->getJson($this->url("/employees?company_id={$this->company->id}&department=IT"))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_show_incluye_incidencias(): void
    {
        // Empleado sin centro, sin convenio y sin calendario del año actual.
        $employee = Employee::create([
            'company_id' => $this->company->id,
            'first_name' => 'Sin', 'last_name' => 'Config',
        ]);

        $this->getJson($this->url("/employees/{$employee->id}"))
            ->assertOk()
            ->assertJsonPath('meta.incidences', fn ($i) => in_array('no_work_center', $i, true)
                && in_array('no_agreement', $i, true)
                && in_array('no_calendar', $i, true));
    }

    public function test_actualiza_empleado(): void
    {
        $employee = Employee::create($this->payload());

        $this->putJson($this->url("/employees/{$employee->id}"), ['job_position' => 'CTO'])
            ->assertOk()
            ->assertJsonPath('data.job_position', 'CTO');
    }

    public function test_activar_y_desactivar(): void
    {
        $employee = Employee::create($this->payload());

        $this->patchJson($this->url("/employees/{$employee->id}/deactivate"))
            ->assertOk()
            ->assertJsonPath('data.active', false)
            ->assertJsonPath('data.employment_status', 'inactive');

        $this->patchJson($this->url("/employees/{$employee->id}/activate"))
            ->assertOk()
            ->assertJsonPath('data.active', true);
    }

    public function test_no_se_puede_borrar_empresa_con_empleados(): void
    {
        Employee::create($this->payload());

        $this->deleteJson($this->url("/companies/{$this->company->id}"))
            ->assertStatus(409)
            ->assertJsonPath('code', 'COMPANY_HAS_EMPLOYEES');
    }

    public function test_invitar_crea_usuario_y_envia_magic_link(): void
    {
        Notification::fake();

        $this->postJson($this->url('/employees/invite'), [
            'company_id' => $this->company->id,
            'first_name' => 'Nueva',
            'last_name' => 'Empleada',
            'email' => 'nueva@demo.gestioname.app',
        ])->assertCreated();

        $user = User::where('email', 'nueva@demo.gestioname.app')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('employee'));
        Notification::assertSentTo($user, MagicLinkNotification::class);
    }
}
