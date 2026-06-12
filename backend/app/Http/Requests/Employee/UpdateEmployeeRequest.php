<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `company_id` es inmutable (un empleado no cambia de empresa).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'work_center_id' => ['nullable', 'uuid', 'exists:work_centers,id'],
            'agreement_id' => ['nullable', 'uuid', 'exists:agreements,id'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'second_last_name' => ['nullable', 'string', 'max:100'],
            'treatment' => ['nullable', 'in:sr,sra,dr,dra'],
            'dni' => ['nullable', 'string', 'max:15'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'clock_code' => ['nullable', 'digits:8', Rule::unique('employees', 'clock_code')->ignore($this->route('employee'))],
            'exempt_from_clock' => ['boolean'],
            'department' => ['nullable', 'string', 'max:100'],
            'job_position' => ['nullable', 'string', 'max:100'],
            'job_category' => ['nullable', 'string', 'max:100'],
            'employment_status' => ['nullable', 'in:active,inactive,leave'],
            'hire_date' => ['nullable', 'date'],
            'email_company' => ['nullable', 'email', 'max:255'],
            'phone_company' => ['nullable', 'string', 'max:20'],
            'mobile_company' => ['nullable', 'string', 'max:20'],
            'email_personal' => ['nullable', 'email', 'max:255'],
            'phone_personal' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'iban' => ['nullable', 'string', 'max:34'],
            'vehicle_plate' => ['nullable', 'string', 'max:15'],
            'notes' => ['nullable', 'string'],
            'allowed_ips' => ['nullable', 'array'],
            'allowed_ips.*.ip_address' => ['required', 'ip'],
            'allowed_ips.*.description' => ['nullable', 'string', 'max:100'],
        ];
    }
}
