<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Registro público de nuevos tenants (landing/onboarding). No pasa por TenantMiddleware:
 * crea un tenant nuevo en plan Free con 30 días de trial.
 */
class RegisterController extends Controller
{
    private const RESERVED = ['www', 'admin', 'api', 'app', 'mail', 'staging', 'static', 'assets', 'superadmin'];

    public function __construct(private readonly TenantRegistrationService $registration) {}

    /** Comprobación en tiempo real de disponibilidad de subdominio. */
    public function checkSubdomain(Request $request): JsonResponse
    {
        $sub = strtolower(trim((string) $request->query('subdomain', '')));
        $valid = preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $sub) === 1
            && ! in_array($sub, self::RESERVED, true);
        $available = $valid && ! Tenant::query()->where('subdomain', $sub)->exists();

        return response()->json(['data' => ['subdomain' => $sub, 'valid' => $valid, 'available' => $available]]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:empresa,entidad,ambas'],
            'subdomain' => [
                'required', 'string', 'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/',
                Rule::notIn(self::RESERVED), Rule::unique('tenants', 'subdomain'),
            ],
            'admin_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $result = $this->registration->register(
                name: $data['name'],
                subdomain: $data['subdomain'],
                adminEmail: $data['admin_email'],
                planSlug: 'free',
                trialDays: 30,
                type: $data['type'],
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'TENANT_PROVISION_FAILED'], 422);
        }

        return response()->json([
            'message' => 'Cuenta creada. Revisa tu email para acceder.',
            'data' => ['url' => $result['url'], 'subdomain' => $result['tenant']->subdomain],
        ], 201);
    }
}
