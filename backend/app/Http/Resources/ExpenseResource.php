<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Expense
 */
class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_id' => $this->entity_id,
            'category_id' => $this->category_id,
            'amount' => $this->amount,
            'date' => $this->date?->toDateString(),
            'description' => $this->description,
            'notes' => $this->notes,
            'receipt_path' => $this->receipt_path,
            'category' => ExpenseCategoryResource::make($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
