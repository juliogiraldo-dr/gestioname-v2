<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entidad/asociación independiente a nivel de tenant (no pertenece a una empresa).
 *
 * @property string $id
 * @property string $name
 * @property string $type
 * @property float $opening_balance
 * @property int|null $fiscal_year
 */
class Entity extends Model
{
    use HasUuids;

    protected $table = 'entities';

    /** @var list<string> */
    protected $fillable = [
        'name', 'type', 'cif', 'address', 'phone', 'email', 'logo_path',
        'opening_balance', 'fiscal_year',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'float',
            'fiscal_year' => 'integer',
        ];
    }

    /** @return HasMany<MemberType, $this> */
    public function memberTypes(): HasMany
    {
        return $this->hasMany(MemberType::class);
    }

    /** @return HasMany<Member, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /** @return HasMany<MemberPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(MemberPayment::class);
    }

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** @return HasMany<ExpenseCategory, $this> */
    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }
}
