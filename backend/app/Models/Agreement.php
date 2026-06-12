<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Convenio laboral de una empresa.
 *
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property float $annual_hours
 * @property int $vacation_days
 * @property string $vacation_type
 * @property Carbon|null $vacation_expiry
 * @property Carbon|null $exercise_close
 */
class Agreement extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'name',
        'annual_hours',
        'vacation_days',
        'vacation_type',
        'vacation_expiry',
        'exercise_close',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'annual_hours' => 'float',
            'vacation_days' => 'integer',
            'vacation_expiry' => 'date:Y-m-d',
            'exercise_close' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return HasMany<AgreementLeaveType, $this> */
    public function leaveTypes(): HasMany
    {
        return $this->hasMany(AgreementLeaveType::class);
    }
}
