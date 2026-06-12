<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Borrado de días del calendario. Sin parámetros = borra todos los días.
 * Con `date_from`/`date_to` borra el rango; con `dates[]` borra días concretos.
 */
class ClearCalendarRequest extends FormRequest
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
            'date_from' => ['nullable', 'date_format:Y-m-d', 'required_with:date_to'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'required_with:date_from', 'after_or_equal:date_from'],
            'dates' => ['nullable', 'array'],
            'dates.*' => ['date_format:Y-m-d'],
        ];
    }
}
