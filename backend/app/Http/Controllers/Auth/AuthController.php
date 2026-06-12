<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\MagicLinkRequest;
use App\Http\Requests\Auth\MagicLinkVerifyRequest;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Endpoints de autenticación, bajo el prefijo /api/v1/auth.
 * Todas las rutas pasan por el TenantMiddleware: el usuario se resuelve en el schema del
 * tenant identificado por subdominio.
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    /** POST /auth/login — email + password → token Sanctum. */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->auth->attemptLogin(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return $this->tokenResponse($user, $this->auth->issueToken($user));
    }

    /** POST /auth/magic-link — envía el enlace de acceso (respuesta genérica). */
    public function magicLink(MagicLinkRequest $request): JsonResponse
    {
        $tenant = app('tenant');

        $this->auth->sendMagicLink(
            $request->string('email')->toString(),
            $tenant->subdomain,
        );

        return response()->json([
            'message' => 'Si el email existe, recibirás un enlace de acceso.',
        ]);
    }

    /** POST /auth/magic-link/verify — valida el token y emite un token Sanctum. */
    public function magicLinkVerify(MagicLinkVerifyRequest $request): JsonResponse
    {
        $user = $this->auth->verifyMagicLink($request->string('token')->toString());

        return $this->tokenResponse($user, $this->auth->issueToken($user));
    }

    /** POST /auth/logout — revoca el token actual. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /** POST /auth/refresh — revoca el token actual y emite uno nuevo. */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->tokenResponse($user, $this->auth->rotateToken($user));
    }

    /** GET /auth/me — usuario autenticado + roles. */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->userPayload($request->user()),
        ]);
    }

    /**
     * @param  array{token: string, expires_at: Carbon}  $token
     */
    private function tokenResponse(User $user, array $token): JsonResponse
    {
        return response()->json([
            'data' => [
                'token' => $token['token'],
                'token_type' => 'Bearer',
                'expires_at' => $token['expires_at']->toIso8601String(),
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->all(),
        ];
    }
}
