<?php

declare(strict_types=1);

namespace App\Services\Socios;

use App\Models\Entity;
use App\Models\Member;
use App\Models\MemberPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/**
 * Generación de PDFs del módulo Socios: recibo de pago, ficha, carnet y listado.
 */
final class SocioPdfService
{
    public function receipt(MemberPayment $payment): string
    {
        $payment->loadMissing(['member', 'entity']);

        return Pdf::loadView('socios.receipt', [
            'payment' => $payment,
            'member' => $payment->member,
            'entity' => $payment->entity,
            'number' => $this->receiptNumber($payment),
        ])->output();
    }

    public function memberCard(Member $member): string
    {
        $member->loadMissing(['entity', 'memberType']);

        return Pdf::loadView('socios.card', ['member' => $member, 'entity' => $member->entity])
            ->setPaper([0, 0, 242, 153]) // tarjeta tipo crédito (85,6 x 54 mm aprox)
            ->output();
    }

    public function memberSheet(Member $member): string
    {
        $member->loadMissing(['entity', 'memberType', 'payments']);

        return Pdf::loadView('socios.sheet', [
            'member' => $member,
            'entity' => $member->entity,
            'payments' => $member->payments->sortByDesc('year'),
        ])->output();
    }

    /**
     * @param  Collection<int, Member>  $members
     */
    public function membersList(Entity $entity, Collection $members): string
    {
        return Pdf::loadView('socios.list', ['entity' => $entity, 'members' => $members])
            ->setPaper('a4')
            ->output();
    }

    private function receiptNumber(MemberPayment $payment): string
    {
        return $payment->reference ?: sprintf('REC-%d-%s', $payment->year, strtoupper(substr($payment->id, 0, 6)));
    }
}
