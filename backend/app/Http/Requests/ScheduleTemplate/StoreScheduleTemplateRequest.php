<?php

declare(strict_types=1);

namespace App\Http\Requests\ScheduleTemplate;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas según el tipo: `fijo` exige tramos; `flexible`, rango de entrada y horas/día;
     * `libre`, alguna bolsa de horas (validado en el servicio).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'type' => ['required', 'in:fijo,flexible,libre'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'tolerance_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'active' => ['boolean'],
            'free_hours_daily' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'free_hours_weekly' => ['nullable', 'numeric', 'min:0', 'max:168'],
            'free_hours_monthly' => ['nullable', 'numeric', 'min:0'],
            'free_hours_annual' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($type === 'fijo') {
            $rules['time_ranges'] = ['required', 'array', 'min:1'];
            $rules['time_ranges.*.time_start'] = ['required', 'date_format:H:i'];
            $rules['time_ranges.*.time_end'] = ['required', 'date_format:H:i'];
            $rules['time_ranges.*.sort_order'] = ['nullable', 'integer', 'min:0'];
        }

        if ($type === 'flexible') {
            $rules['flex_start_min'] = ['required', 'date_format:H:i'];
            $rules['flex_start_max'] = ['required', 'date_format:H:i'];
            $rules['flex_hours_day'] = ['required', 'numeric', 'min:0', 'max:24'];
        }

        return $rules;
    }
}
