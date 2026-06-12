<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeaveRequest
 */
class LeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'leave_type_id' => $this->leave_type_id,
            'date_start' => $this->date_start?->toDateString(),
            'date_end' => $this->date_end?->toDateString(),
            'time_start' => $this->time_start,
            'time_end' => $this->time_end,
            'total_days' => $this->total_days,
            'total_hours' => $this->total_hours,
            'description' => $this->description,
            'document_path' => $this->document_path,
            'status' => $this->status,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_note' => $this->review_note,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id, 'name' => $this->employee->fullName(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
