<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan de suscripción (schema public).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property float $price_monthly
 * @property float|null $price_yearly
 * @property bool $is_public
 * @property array<string, int|null> $limits
 * @property list<string> $modules_allowed
 */
class Plan extends Model
{
    /** @var list<string> */
    protected $fillable = ['name', 'slug', 'price_monthly', 'price_yearly', 'is_public', 'limits', 'modules_allowed'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_monthly' => 'float',
            'price_yearly' => 'float',
            'is_public' => 'boolean',
            'limits' => 'array',
            'modules_allowed' => 'array',
        ];
    }

    /** @return HasMany<Tenant, $this> */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
