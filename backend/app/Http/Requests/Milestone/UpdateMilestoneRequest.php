<?php

declare(strict_types=1);

namespace App\Http\Requests\Milestone;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `company_id` es inmutable: un hito no cambia de empresa.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'type' => ['sometimes', 'required', 'in:entrada,salida'],
            'show_in_report' => ['boolean'],
            'active' => ['boolean'],
            'work_center_ids' => ['nullable', 'array'],
            'work_center_ids.*' => ['uuid', 'exists:work_centers,id'],
        ];
    }
}
