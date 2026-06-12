<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class FillQuickRequest extends FormRequest
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
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],   // 1=lunes ... 7=domingo
            'months' => ['required', 'array', 'min:1'],
            'months.*' => ['integer', 'between:1,12'],
            'schedule_template_id' => ['required', 'uuid', 'exists:schedule_templates,id'],
            'include_holidays' => ['boolean'],
        ];
    }
}
