<?php

declare(strict_types=1);

namespace App\Http\Requests\LeaveType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'type' => ['sometimes', 'required', 'in:ausencia,presencia'],
            'count_in' => ['sometimes', 'required', 'in:dias,horas'],
            'subtracts_vacation' => ['boolean'],
            'requires_document' => ['boolean'],
            'visible_to_employee' => ['boolean'],
            'max_days' => ['nullable', 'integer', 'min:0', 'max:366'],
            'max_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
        ];
    }
}
