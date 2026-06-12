<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Override de límites/módulos para un tenant concreto (schema public).
 *
 * @property int $tenant_id
 * @property array<string, int|null>|null $limits
 * @property list<string>|null $modules_allowed
 */
class PlanOverride extends Model
{
    /** @var list<string> */
    protected $fillable = ['tenant_id', 'limits', 'modules_allowed'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['limits' => 'array', 'modules_allowed' => 'array'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
