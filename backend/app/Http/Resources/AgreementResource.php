<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Agreement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Agreement
 */
class AgreementResource extends JsonResource
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
            'annual_hours' => $this->annual_hours,
            'vacation_days' => $this->vacation_days,
            'vacation_type' => $this->vacation_type,
            'vacation_expiry' => $this->vacation_expiry?->toDateString(),
            'exercise_close' => $this->exercise_close?->toDateString(),
            'leave_types' => AgreementLeaveTypeResource::collection($this->whenLoaded('leaveTypes')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
