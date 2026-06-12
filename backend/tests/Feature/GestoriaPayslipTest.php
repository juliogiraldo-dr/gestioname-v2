<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use App\Notifications\PayslipAvailableNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class GestoriaPayslipTest extends TenantTestCase
{
    private Company $company;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'first_name' => 'Juan',
            'last_name' => 'García',
            'email_personal' => 'juan@example.com',
        ]);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('nomina.pdf', 50, 'application/pdf');
    }

    public function test_gestoria_sube_nomina_y_avisa_al_empleado(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->makeUser('gestoria'));

        $this->postJson($this->url("/employees/{$this->employee->id}/payslips"), [
            'file' => $this->pdf(),
            'month' => 6,
            'year' => 2026,
        ])->assertCreated()
            ->assertJsonPath('data.period', 'junio de 2026')
            ->assertJsonPath('data.notified', true);

        $payslip = Payslip::first();
        $this->assertNotNull($payslip);
        Storage::disk('local')->assertExists($payslip->file_path);
        Notification::assertSentOnDemand(PayslipAvailableNotification::class);
    }

    public function test_resubir_nomina_del_mismo_periodo_reemplaza(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->makeUser('gestoria'));

        $this->postJson($this->url("/employees/{$this->employee->id}/payslips"), [
            'file' => $this->pdf(), 'month' => 6, 'year' => 2026,
        ])->assertCreated();
        $this->postJson($this->url("/employees/{$this->employee->id}/payslips"), [
            'file' => $this->pdf(), 'month' => 6, 'year' => 2026,
        ])->assertCreated();

        $this->assertSame(1, Payslip::where('employee_id', $this->employee->id)->count());
    }

    public function test_gestoria_no_puede_gestionar_empleados_ni_fichajes(): void
    {
        Sanctum::actingAs($this->makeUser('gestoria'));

        $this->getJson($this->url('/employees'))->assertForbidden();
        $this->getJson($this->url('/attendance/daily'))->assertForbidden();
    }

    public function test_gestoria_puede_ver_informes(): void
    {
        Sanctum::actingAs($this->makeUser('gestoria'));

        // Sin payload válido devuelve 422 (validación), no 403: el acceso está permitido.
        $this->postJson($this->url('/reports/leave-summary'), [])->assertStatus(422);
    }

    public function test_enlace_publico_de_descarga_es_de_un_solo_uso(): void
    {
        Sanctum::actingAs($this->makeUser('gestoria'));

        $payslip = $this->employee->payslips()->create([
            'month' => 5, 'year' => 2026,
            'file_path' => UploadedFile::fake()->create('n.pdf', 10)->store("payslips/{$this->employee->id}"),
            'original_name' => 'mayo.pdf',
        ]);

        $url = $this->postJson($this->url('/download-tokens'), ['payslip_id' => $payslip->id])
            ->assertCreated()
            ->json('data.url');

        $token = (string) substr($url, (int) strrpos($url, '/') + 1);

        // Primera descarga: OK (pública, sin autenticación).
        $this->getJson($this->url("/download/{$token}"))->assertOk();

        // Segunda: 410 Gone (ya usado).
        $this->getJson($this->url("/download/{$token}"))->assertStatus(410);
    }

    public function test_empleado_ve_y_descarga_su_nomina_pero_no_la_de_otro(): void
    {
        $user = User::create(['name' => 'Juan', 'email' => 'juan.user@demo.gestioname.app', 'password' => 'secret-password']);
        $user->assignRole('employee');
        $this->employee->update(['user_id' => $user->id]);

        $mine = $this->employee->payslips()->create([
            'month' => 4, 'year' => 2026,
            'file_path' => UploadedFile::fake()->create('m.pdf', 10)->store('payslips/mine'),
            'original_name' => 'abril.pdf',
        ]);

        $other = Employee::create(['company_id' => $this->company->id, 'first_name' => 'Ana', 'last_name' => 'Ruiz']);
        $hers = $other->payslips()->create([
            'month' => 4, 'year' => 2026,
            'file_path' => UploadedFile::fake()->create('o.pdf', 10)->store('payslips/other'),
            'original_name' => 'ana.pdf',
        ]);

        Sanctum::actingAs($user);

        $this->getJson($this->url('/me/payslips'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson($this->url("/me/payslips/{$mine->id}/download"))->assertOk();
        $this->getJson($this->url("/me/payslips/{$hers->id}/download"))->assertForbidden();
    }
}
