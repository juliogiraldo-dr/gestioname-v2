<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Tramo horario de una plantilla `fijo`.
 *
 * @property string $id
 * @property string $schedule_template_id
 * @property string $time_start
 * @property string $time_end
 * @property int $sort_order
 */
class ScheduleTimeRange extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'schedule_template_id', 'time_start', 'time_end', 'sort_order',
    ];

    /** @return BelongsTo<ScheduleTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }

    /** Duración del tramo en horas decimales. */
    public function durationHours(): float
    {
        $start = Carbon::createFromFormat('H:i:s', $this->normalize($this->time_start));
        $end = Carbon::createFromFormat('H:i:s', $this->normalize($this->time_end));

        return round($start->diffInMinutes($end) / 60, 2);
    }

    private function normalize(string $time): string
    {
        // Acepta 'HH:MM' o 'HH:MM:SS'.
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
