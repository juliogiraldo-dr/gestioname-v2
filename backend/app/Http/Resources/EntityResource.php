<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Entity
 */
class EntityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'cif' => $this->cif,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_path' => $this->logo_path,
            'opening_balance' => $this->opening_balance,
            'fiscal_year' => $this->fiscal_year,
            'members_count' => $this->whenCounted('members'),
            'member_types' => MemberTypeResource::collection($this->whenLoaded('memberTypes')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
