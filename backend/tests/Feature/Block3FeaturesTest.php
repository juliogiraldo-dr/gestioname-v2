<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Entity;
use App\Models\Member;
use App\Models\User;
use App\Notifications\ContractExpiringNotification;
use App\Notifications\UpgradeRequestNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class Block3FeaturesTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
    }

    private function employeeWithContract(string $endDate): Employee
    {
        return Employee::create([
            'company_id' => $this->company->id, 'first_name' => 'Juan', 'last_name' => 'G',
            'active' => true, 'contract_end_date' => $endDate,
        ]);
    }

    public function test_portal_del_socio_devuelve_datos_y_pagos(): void
    {
        $user = User::create(['name' => 'Socia', 'email' => 'socia@demo.gestioname.app', 'password' => 'secret-password']);
        $user->assignRole('member');

        $entity = Entity::create(['name' => 'Club', 'type' => 'club', 'fiscal_year' => 2026]);
        $member = $entity->members()->create(['first_name' => 'Ana', 'last_name' => 'Ruiz', 'status' => 'activo', 'user_id' => $user->id]);
        $member->payments()->create(['entity_id' => $entity->id, 'year' => 2026, 'amount' => 50, 'status' => 'pagado']);

        Sanctum::actingAs($user);

        $this->getJson($this->url('/me/member'))
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Ana Ruiz')
            ->assertJsonPath('data.entity.name', 'Club');

        $this->getJson($this->url('/me/member/payments'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pagado');
    }

    public function test_contratos_proximos_a_vencer(): void
    {
        $this->employeeWithContract(now()->addDays(10)->toDateString()); // dentro de 30
        $this->employeeWithContract(now()->addDays(60)->toDateString()); // fuera de 30

        $this->getJson($this->url('/employees/contracts-expiring?days=30'))
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonCount(1, 'data.employees');
    }

    public function test_comando_alerta_contratos_avisa_al_admin(): void
    {
        Notification::fake();
        // El admin (TenantTestCase) tiene email admin@demo.gestioname.app.
        $this->employeeWithContract(now()->addDays(30)->toDateString());
        $this->employeeWithContract(now()->addDays(7)->toDateString());
        $this->employeeWithContract(now()->addDays(15)->toDateString()); // no umbral

        $this->artisan('alerts:contracts')->assertSuccessful();

        Notification::assertSentOnDemandTimes(ContractExpiringNotification::class, 2);
    }

    public function test_solicitud_de_upgrade_envia_email(): void
    {
        Notification::fake();

        $this->postJson($this->url('/subscription/upgrade-request'), [
            'name' => 'Julio', 'email' => 'julio@datarecover.es', 'plan' => 'Business',
        ])->assertOk();

        Notification::assertSentOnDemandTimes(UpgradeRequestNotification::class, 1);
    }
}
