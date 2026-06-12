<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrgChartNode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrgChartNode
 */
class OrgChartNodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_center_id' => $this->work_center_id,
            'employee_id' => $this->employee_id,
            'parent_id' => $this->parent_id,
            'receives_notifications' => $this->receives_notifications,
            'sort_order' => $this->sort_order,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id, 'name' => $this->employee->fullName(),
            ]),
            'children' => OrgChartNodeResource::collection($this->whenLoaded('children')),
        ];
    }
}
