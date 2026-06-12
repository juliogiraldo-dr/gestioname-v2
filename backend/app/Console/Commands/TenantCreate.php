<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TenantProvisioner;
use App\Support\TenantSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

/**
 * Crea un tenant: registro en `public.tenants`, schema PostgreSQL, migraciones y roles.
 *
 *   php artisan tenant:create "Mi Empresa" miempresa
 *   php artisan tenant:create "Mi Empresa" miempresa --admin-email=admin@miempresa.es
 */
class TenantCreate extends Command
{
    protected $signature = 'tenant:create
        {name : Nombre de la empresa/entidad}
        {subdomain : Subdominio = nombre del schema (etiqueta DNS)}
        {--plan=free : Plan inicial}
        {--status=active : Estado inicial (trial|active|suspended)}
        {--admin-email= : Si se indica, crea un usuario admin con este email}
        {--admin-password= : Contraseña del admin (si se omite, se genera una aleatoria)}';

    protected $description = 'Crea un nuevo tenant con su schema, migraciones y roles';

    public function handle(TenantProvisioner $provisioner): int
    {
        $name = (string) $this->argument('name');
        $subdomain = strtolower(trim((string) $this->argument('subdomain')));

        if (! TenantSchema::isValid($subdomain)) {
            $this->error("Subdominio no válido: '{$subdomain}'. Debe ser una etiqueta DNS (a-z, 0-9, guiones).");

            return self::FAILURE;
        }

        try {
            $tenant = $provisioner->create(
                name: $name,
                subdomain: $subdomain,
                plan: (string) $this->option('plan'),
                status: (string) $this->option('status'),
            );
        } catch (Throwable $e) {
            $this->error('No se pudo crear el tenant: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Tenant creado: {$tenant->name} (schema '{$tenant->subdomain}', id {$tenant->id}).");

        $adminEmail = $this->option('admin-email');
        if ($adminEmail !== null && $adminEmail !== '') {
            $password = (string) ($this->option('admin-password') ?: Str::password(16));

            $provisioner->createAdmin(
                subdomain: $subdomain,
                name: 'Administrador',
                email: (string) $adminEmail,
                password: $password,
            );

            $this->info("Usuario admin creado: {$adminEmail}");
            if (! $this->option('admin-password')) {
                $this->warn("Contraseña generada: {$password}");
            }
        }

        return self::SUCCESS;
    }
}
