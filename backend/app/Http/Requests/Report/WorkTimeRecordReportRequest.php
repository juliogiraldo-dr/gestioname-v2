<?php

declare(strict_types=1);

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class WorkTimeRecordReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El rol se controla en la ruta.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'company_id' => ['nullable', 'uuid'],
            'work_center_ids' => ['nullable', 'array'],
            'work_center_ids.*' => ['uuid'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['uuid'],
            'format' => ['required', 'in:excel,pdf'],
            'options' => ['nullable', 'array'],
            'options.include_work_center' => ['boolean'],
            'options.include_delays' => ['boolean'],
            'options.include_geolocation' => ['boolean'],
            'options.include_method' => ['boolean'],
            'options.decimal_format' => ['boolean'],
            'options.split_by_employee' => ['boolean'],
            'options.password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
