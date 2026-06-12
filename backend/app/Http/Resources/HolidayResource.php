<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Holiday
 */
class HolidayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'repeatable' => $this->repeatable,
            'day_of_year' => $this->day_of_year,
            'date' => $this->date?->toDateString(),
            'province' => $this->province,
            'locality' => $this->locality,
            'work_center_ids' => $this->whenLoaded('workCenters', fn () => $this->workCenters->pluck('id')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
