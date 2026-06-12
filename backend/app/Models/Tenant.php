<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tenant del sistema. Reside en el schema `public`.
 *
 * El search_path que fija TenantMiddleware siempre incluye `, public`, por lo que
 * este modelo es accesible aunque haya un schema de tenant activo. Los schemas de
 * tenant nunca contienen una tabla `tenants`, así que no hay riesgo de shadowing.
 *
 * @property int $id
 * @property string $name
 * @property string $subdomain
 * @property string|null $custom_domain
 * @property string $plan
 * @property string $status
 */
class Tenant extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'subdomain',
        'custom_domain',
        'plan',
        'plan_id',
        'status',
        'trial_ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime'];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Plan de suscripción. Se llama `subscriptionPlan` (no `plan`) para no chocar con la
     * columna string legacy `plan`.
     *
     * @return BelongsTo<Plan, $this>
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /** @return HasOne<PlanOverride, $this> */
    public function override(): HasOne
    {
        return $this->hasOne(PlanOverride::class);
    }

    /** @return HasOne<TenantBranding, $this> */
    public function branding(): HasOne
    {
        return $this->hasOne(TenantBranding::class);
    }
}
