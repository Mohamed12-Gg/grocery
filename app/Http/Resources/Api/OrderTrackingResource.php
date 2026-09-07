<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\AddressResource;
use App\Http\Resources\Api\OrderItemResource;

class OrderTrackingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $trackingStage = match ($this->status) {
            'shipping' => 'arriving',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            default => 'processing',
        };
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'tracking' => [
                'stage' => $trackingStage,
                'stage_label' => match ($trackingStage) {
                    'arriving' => 'Arriving',
                    'out_for_delivery' => 'Out for delivery',
                    'delivered' => 'Delivered',
                    default => 'Processing',
                },
                'positions' => [['stage' => 'arriving', 'label' => 'Arriving', 'completed' => in_array($this->status, ['shipping', 'out_for_delivery', 'delivered']), 'timestamp' => $this->shipping_at?->toIso8601String()], ['stage' => 'out_for_delivery', 'label' => 'Out for delivery', 'completed' => in_array($this->status, ['out_for_delivery', 'delivered']), 'timestamp' => $this->out_for_delivery_at?->toIso8601String()], ['stage' => 'delivered', 'label' => 'Delivered', 'completed' => $this->status === 'delivered', 'timestamp' => $this->delivered_at?->toIso8601String()]],
            ],
            'total' => (float) $this->total,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'estimated_delivery_time' => $this->estimated_delivery_time?->toIso8601String(),
            'address' => $this->when($this->address, fn() => new AddressResource($this->address)),
            'items' => OrderItemResource::collection($this->items),
        ];
    }
}
