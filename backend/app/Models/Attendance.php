<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Fichaje (registro de jornada). Inmutable salvo corrección trazada; borrado lógico.
 *
 * @property string $id
 * @property string $employee_id
 * @property string $milestone_id
 * @property Carbon $clocked_at
 * @property string $method
 */
class Attendance extends Model
{
    use HasUuids, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'employee_id', 'milestone_id', 'clocked_at', 'lat', 'lng', 'ip_address', 'method',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clocked_at' => 'datetime',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<AttendanceMilestone, $this> */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(AttendanceMilestone::class, 'milestone_id');
    }

    /** @return HasMany<AttendanceCorrection, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
