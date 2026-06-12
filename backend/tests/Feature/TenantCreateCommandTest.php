<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica el comando `tenant:create`. En SQLite la creación de schema y la migración
 * son no-op (las tablas ya existen por las migraciones de tenant autocargadas en testing),
 * por lo que el test se centra en: registro del tenant, siembra de roles y alta del admin.
 */
class TenantCreateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_tenant_con_roles_y_usuario_admin(): void
    {
        $this->artisan('tenant:create', [
            'name' => 'Acme S.L.',
            'subdomain' => 'acme',
            '--admin-email' => 'admin@acme.test',
            '--admin-password' => 'secret-password',
        ])->assertSuccessful();

        $this->assertDatabaseHas('tenants', [
            'subdomain' => 'acme',
            'name' => 'Acme S.L.',
            'status' => 'active',
        ]);

        // Se sembraron los 6 roles del sistema.
        $this->assertDatabaseCount('roles', count(RoleSeeder::ROLES));

        $admin = User::where('email', 'admin@acme.test')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_rechaza_subdominio_no_valido(): void
    {
        $this->artisan('tenant:create', [
            'name' => 'Mala',
            'subdomain' => 'Sub_Inválido!',
        ])->assertFailed();

        $this->assertDatabaseMissing('tenants', ['name' => 'Mala']);
    }

    public function test_rechaza_subdominio_duplicado(): void
    {
        $this->artisan('tenant:create', ['name' => 'Acme', 'subdomain' => 'acme'])
            ->assertSuccessful();

        $this->artisan('tenant:create', ['name' => 'Otra Acme', 'subdomain' => 'acme'])
            ->assertFailed();

        $this->assertDatabaseCount('tenants', 1);
    }
}
