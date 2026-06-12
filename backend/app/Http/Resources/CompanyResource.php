<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_group_id' => $this->company_group_id,
            'group' => $this->whenLoaded('group', fn () => $this->group === null ? null : [
                'id' => $this->group->id,
                'name' => $this->group->name,
            ]),
            'name' => $this->name,
            'cif' => $this->cif,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_path' => $this->logo_path,
            'work_centers' => WorkCenterResource::collection($this->whenLoaded('workCenters')),
            'milestones' => AttendanceMilestoneResource::collection($this->whenLoaded('milestones')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
