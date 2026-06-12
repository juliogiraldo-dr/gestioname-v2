<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Construye URLs públicas por tenant. Si `app.tenant_domain` está definido (p. ej.
 * `app.gestioname.es`), cada tenant vive en su subdominio (`demo.app.gestioname.es`);
 * si no, se usa `app.frontend_url` (entornos sin DNS con wildcard).
 */
final class TenantUrl
{
    /** Base del frontend para un tenant concreto. */
    public static function frontend(string $subdomain): string
    {
        $domain = config('app.tenant_domain');

        if (is_string($domain) && trim($domain) !== '') {
            return 'https://'.$subdomain.'.'.trim($domain);
        }

        return rtrim((string) config('app.frontend_url'), '/');
    }

    /** Enlace de acceso por magic link del tenant. */
    public static function magicLink(string $subdomain, string $token): string
    {
        return sprintf(
            '%s/auth/magic-link?token=%s&tenant=%s',
            self::frontend($subdomain),
            urlencode($token),
            urlencode($subdomain),
        );
    }
}
