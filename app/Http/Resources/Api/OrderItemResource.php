<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meal' => ['id' => $this->meal->id, 'title' => $this->meal->title, 'image_url' => $this->meal->image_url],
            'quantity' => $this->quantity,
            'subtotal' => (float) $this->subtotal,
        ];
    }
}
