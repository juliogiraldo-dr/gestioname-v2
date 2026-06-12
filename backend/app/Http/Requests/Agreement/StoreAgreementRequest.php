<?php

declare(strict_types=1);

namespace App\Http\Requests\Agreement;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgreementRequest extends FormRequest
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
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'annual_hours' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'vacation_days' => ['required', 'integer', 'min:0', 'max:366'],
            'vacation_type' => ['required', 'in:laborables,naturales'],
            'vacation_expiry' => ['nullable', 'date'],
            'exercise_close' => ['nullable', 'date'],
        ];
    }
}
