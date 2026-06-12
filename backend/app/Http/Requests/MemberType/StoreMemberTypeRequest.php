<?php

declare(strict_types=1);

namespace App\Http\Requests\MemberType;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
            'fee_periodicity' => ['nullable', 'in:anual,semestral,trimestral,mensual'],
            'active' => ['boolean'],
        ];
    }
}
