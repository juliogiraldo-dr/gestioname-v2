<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrada del registro de auditoría del super-admin (schema public).
 *
 * @property int $id
 * @property string|null $superadmin_user_id
 * @property string $action
 * @property int|null $tenant_id
 * @property array<string, mixed>|null $details
 */
class SuperAdminAuditLog extends Model
{
    protected $table = 'superadmin_audit_log';

    public const UPDATED_AT = null; // solo created_at

    /** @var list<string> */
    protected $fillable = ['superadmin_user_id', 'action', 'tenant_id', 'target_user_id', 'details', 'ip', 'created_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
