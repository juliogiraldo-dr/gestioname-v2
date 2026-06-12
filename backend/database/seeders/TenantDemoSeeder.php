<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use App\Support\TenantSchema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Crea el tenant de demostración `demo` con su schema, migraciones, roles, un admin y
 * un super-admin. Idempotente: si el tenant ya existe, solo asegura plan y super-admin.
 *
 *   php artisan db:seed --class=TenantDemoSeeder
 */
class TenantDemoSeeder extends Seeder
{
    private const SUBDOMAIN = 'demo';

    public function run(): void
    {
        $tenant = Tenant::query()->where('subdomain', self::SUBDOMAIN)->first();

        if ($tenant === null) {
            /** @var TenantProvisioner $provisioner */
            $provisioner = app(TenantProvisioner::class);

            $provisioner->create(name: 'Datarecover Demo', subdomain: self::SUBDOMAIN, plan: 'professional', status: 'active');
            $provisioner->createAdmin(self::SUBDOMAIN, 'Administrador', 'admin@demo.gestioname.app', 'password');

            $this->call(DemoSociosSeeder::class);

            $tenant = Tenant::query()->where('subdomain', self::SUBDOMAIN)->first();
        }

        // Plan profesional + trial generoso (idempotente).
        $plan = Plan::query()->where('slug', 'professional')->first();
        $tenant->update([
            'plan_id' => $plan?->id,
            'trial_ends_at' => $tenant->trial_ends_at ?? Carbon::now()->addYears(5),
        ]);

        $this->ensureSuperAdmin();

        $this->command?->info('Demo: admin@demo.gestioname.app / password · superadmin@demo.gestioname.app / password');
    }

    /** Crea el usuario super-admin del tenant demo (dentro de su schema). */
    private function ensureSuperAdmin(): void
    {
        TenantSchema::use(self::SUBDOMAIN);

        try {
            $user = User::query()->firstOrCreate(
                ['email' => 'superadmin@demo.gestioname.app'],
                ['name' => 'Super Admin', 'password' => 'password', 'email_verified_at' => Carbon::now()],
            );

            if (! $user->hasRole('super-admin')) {
                $user->assignRole('super-admin');
            }
        } finally {
            TenantSchema::usePublic();
        }
    }
}
