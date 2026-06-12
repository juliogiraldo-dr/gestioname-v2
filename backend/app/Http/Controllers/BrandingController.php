<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantBranding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Marca blanca: branding visual del tenant (color, tipografía, logo) y dominio propio.
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
                'font' => $branding?->font,
                'logo_path' => $this->logoUrl($tenant, $branding),
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
            'font' => ['nullable', 'string', 'max:100'],
            'logo_path' => ['nullable', 'string', 'max:1024'],
            'custom_domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'custom_domain')->ignore($tenant->id)],
        ]);

        $tenant->update(['custom_domain' => ($data['custom_domain'] ?? null) ?: null]);

        $attributes = [
            'app_name' => $data['app_name'] ?? null,
            'primary_color' => $data['primary_color'] ?? null,
            'font' => $data['font'] ?? null,
        ];
        // Si llega logo_path (URL externa), se usa y se descarta el fichero subido.
        if (array_key_exists('logo_path', $data)) {
            $attributes['logo_path'] = $data['logo_path'] ?: null;
            $attributes['logo_file'] = null;
        }

        TenantBranding::updateOrCreate(['tenant_id' => $tenant->id], $attributes);

        Cache::forget("branding:{$tenant->id}");

        return $this->show();
    }

    /** Sube el logo como fichero (drag & drop). Lo sirve luego logo() de forma pública. */
    public function uploadLogo(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $request->validate(['file' => ['required', 'image', 'max:2048', 'mimes:png,jpg,jpeg,webp,svg']]);

        $branding = $tenant->branding;
        if ($branding?->logo_file) {
            Storage::delete($branding->logo_file);
        }

        $path = $request->file('file')->store("branding/{$tenant->id}");

        TenantBranding::updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['logo_file' => $path, 'logo_path' => null],
        );

        Cache::forget("branding:{$tenant->id}");

        return $this->show();
    }

    /** Sirve el logo subido de un tenant. Público (se usa en <img> antes de login). */
    public function logo(Tenant $tenant): StreamedResponse
    {
        $branding = $tenant->branding;
        abort_if($branding?->logo_file === null || ! Storage::exists($branding->logo_file), 404);

        return Storage::response($branding->logo_file);
    }

    /** URL pública del logo: la del fichero subido si existe, si no la URL externa guardada. */
    private function logoUrl(Tenant $tenant, ?TenantBranding $branding): ?string
    {
        if ($branding?->logo_file) {
            return rtrim((string) config('app.url'), '/')."/api/v1/branding/{$tenant->id}/logo";
        }

        return $branding?->logo_path;
    }
}
