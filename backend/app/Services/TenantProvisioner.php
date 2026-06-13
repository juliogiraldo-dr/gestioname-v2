<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\TenantSchema;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\NationalHolidaySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Provisión de tenants: crea el registro en `public.tenants`, su schema PostgreSQL,
 * ejecuta las migraciones de tenant y siembra los roles base.
 *
 * Lo usan tanto el comando `tenant:create` como `TenantDemoSeeder`, de modo que el flujo
 * de alta de un tenant esté en un único sitio.
 */
final class TenantProvisioner
{
    /** Ruta (relativa a la raíz del proyecto) de las migraciones que se aplican por tenant. */
    private const TENANT_MIGRATIONS_PATH = 'database/migrations/tenant';

    /**
     * Crea un tenant completo: registro + schema + migraciones + roles.
     *
     * @throws RuntimeException si el subdominio ya está en uso.
     */
    public function create(
        string $name,
        string $subdomain,
        string $plan = 'free',
        string $status = 'active',
    ): Tenant {
        TenantSchema::assertValid($subdomain);

        if (Tenant::query()->where('subdomain', $subdomain)->exists()) {
            throw new RuntimeException("Ya existe un tenant con el subdominio '{$subdomain}'.");
        }

        $tenant = Tenant::create([
            'name' => $name,
            'subdomain' => $subdomain,
            'plan' => $plan,
            'status' => $status,
        ]);

        TenantSchema::create($subdomain);
        $this->migrate($subdomain);
        $this->seedBase($subdomain);

        return $tenant;
    }

    /**
     * Aplica las migraciones de tenant sobre el schema indicado.
     * Aísla el search_path al schema (sin `public`) para que la tabla `migrations`
     * y todas las tablas se creen dentro del propio schema del tenant.
     */
    public function migrate(string $subdomain): void
    {
        TenantSchema::useOnly($subdomain);

        // Con el search_path aislado al schema del tenant, la tabla `cache` (en `public`)
        // no es visible. La migración de Spatie limpia su caché de permisos al final;
        // forzamos el driver `array` durante la migración para que esa limpieza no toque
        // la BD (es irrelevante crear/limpiar caché mientras se migra).
        $previousCache = config('cache.default');
        config(['cache.default' => 'array']);

        try {
            Artisan::call('migrate', [
                '--path' => self::TENANT_MIGRATIONS_PATH,
                '--force' => true,
            ]);
        } finally {
            config(['cache.default' => $previousCache]);
            TenantSchema::usePublic();
        }
    }

    /**
     * Crea el usuario administrador inicial del tenant y le asigna el rol `admin`.
     */
    public function createAdmin(string $subdomain, string $name, string $email, string $password): User
    {
        TenantSchema::use($subdomain);

        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,        // cast `hashed` en el modelo
                'email_verified_at' => now(),
            ]);

            $user->assignRole('admin');

            return $user;
        } finally {
            TenantSchema::usePublic();
        }
    }

    /**
     * Datos base de todo tenant nuevo: roles del sistema y festivos nacionales.
     */
    private function seedBase(string $subdomain): void
    {
        TenantSchema::use($subdomain);

        try {
            (new RoleSeeder)->run();
            (new NationalHolidaySeeder)->run();
            (new ChartOfAccountsSeeder)->run();
            TenantModule::syncCatalog();
        } finally {
            TenantSchema::usePublic();
        }
    }
}
