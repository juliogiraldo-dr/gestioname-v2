<?php

declare(strict_types=1);

namespace App\Http\Requests\Agreement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `company_id` es inmutable: un convenio no cambia de empresa.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'annual_hours' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999.99'],
            'vacation_days' => ['sometimes', 'required', 'integer', 'min:0', 'max:366'],
            'vacation_type' => ['sometimes', 'required', 'in:laborables,naturales'],
            'vacation_expiry' => ['nullable', 'date'],
            'exercise_close' => ['nullable', 'date'],
        ];
    }
}
