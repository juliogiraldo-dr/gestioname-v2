<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\MagicLinkToken;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Lógica de autenticación del tenant activo: credenciales, emisión de tokens Sanctum y
 * flujo de magic link. Se asume que el TenantMiddleware ya fijó el schema correcto.
 */
final class AuthService
{
    public const TOKEN_TTL_DAYS = 30;

    public const MAGIC_LINK_TTL_MINUTES = 15;

    private const TOKEN_NAME = 'api';

    /**
     * Verifica email + contraseña. Lanza ValidationException (422) si fallan.
     */
    public function attemptLogin(string $email, string $password): User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || $user->password === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        $this->assertActive($user);

        return $user;
    }

    /** @throws ValidationException si el usuario está desactivado. */
    private function assertActive(User $user): void
    {
        if ($user->active === false) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está desactivada. Contacta con el administrador.'],
            ]);
        }
    }

    /**
     * Emite un token Sanctum con caducidad. Devuelve el token en claro y su expiración.
     *
     * @return array{token: string, expires_at: Carbon}
     */
    public function issueToken(User $user): array
    {
        $expiresAt = Carbon::now()->addDays(self::TOKEN_TTL_DAYS);

        $user->forceFill(['last_login_at' => Carbon::now()])->saveQuietly();

        $token = $user->createToken(self::TOKEN_NAME, ['*'], $expiresAt);

        return [
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Revoca el token actual del usuario y emite uno nuevo.
     *
     * @return array{token: string, expires_at: Carbon}
     */
    public function rotateToken(User $user): array
    {
        $current = $user->currentAccessToken();
        if ($current !== null) {
            $current->delete();
        }

        return $this->issueToken($user);
    }

    /**
     * Genera y envía un magic link. Por seguridad NO revela si el email existe:
     * siempre devuelve sin error (el controlador responde 200 genérico).
     */
    public function sendMagicLink(string $email, string $subdomain): void
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return;
        }

        $plain = Str::random(64);

        MagicLinkToken::create([
            'email' => $email,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => Carbon::now()->addMinutes(self::MAGIC_LINK_TTL_MINUTES),
        ]);

        $user->notify(new MagicLinkNotification($plain, $subdomain));
    }

    /**
     * Genera un magic link, lo envía por email y DEVUELVE el enlace (para que el
     * super-admin lo copie). Devuelve null si el email no existe en el tenant.
     *
     * @return array{token: string, url: string, expires_at: Carbon}|null
     */
    public function issueMagicLink(string $email, string $subdomain, int $ttlMinutes = self::MAGIC_LINK_TTL_MINUTES): ?array
    {
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            return null;
        }

        $plain = Str::random(64);
        $expiresAt = Carbon::now()->addMinutes($ttlMinutes);

        MagicLinkToken::create([
            'email' => $email,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => $expiresAt,
        ]);

        $user->notify(new MagicLinkNotification($plain, $subdomain));

        $url = sprintf(
            '%s/auth/magic-link?token=%s&tenant=%s',
            rtrim((string) config('app.frontend_url'), '/'),
            urlencode($plain),
            urlencode($subdomain),
        );

        return ['token' => $plain, 'url' => $url, 'expires_at' => $expiresAt];
    }

    /**
     * Valida un magic link y devuelve el usuario. Marca el token como usado (un solo uso).
     * Lanza ValidationException (422) si el token no es válido o ha caducado.
     */
    public function verifyMagicLink(string $plainToken): User
    {
        $record = MagicLinkToken::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if ($record === null || ! $record->isUsable()) {
            throw ValidationException::withMessages([
                'token' => ['El enlace no es válido o ha caducado.'],
            ]);
        }

        $user = User::query()->where('email', $record->email)->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'token' => ['El enlace no es válido o ha caducado.'],
            ]);
        }

        $this->assertActive($user);

        $record->forceFill(['used_at' => Carbon::now()])->save();

        return $user;
    }
}
