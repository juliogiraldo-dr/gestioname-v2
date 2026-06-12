<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attendance
 */
class AttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->fullName(),
            ]),
            'milestone_id' => $this->milestone_id,
            'milestone' => $this->whenLoaded('milestone', fn () => [
                'id' => $this->milestone->id,
                'name' => $this->milestone->name,
                'type' => $this->milestone->type,
            ]),
            'clocked_at' => $this->clocked_at?->toIso8601String(),
            'lat' => $this->lat,
            'lng' => $this->lng,
            'ip_address' => $this->ip_address,
            'method' => $this->method,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
