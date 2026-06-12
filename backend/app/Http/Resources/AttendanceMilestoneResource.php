<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AttendanceMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AttendanceMilestone
 */
class AttendanceMilestoneResource extends JsonResource
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
            'description' => $this->description,
            'color' => $this->color,
            'type' => $this->type,
            'show_in_report' => $this->show_in_report,
            'active' => $this->active,
            'work_center_ids' => $this->whenLoaded('workCenters', fn () => $this->workCenters->pluck('id')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
