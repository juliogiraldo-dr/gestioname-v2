<?php

declare(strict_types=1);

namespace App\Http\Requests\Holiday;

use Illuminate\Foundation\Http\FormRequest;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `day_of_year` se exige si el festivo es repetible; `date`, si no lo es.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $repeatable = $this->boolean('repeatable');

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:nacional,autonomico,local'],
            'repeatable' => ['required', 'boolean'],
            'day_of_year' => [$repeatable ? 'required' : 'nullable', 'integer', 'between:1,366'],
            'date' => [$repeatable ? 'nullable' : 'required', 'date'],
            'province' => ['nullable', 'string', 'max:100'],
            'locality' => ['nullable', 'string', 'max:100'],
            'work_center_ids' => ['nullable', 'array'],
            'work_center_ids.*' => ['uuid', 'exists:work_centers,id'],
        ];
    }
}
