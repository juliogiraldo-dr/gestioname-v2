<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
            'company_group_id' => ['sometimes', 'nullable', 'uuid', 'exists:company_groups,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'cif' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('companies', 'cif')->ignore($this->route('company')),
            ],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
