<?php

declare(strict_types=1);

namespace App\Http\Requests\WorkCenter;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkCenterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'timezone' => ['nullable', 'timezone:all'],
            'location_required' => ['boolean'],
            'agreement_ids' => ['array'],
            'agreement_ids.*' => ['uuid', 'exists:agreements,id'],
        ];
    }
}
