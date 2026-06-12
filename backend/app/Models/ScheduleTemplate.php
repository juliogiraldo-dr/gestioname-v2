<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plantilla de horario (fijo/flexible/libre).
 *
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property string $type
 * @property int $year
 */
class ScheduleTemplate extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id', 'name', 'color', 'type', 'year', 'tolerance_minutes',
        'flex_start_min', 'flex_start_max', 'flex_hours_day',
        'free_hours_daily', 'free_hours_weekly', 'free_hours_monthly', 'free_hours_annual',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'tolerance_minutes' => 'integer',
            'flex_hours_day' => 'float',
            'free_hours_daily' => 'float',
            'free_hours_weekly' => 'float',
            'free_hours_monthly' => 'float',
            'free_hours_annual' => 'float',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return HasMany<ScheduleTimeRange, $this> */
    public function timeRanges(): HasMany
    {
        return $this->hasMany(ScheduleTimeRange::class)->orderBy('sort_order');
    }

    /**
     * Horas de trabajo que representa esta plantilla en un día.
     * Para `fijo` suma los tramos; para `flexible`/`libre` usa las horas configuradas.
     */
    public function dailyHours(): float
    {
        return match ($this->type) {
            'fijo' => $this->timeRanges->sum(fn (ScheduleTimeRange $r) => $r->durationHours()),
            'flexible' => (float) ($this->flex_hours_day ?? 0),
            'libre' => (float) ($this->free_hours_daily ?? 0),
            default => 0.0,
        };
    }
}
