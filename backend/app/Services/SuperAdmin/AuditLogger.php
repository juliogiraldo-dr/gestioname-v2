<?php

declare(strict_types=1);

namespace App\Services\SuperAdmin;

use App\Models\SuperAdminAuditLog;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

/**
 * Registra acciones del super-admin en `superadmin_audit_log` (schema public).
 */
final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function record(string $action, ?Tenant $tenant = null, array $details = [], ?string $targetUserId = null): void
    {
        $actor = auth()->user();

        SuperAdminAuditLog::create([
            'superadmin_user_id' => $actor?->getKey(),
            'action' => $action,
            'tenant_id' => $tenant?->id,
            'target_user_id' => $targetUserId,
            'details' => array_merge(['actor_email' => $actor?->email], $details),
            'ip' => request()->ip(),
            'created_at' => Carbon::now(),
        ]);
    }
}
