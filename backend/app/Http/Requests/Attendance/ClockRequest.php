<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ClockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clock_code' => ['required', 'digits:8'],
            'milestone_id' => ['required', 'uuid', 'exists:attendance_milestones,id'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'method' => ['nullable', 'in:web,movil,kiosk'],
            'work_mode' => ['nullable', 'in:presencial,teletrabajo'],
        ];
    }
}
