<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkCenter
 */
class WorkCenterResource extends JsonResource
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
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'timezone' => $this->timezone,
            'location_required' => (bool) $this->location_required,
            'milestones' => AttendanceMilestoneResource::collection($this->whenLoaded('milestones')),
            'agreement_ids' => $this->whenLoaded('agreements', fn () => $this->agreements->pluck('id')->all()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
