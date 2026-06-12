<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago de cuota de un socio en un ejercicio.
 *
 * @property string $id
 * @property string $member_id
 * @property string $entity_id
 * @property int $year
 * @property float $amount
 * @property string $status
 */
class MemberPayment extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'member_id', 'entity_id', 'year', 'amount', 'status',
        'payment_date', 'payment_method', 'reference', 'notes', 'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'amount' => 'float',
            'payment_date' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<Entity, $this> */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
