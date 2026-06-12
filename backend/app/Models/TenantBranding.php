<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Personalización visual (marca blanca) de un tenant. Reside en `public`.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string|null $logo_path
 * @property string|null $primary_color
 * @property string|null $app_name
 */
class TenantBranding extends Model
{
    protected $table = 'tenant_branding';

    /** @var list<string> */
    protected $fillable = ['tenant_id', 'logo_path', 'logo_file', 'primary_color', 'app_name', 'font'];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
