<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Member
 */
class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_id' => $this->entity_id,
            'member_type_id' => $this->member_type_id,
            'member_number' => $this->member_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'dni' => $this->dni,
            'birth_date' => $this->birth_date?->toDateString(),
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'phone' => $this->phone,
            'email' => $this->email,
            'date_join' => $this->date_join?->toDateString(),
            'date_leave' => $this->date_leave?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'member_type' => MemberTypeResource::make($this->whenLoaded('memberType')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
