<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ScheduleTimeRange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ScheduleTimeRange
 */
class ScheduleTimeRangeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'time_start' => $this->time_start,
            'time_end' => $this->time_end,
            'sort_order' => $this->sort_order,
        ];
    }
}
