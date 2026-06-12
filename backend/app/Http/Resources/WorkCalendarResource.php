<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WorkCalendar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkCalendar
 */
class WorkCalendarResource extends JsonResource
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
            'year' => $this->year,
            'country' => $this->country,
            'province' => $this->province,
            'locality' => $this->locality,
            'description' => $this->description,
            'days_count' => $this->whenCounted('days'),
            'days' => CalendarDayResource::collection($this->whenLoaded('days')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
