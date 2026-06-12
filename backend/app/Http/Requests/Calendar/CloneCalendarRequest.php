<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class CloneCalendarRequest extends FormRequest
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
            'target_year' => ['required', 'integer', 'between:2000,2100'],
            'name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
