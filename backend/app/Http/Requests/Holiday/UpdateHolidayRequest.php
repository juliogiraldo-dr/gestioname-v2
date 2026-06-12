<?php

declare(strict_types=1);

namespace App\Http\Requests\Holiday;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHolidayRequest extends FormRequest
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
        $repeatable = $this->boolean('repeatable');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:nacional,autonomico,local'],
            'repeatable' => ['sometimes', 'required', 'boolean'],
            'day_of_year' => [$repeatable ? 'required' : 'nullable', 'integer', 'between:1,366'],
            'date' => [$repeatable ? 'nullable' : 'sometimes', 'nullable', 'date'],
            'province' => ['nullable', 'string', 'max:100'],
            'locality' => ['nullable', 'string', 'max:100'],
            'work_center_ids' => ['nullable', 'array'],
            'work_center_ids.*' => ['uuid', 'exists:work_centers,id'],
        ];
    }
}
