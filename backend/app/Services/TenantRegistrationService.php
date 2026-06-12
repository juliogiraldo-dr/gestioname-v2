<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Notifications\TenantWelcomeNotification;
use App\Services\Auth\AuthService;
use App\Support\TenantSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Alta completa de un tenant: provisión (schema + migraciones + roles + módulos),
 * plan y trial, usuario admin y magic link de bienvenida. Lo usan el registro público
 * y el panel super-admin.
 */
final class TenantRegistrationService
{
    /** Módulos activados por defecto según el tipo de organización. */
    private const MODULES_BY_TYPE = [
        'empresa' => ['rrhh', 'tesoreria'],
        'entidad' => ['socios', 'tesoreria'],
        'ambas' => ['rrhh', 'socios', 'tesoreria'],
    ];

    public function __construct(
        private readonly TenantProvisioner $provisioner,
        private readonly AuthService $auth,
    ) {}

    /**
     * @return array{tenant: Tenant, url: string}
     */
    public function register(
        string $name,
        string $subdomain,
        string $adminEmail,
        string $planSlug = 'free',
        int $trialDays = 30,
        string $type = 'ambas',
        string $adminName = 'Administrador',
        bool $termsAccepted = false,
    ): array {
        $plan = Plan::query()->where('slug', $planSlug)->first();

        $tenant = $this->provisioner->create(
            name: $name,
            subdomain: $subdomain,
            plan: $planSlug,
            status: 'active',
        );

        $tenant->update([
            'plan_id' => $plan?->id,
            'trial_ends_at' => $trialDays > 0 ? Carbon::now()->addDays($trialDays) : null,
            'terms_accepted_at' => $termsAccepted ? Carbon::now() : null,
        ]);

        // El admin entra por magic link; la contraseña inicial es aleatoria e inutilizable.
        $this->provisioner->createAdmin($subdomain, $adminName, $adminEmail, Str::random(40));

        $this->configureModules($subdomain, $type);

        // Email de bienvenida con enlace de acceso (magic link, 24 h) y resumen del plan.
        TenantSchema::use($subdomain);
        try {
            $url = $this->auth->createMagicLinkUrl($adminEmail, $subdomain, 60 * 24);
            if ($url !== null) {
                Notification::route('mail', $adminEmail)
                    ->notify(new TenantWelcomeNotification($name, $url, $plan?->name ?? 'Free', $trialDays));
            }
        } finally {
            TenantSchema::usePublic();
        }

        return [
            'tenant' => $tenant->fresh(),
            'url' => "https://{$subdomain}.gestioname.app",
        ];
    }

    /** Activa los módulos correspondientes al tipo de organización. */
    private function configureModules(string $subdomain, string $type): void
    {
        $enabled = self::MODULES_BY_TYPE[$type] ?? self::MODULES_BY_TYPE['ambas'];

        TenantSchema::use($subdomain);
        try {
            TenantModule::syncCatalog();
            TenantModule::query()->update(['enabled' => false]);
            TenantModule::query()->whereIn('key', $enabled)->update(['enabled' => true]);
        } finally {
            TenantSchema::usePublic();
        }
    }
}
