<?php

declare(strict_types=1);

namespace App\Http\Requests\Entity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntityRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:pena,ampa,asociacion_cultural,vecinal,club,cofradia,otro'],
            'cif' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo_path' => ['nullable', 'string'],
            'opening_balance' => ['sometimes', 'numeric'],
            'fiscal_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ];
    }
}
