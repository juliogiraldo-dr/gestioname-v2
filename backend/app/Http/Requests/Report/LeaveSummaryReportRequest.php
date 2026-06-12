<?php

declare(strict_types=1);

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class LeaveSummaryReportRequest extends FormRequest
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
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'company_id' => ['nullable', 'uuid'],
            'work_center_ids' => ['nullable', 'array'],
            'work_center_ids.*' => ['uuid'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['uuid'],
        ];
    }
}
