<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AttendanceCorrection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AttendanceCorrection
 */
class AttendanceCorrectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_id' => $this->attendance_id,
            'corrected_by' => $this->corrected_by,
            'old_clocked_at' => $this->old_clocked_at?->toIso8601String(),
            'new_clocked_at' => $this->new_clocked_at?->toIso8601String(),
            'reason' => $this->reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
