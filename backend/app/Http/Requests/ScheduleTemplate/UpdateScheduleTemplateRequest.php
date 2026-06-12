<?php

declare(strict_types=1);

namespace App\Http\Requests\ScheduleTemplate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `company_id` es inmutable. Si se envían `time_ranges`, sustituyen a los actuales.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'type' => ['sometimes', 'required', 'in:fijo,flexible,libre'],
            'year' => ['sometimes', 'required', 'integer', 'between:2000,2100'],
            'tolerance_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'active' => ['boolean'],
            'flex_start_min' => ['nullable', 'date_format:H:i'],
            'flex_start_max' => ['nullable', 'date_format:H:i'],
            'flex_hours_day' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'free_hours_daily' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'free_hours_weekly' => ['nullable', 'numeric', 'min:0', 'max:168'],
            'free_hours_monthly' => ['nullable', 'numeric', 'min:0'],
            'free_hours_annual' => ['nullable', 'numeric', 'min:0'],
            'time_ranges' => ['sometimes', 'array'],
            'time_ranges.*.time_start' => ['required_with:time_ranges', 'date_format:H:i'],
            'time_ranges.*.time_end' => ['required_with:time_ranges', 'date_format:H:i'],
            'time_ranges.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
