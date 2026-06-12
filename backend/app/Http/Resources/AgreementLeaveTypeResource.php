<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AgreementLeaveType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AgreementLeaveType
 */
class AgreementLeaveTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agreement_id' => $this->agreement_id,
            'name' => $this->name,
            'type' => $this->type,
            'count_in' => $this->count_in,
            'subtracts_vacation' => $this->subtracts_vacation,
            'requires_document' => $this->requires_document,
            'visible_to_employee' => $this->visible_to_employee,
            'max_days' => $this->max_days,
            'max_hours' => $this->max_hours,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
