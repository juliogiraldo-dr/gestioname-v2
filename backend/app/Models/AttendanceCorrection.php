<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Corrección/eliminación de un fichaje (auditoría inmutable ET 34.9). Solo tiene
 * `created_at`; nunca se actualiza ni se borra.
 *
 * @property string $id
 * @property string $attendance_id
 * @property string|null $corrected_by
 * @property Carbon|null $old_clocked_at
 * @property Carbon|null $new_clocked_at
 * @property string $reason
 */
class AttendanceCorrection extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'attendance_id', 'corrected_by', 'old_clocked_at', 'new_clocked_at', 'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_clocked_at' => 'datetime',
            'new_clocked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Attendance, $this> */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /** @return BelongsTo<User, $this> */
    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}
