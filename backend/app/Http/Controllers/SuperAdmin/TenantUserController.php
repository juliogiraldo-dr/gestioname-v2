<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\SuperAdmin\AuditLogger;
use App\Support\TenantSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de los usuarios de un tenant desde el super-admin. Todas las operaciones
 * se ejecutan dentro del schema del tenant indicado.
 */
class TenantUserController extends Controller
{
    private const ROLES = ['super-admin', 'admin', 'rrhh-coordinator', 'operator', 'employee', 'member'];

    public function __construct(
        private readonly AuthService $auth,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Tenant $tenant): JsonResponse
    {
        $users = $this->inTenant($tenant, fn () => User::query()->with('roles')->orderBy('name')->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'roles' => $u->getRoleNames()->all(),
            'active' => $u->active,
            'last_login_at' => $u->last_login_at?->toIso8601String(),
        ])->all());

        return response()->json(['data' => $users]);
    }

    public function resetPassword(Tenant $tenant, string $userId): JsonResponse
    {
        $result = $this->inTenant($tenant, function () use ($tenant, $userId) {
            $user = User::query()->findOrFail($userId);

            return ['email' => $user->email, 'link' => $this->auth->issueMagicLink($user->email, $tenant->subdomain, 15)];
        });

        $this->audit->record('reset_password', $tenant, ['target_email' => $result['email']], $userId);

        return response()->json(['data' => [
            'email' => $result['email'],
            'magic_link' => $result['link']['url'] ?? null,
            'expires_at' => $result['link']['expires_at'] ?? null,
        ]]);
    }

    public function changeRole(Request $request, Tenant $tenant, string $userId): JsonResponse
    {
        $data = $request->validate(['role' => ['required', 'in:'.implode(',', self::ROLES)]]);

        $this->inTenant($tenant, function () use ($userId, $data) {
            User::query()->findOrFail($userId)->syncRoles([$data['role']]);
        });

        $this->audit->record('change_role', $tenant, ['role' => $data['role']], $userId);

        return response()->json(['data' => ['role' => $data['role']]]);
    }

    public function toggleActive(Tenant $tenant, string $userId): JsonResponse
    {
        $active = $this->inTenant($tenant, function () use ($userId) {
            $user = User::query()->findOrFail($userId);
            $user->update(['active' => ! $user->active]);

            return $user->active;
        });

        $this->audit->record($active ? 'activate_user' : 'deactivate_user', $tenant, [], $userId);

        return response()->json(['data' => ['active' => $active]]);
    }

    /**
     * Ejecuta una operación con el schema del tenant activo y restaura `public`.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function inTenant(Tenant $tenant, callable $callback): mixed
    {
        TenantSchema::use($tenant->subdomain);
        try {
            return $callback();
        } finally {
            TenantSchema::usePublic();
        }
    }
}
