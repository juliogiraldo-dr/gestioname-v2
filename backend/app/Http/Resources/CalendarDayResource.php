<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CalendarDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CalendarDay
 */
class CalendarDayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'schedule_template_id' => $this->schedule_template_id,
            'template' => ScheduleTemplateResource::make($this->whenLoaded('template')),
        ];
    }
}
