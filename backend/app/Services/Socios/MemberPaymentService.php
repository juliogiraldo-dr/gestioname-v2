<?php

declare(strict_types=1);

namespace App\Services\Socios;

use App\Models\Member;
use App\Models\MemberPayment;

/**
 * Lógica de negocio de pagos de cuota.
 */
final class MemberPaymentService
{
    /**
     * Registra un pago para un socio. Si no se indica importe, usa la cuota de su tipo.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Member $member, array $data, ?string $userId): MemberPayment
    {
        $data['entity_id'] = $member->entity_id;
        $data['created_by'] = $userId;
        $data['amount'] ??= $member->memberType?->fee_amount ?? 0;

        return $member->payments()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MemberPayment $payment, array $data): MemberPayment
    {
        $payment->update($data);

        return $payment;
    }

    public function delete(MemberPayment $payment): void
    {
        $payment->delete();
    }
}
