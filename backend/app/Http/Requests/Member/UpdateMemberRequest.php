<?php

declare(strict_types=1);

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
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
            'member_type_id' => ['nullable', 'uuid', 'exists:member_types,id'],
            'member_number' => ['nullable', 'string', 'max:20'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:200'],
            'dni' => ['nullable', 'string', 'max:15'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'date_join' => ['nullable', 'date_format:Y-m-d'],
            'date_leave' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'in:activo,baja_voluntaria,baja_impagada,honor,pendiente'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
