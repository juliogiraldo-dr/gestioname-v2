<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ScheduleTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ScheduleTemplate
 */
class ScheduleTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'color' => $this->color,
            'type' => $this->type,
            'year' => $this->year,
            'tolerance_minutes' => $this->tolerance_minutes,
            'flex_start_min' => $this->flex_start_min,
            'flex_start_max' => $this->flex_start_max,
            'flex_hours_day' => $this->flex_hours_day,
            'free_hours_daily' => $this->free_hours_daily,
            'free_hours_weekly' => $this->free_hours_weekly,
            'free_hours_monthly' => $this->free_hours_monthly,
            'free_hours_annual' => $this->free_hours_annual,
            'active' => $this->active,
            'daily_hours' => $this->relationLoaded('timeRanges') || $this->type !== 'fijo' ? $this->dailyHours() : null,
            'time_ranges' => ScheduleTimeRangeResource::collection($this->whenLoaded('timeRanges')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
