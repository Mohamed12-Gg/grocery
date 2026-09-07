<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'total' => (float) $this->total,
            'created_at' => $this->created_at,
            'items_count' => $this->whenLoaded('items', fn () => $this->items->sum('quantity')),
        ];
    }
}