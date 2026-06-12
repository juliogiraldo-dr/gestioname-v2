<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantBranding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/**
 * Marca blanca: branding visual del tenant y dominio propio.
 */
class BrandingController extends Controller
{
    private const DEFAULT_APP_NAME = 'Gestioname';

    private const DEFAULT_COLOR = '#0F2756';

    /** Branding público (sin auth): el frontend lo aplica al cargar. Cacheado 10 min. */
    public function show(): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $data = Cache::remember("branding:{$tenant->id}", 600, function () use ($tenant): array {
            $branding = $tenant->branding;

            return [
                'app_name' => $branding?->app_name ?: self::DEFAULT_APP_NAME,
                'primary_color' => $branding?->primary_color ?: self::DEFAULT_COLOR,
                'logo_path' => $branding?->logo_path,
                'custom_domain' => $tenant->custom_domain,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /** Configura branding y dominio propio (módulo marca blanca). */
    public function update(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $data = $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo_path' => ['nullable', 'string', 'max:1024'],
            'custom_domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'custom_domain')->ignore($tenant->id)],
        ]);

        $tenant->update(['custom_domain' => ($data['custom_domain'] ?? null) ?: null]);

        TenantBranding::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'app_name' => $data['app_name'] ?? null,
                'primary_color' => $data['primary_color'] ?? null,
                'logo_path' => $data['logo_path'] ?? null,
            ],
        );

        Cache::forget("branding:{$tenant->id}");

        return $this->show();
    }
}
