<?php

declare(strict_types=1);

namespace App\Http\Requests\MemberPayment;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberPaymentRequest extends FormRequest
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
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pagado,parcial,pendiente'],
            'payment_date' => ['nullable', 'date_format:Y-m-d'],
            'payment_method' => ['nullable', 'in:efectivo,transferencia,bizum,domiciliacion,otro'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
