<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Calendario laboral anual.
 *
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property int $year
 */
class WorkCalendar extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id', 'name', 'color', 'year', 'country', 'province', 'locality', 'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['year' => 'integer'];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return HasMany<CalendarDay, $this> */
    public function days(): HasMany
    {
        return $this->hasMany(CalendarDay::class, 'calendar_id');
    }

    /** @return BelongsToMany<Employee, $this> */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'calendar_employees', 'calendar_id', 'employee_id');
    }
}
