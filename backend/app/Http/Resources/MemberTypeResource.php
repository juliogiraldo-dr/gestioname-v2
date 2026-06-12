<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MemberType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MemberType
 */
class MemberTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_id' => $this->entity_id,
            'name' => $this->name,
            'description' => $this->description,
            'fee_amount' => $this->fee_amount,
            'fee_periodicity' => $this->fee_periodicity,
            'active' => $this->active,
        ];
    }
}
