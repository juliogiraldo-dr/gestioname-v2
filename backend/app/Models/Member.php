<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Socio de una entidad. `dni` cifrado (LOPD/GDPR).
 *
 * @property string $id
 * @property string $entity_id
 * @property string|null $member_type_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string $status
 */
class Member extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'entity_id', 'member_type_id', 'member_number', 'first_name', 'last_name',
        'dni', 'birth_date', 'address', 'postal_code', 'city', 'phone', 'email',
        'date_join', 'date_leave', 'status', 'user_id', 'notes',
    ];

    /** @var list<string> */
    protected $hidden = ['dni'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dni' => 'encrypted',
            'birth_date' => 'date:Y-m-d',
            'date_join' => 'date:Y-m-d',
            'date_leave' => 'date:Y-m-d',
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** @return BelongsTo<Entity, $this> */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /** @return BelongsTo<MemberType, $this> */
    public function memberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class);
    }

    /** @return HasMany<MemberPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(MemberPayment::class);
    }
}
