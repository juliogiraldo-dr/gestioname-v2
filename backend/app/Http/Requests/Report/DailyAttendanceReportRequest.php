<?php

declare(strict_types=1);

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class DailyAttendanceReportRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
            'company_id' => ['nullable', 'uuid'],
            'work_center_id' => ['nullable', 'uuid'],
            'format' => ['nullable', 'in:json,pdf'],
        ];
    }
}
