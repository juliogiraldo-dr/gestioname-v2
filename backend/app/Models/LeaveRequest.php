<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Solicitud de ausencia/presencia.
 *
 * @property string $id
 * @property string $employee_id
 * @property string $leave_type_id
 * @property Carbon $date_start
 * @property Carbon $date_end
 * @property float|null $total_days
 * @property string $status
 */
class LeaveRequest extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'employee_id', 'leave_type_id', 'date_start', 'date_end', 'time_start', 'time_end',
        'total_days', 'total_hours', 'description', 'document_path', 'status',
        'reviewed_by', 'reviewed_at', 'review_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_start' => 'date:Y-m-d',
            'date_end' => 'date:Y-m-d',
            'total_days' => 'float',
            'total_hours' => 'float',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<AgreementLeaveType, $this> */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(AgreementLeaveType::class, 'leave_type_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
