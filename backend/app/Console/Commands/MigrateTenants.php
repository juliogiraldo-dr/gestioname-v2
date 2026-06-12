<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantProvisioner;
use Illuminate\Console\Command;
use Throwable;

/**
 * Ejecuta las migraciones de tenant sobre TODOS los schemas.
 *
 *   php artisan migrate:tenants
 *   php artisan migrate:tenants --tenant=miempresa   (solo uno)
 *
 * Se procesa secuencialmente: cada tenant fija su propio search_path y registra sus
 * migraciones en la tabla `migrations` de su schema, por lo que ejecutarlo de nuevo es
 * idempotente (solo corre lo pendiente en cada schema).
 */
class MigrateTenants extends Command
{
    protected $signature = 'migrate:tenants
        {--tenant= : Migrar solo el tenant con este subdominio}
        {--include-suspended : Incluir también tenants no activos}';

    protected $description = 'Aplica las migraciones de tenant en todos los schemas';

    public function handle(TenantProvisioner $provisioner): int
    {
        $query = Tenant::query();

        if ($single = $this->option('tenant')) {
            $query->where('subdomain', $single);
        } elseif (! $this->option('include-suspended')) {
            $query->where('status', 'active');
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No hay tenants que migrar.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($tenants as $tenant) {
            $this->info("Migrando tenant: {$tenant->subdomain}");

            try {
                $provisioner->migrate($tenant->subdomain);
            } catch (Throwable $e) {
                $failed++;
                $this->error("  Error en '{$tenant->subdomain}': ".$e->getMessage());
            }
        }

        if ($failed > 0) {
            $this->error("{$failed} tenant(s) fallaron.");

            return self::FAILURE;
        }

        $this->info("Migrados {$tenants->count()} tenant(s) correctamente.");

        return self::SUCCESS;
    }
}
