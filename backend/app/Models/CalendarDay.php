<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Día de un calendario con su plantilla de horario asignada.
 *
 * @property string $id
 * @property string $calendar_id
 * @property Carbon $date
 * @property string $schedule_template_id
 */
class CalendarDay extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = ['calendar_id', 'date', 'schedule_template_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<WorkCalendar, $this> */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(WorkCalendar::class, 'calendar_id');
    }

    /** @return BelongsTo<ScheduleTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }
}
