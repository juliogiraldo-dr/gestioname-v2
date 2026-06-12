<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de socio con su cuota.
 *
 * @property string $id
 * @property string $entity_id
 * @property string $name
 * @property float $fee_amount
 * @property string $fee_periodicity
 * @property bool $active
 */
class MemberType extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'entity_id', 'name', 'description', 'fee_amount', 'fee_periodicity', 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee_amount' => 'float',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Entity, $this> */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /** @return HasMany<Member, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
