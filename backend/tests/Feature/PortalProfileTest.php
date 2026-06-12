<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class PortalProfileTest extends TenantTestCase
{
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $agreement = Agreement::create(['company_id' => $company->id, 'name' => 'Oficinas', 'annual_hours' => 1750, 'vacation_days' => 22, 'vacation_type' => 'laborables']);

        $user = User::create(['name' => 'Juan', 'email' => 'juan@demo.gestioname.app', 'password' => 'secret-password']);
        $user->assignRole('employee');

        $this->employee = Employee::create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'first_name' => 'Juan', 'last_name' => 'García',
            'job_position' => 'Técnico', 'employment_status' => 'active',
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);
    }

    public function test_empleado_edita_su_perfil_de_contacto(): void
    {
        $this->putJson($this->url('/me/profile'), [
            'phone_personal' => '600111222',
            'address' => 'Calle Mayor 1',
            'city' => 'Madrid',
        ])->assertOk()->assertJsonPath('data.phone_personal', '600111222');

        $this->assertSame('Madrid', $this->employee->fresh()->city);
    }

    public function test_empleado_sube_su_avatar(): void
    {
        $this->postJson($this->url('/me/avatar'), [
            'file' => UploadedFile::fake()->image('foto.jpg', 200, 200),
        ])->assertOk();

        $path = $this->employee->fresh()->photo_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);

        $this->getJson($this->url('/me/avatar'))->assertOk();
    }

    public function test_empleado_ve_sus_datos_laborales(): void
    {
        $this->getJson($this->url('/me/labor'))
            ->assertOk()
            ->assertJsonPath('data.contract.job_position', 'Técnico')
            ->assertJsonPath('data.agreement.name', 'Oficinas')
            ->assertJsonPath('data.agreement.annual_hours', 1750);
    }
}
