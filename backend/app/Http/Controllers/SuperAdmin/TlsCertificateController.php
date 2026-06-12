<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Gestión del certificado TLS wildcard de la plataforma (`*.app.gestioname.es`).
 * El cert se genera fuera (Plesk · Let's Encrypt DNS-01) y se sube aquí; Traefik lo
 * recoge tras reiniciar el frontend. Solo super-admin.
 */
class TlsCertificateController extends Controller
{
    private const DIR = 'tls';

    private const CRT = 'tls/wildcard.crt';

    private const KEY = 'tls/wildcard.key';

    /** Info del certificado actual, o null si no hay ninguno subido. */
    public function show(): JsonResponse
    {
        if (! Storage::exists(self::CRT)) {
            return response()->json(['data' => null]);
        }

        $pem = Storage::get(self::CRT);
        $info = $this->parse((string) $pem);

        return response()->json(['data' => $info]);
    }

    /** Verifica y guarda un nuevo certificado wildcard + su clave privada. */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'certificate' => ['required', 'string'],
            'private_key' => ['required', 'string'],
        ]);

        $cert = trim($data['certificate']);
        $key = trim($data['private_key']);

        // 1) El certificado debe ser X.509 válido.
        $parsed = @openssl_x509_parse($cert);
        if ($parsed === false) {
            throw ValidationException::withMessages([
                'certificate' => ['El certificado no es un X.509 válido.'],
            ]);
        }

        // 2) La clave privada debe corresponder al certificado.
        if (@openssl_x509_check_private_key($cert, $key) !== true) {
            throw ValidationException::withMessages([
                'private_key' => ['La clave privada no corresponde al certificado.'],
            ]);
        }

        Storage::makeDirectory(self::DIR);
        Storage::put(self::CRT, $cert);
        Storage::put(self::KEY, $key);

        return response()->json(['data' => $this->parse($cert)]);
    }

    /**
     * Extrae los datos legibles de un certificado PEM.
     *
     * @return array{domain: ?string, cn: ?string, san: ?string, valid_until: ?string, days_left: ?int}
     */
    private function parse(string $cert): array
    {
        $parsed = openssl_x509_parse($cert) ?: [];
        $cn = $parsed['subject']['CN'] ?? null;
        $san = $parsed['extensions']['subjectAltName'] ?? null;
        $validToTs = $parsed['validTo_time_t'] ?? null;
        $validUntil = is_int($validToTs) ? Carbon::createFromTimestamp($validToTs) : null;

        return [
            'domain' => $cn,
            'cn' => $cn,
            'san' => $san,
            'valid_until' => $validUntil?->toIso8601String(),
            'days_left' => $validUntil !== null ? (int) Carbon::now()->diffInDays($validUntil, false) : null,
        ];
    }
}
