<?php

declare(strict_types=1);

namespace App\Http\Requests\Milestone;

use Illuminate\Foundation\Http\FormRequest;

class StoreMilestoneRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'type' => ['required', 'in:entrada,salida'],
            'show_in_report' => ['boolean'],
            'active' => ['boolean'],
            'work_center_ids' => ['nullable', 'array'],
            'work_center_ids.*' => ['uuid', 'exists:work_centers,id'],
        ];
    }
}
