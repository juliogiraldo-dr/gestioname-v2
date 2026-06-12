<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TenantModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantModuleController extends Controller
{
    /** Lista los módulos del tenant (catálogo completo + estado activado). */
    public function index(): JsonResponse
    {
        TenantModule::syncCatalog();

        $enabled = TenantModule::query()->pluck('enabled', 'key');

        $data = collect(TenantModule::CATALOG)->map(fn (array $meta, string $key) => [
            'key' => $key,
            'label' => $meta['label'],
            'description' => $meta['description'],
            'enabled' => (bool) ($enabled[$key] ?? $meta['default']),
        ])->values();

        return response()->json(['data' => $data]);
    }

    /** Activa/desactiva un módulo del tenant. */
    public function update(Request $request, string $key): JsonResponse
    {
        abort_unless(array_key_exists($key, TenantModule::CATALOG), 404, 'Módulo no encontrado.');

        $validated = $request->validate(['enabled' => ['required', 'boolean']]);

        $module = TenantModule::query()->firstOrNew(['key' => $key]);
        $module->enabled = $validated['enabled'];
        $module->save();

        return response()->json(['data' => [
            'key' => $key,
            'label' => TenantModule::CATALOG[$key]['label'],
            'enabled' => $module->enabled,
        ]]);
    }
}
