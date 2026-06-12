<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifica el tenant por subdominio y fija el search_path de PostgreSQL al schema
 * correspondiente, de forma que todos los queries Eloquent posteriores operen sobre
 * los datos del tenant. El schema `public` queda como fallback (tablas del sistema).
 *
 * Acceso al tenant durante la request:
 *   $tenant = app('tenant');                      // instancia compartida
 *   $tenant = $request->attributes->get('tenant');
 *
 * Nota: a diferencia del ejemplo en docs/multi-tenancy.md, NO inyectamos el tenant
 * en $request->merge(): eso contamina el input validado y es mass-assignable. Usamos
 * el contenedor y los attributes de la request, que es lo correcto.
 */
class TenantMiddleware
{
    /**
     * Subdominios reservados de la plataforma: nunca son un tenant.
     */
    private const RESERVED = ['www', 'admin', 'api', 'app', 'mail', 'staging', 'static', 'assets'];

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant === null) {
            abort(404, 'Tenant no encontrado.');
        }

        $this->switchSchema($tenant->subdomain);

        // Disponible en toda la request.
        app()->instance('tenant', $tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }

    /**
     * Resuelve el tenant de la request por, en orden de prioridad:
     *   1. Subdominio de la plataforma (empresa.gestioname.app).
     *   2. Dominio propio (marca blanca): host completo == tenants.custom_domain.
     *   3. Cabecera `X-Tenant-ID` (desarrollo local sin subdominio).
     *
     * El subdominio real SIEMPRE tiene prioridad: en producción la cabecera y el dominio
     * propio no pueden suplantar a otro tenant servido por subdominio.
     */
    private function resolveTenant(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain !== null && ! in_array($subdomain, self::RESERVED, true)) {
            $bySubdomain = Tenant::query()->where('subdomain', $subdomain)->where('status', 'active')->first();
            if ($bySubdomain !== null) {
                return $bySubdomain;
            }
        }

        $byDomain = Tenant::query()->where('custom_domain', $host)->where('status', 'active')->first();
        if ($byDomain !== null) {
            return $byDomain;
        }

        $header = $request->header('X-Tenant-ID');
        if (is_string($header) && trim($header) !== '') {
            return Tenant::query()
                ->where('subdomain', strtolower(trim($header)))
                ->where('status', 'active')
                ->first();
        }

        return null;
    }

    /**
     * Extrae el primer segmento del host como subdominio.
     *
     *   empresa1.gestioname.app  → empresa1
     *   empresa1.localhost:8000  → empresa1   (puerto descartado por getHost de Symfony,
     *                                           el split por ':' es defensa extra)
     *   localhost                → null        (sin subdominio)
     *   gestioname.app           → 'gestioname' (apex; resolverá a 404 al no existir tenant)
     */
    private function extractSubdomain(string $host): ?string
    {
        $host = explode(':', $host, 2)[0];
        $parts = explode('.', $host);

        if (count($parts) < 2) {
            return null;
        }

        return strtolower($parts[0]);
    }

    /**
     * Fija el search_path al schema del tenant.
     *
     * `SET search_path` NO admite parámetros enlazados (no es un statement parametrizable),
     * por lo que el nombre del schema se interpola en la SQL. Validamos el identificador
     * como etiqueta DNS estricta (idéntico a lo permitido en un subdominio real) y lo
     * entrecomillamos: defensa en profundidad frente a inyección SQL aunque el valor
     * provenga de una columna ya validada en BD.
     */
    private function switchSchema(string $subdomain): void
    {
        if (preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain) !== 1) {
            abort(404, 'Tenant no encontrado.');
        }

        // El cambio de search_path solo aplica a PostgreSQL. En SQLite (tests/local)
        // no hay schemas; se omite sin error.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(sprintf('SET search_path TO "%s", public', $subdomain));
    }
}
