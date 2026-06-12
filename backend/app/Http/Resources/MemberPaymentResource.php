<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MemberPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MemberPayment
 */
class MemberPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'full_name' => $this->member->fullName(),
            ]),
            'entity_id' => $this->entity_id,
            'year' => $this->year,
            'amount' => $this->amount,
            'status' => $this->status,
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
