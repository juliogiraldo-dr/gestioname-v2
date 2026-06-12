<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * Base para tests de API dentro de un tenant. Crea el tenant `demo`, los roles del
 * sistema y un usuario admin autenticado. Las peticiones deben usar url() para incluir
 * el host del tenant (lo lee el TenantMiddleware).
 */
abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected const BASE = 'http://demo.gestioname.app/api/v1';

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Tenant::create([
            'name' => 'Datarecover Demo',
            'subdomain' => 'demo',
            'plan' => 'professional',
            'status' => 'active',
        ]);

        (new RoleSeeder)->run();

        $this->admin = $this->makeUser('admin');
        Sanctum::actingAs($this->admin);
    }

    protected function url(string $path): string
    {
        return self::BASE.$path;
    }

    /** Crea un usuario con el rol indicado (email único por rol). */
    protected function makeUser(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => $role.'@demo.gestioname.app',
            'password' => 'secret-password',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
