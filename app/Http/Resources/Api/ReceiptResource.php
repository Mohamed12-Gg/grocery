<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'receipt_number' => $this->order_number,

            'invoice_number' => 'INV-' . str_pad(
                $this->id,
                8,
                '0',
                STR_PAD_LEFT
            ),

            'type' => 'receipt',

            'date' => $this->placed_at ?? $this->created_at,
            'payment_date' => $this->placed_at ?? $this->created_at,

            'status' => $this->status,
            'status_description' => $this->status_description,

            'customer' => [
                'id' => $this->user->id,
                'name' => $this->user->full_name
                    ?? $this->user->username
                    ?? 'Customer',
                'firstname' => $this->user->firstname,
                'lastname' => $this->user->lastname,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'country_code' => $this->user->country_code,
            ],

            'delivery_address' => $this->address ? [
                'id' => $this->address->id,
                'label' => $this->address->label,
                'full_name' => $this->address->full_name,
                'phone' => $this->address->phone,
                'country_code' => $this->address->country_code,
                'street_address' => $this->address->street_address,
                'building_number' => $this->address->building_number,
                'floor' => $this->address->floor,
                'apartment' => $this->address->apartment,
                'landmark' => $this->address->landmark,
                'city' => $this->address->city,
                'state' => $this->address->state,
                'postal_code' => $this->address->postal_code,
                'country' => $this->address->country,
                'full_address' => $this->address->full_address,
            ] : null,

            'payment' => [
                'method' => $this->payment_method,
                'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
                'method_display' => $this->getPaymentMethodDisplay(
                    $this->payment_method
                ),
            ],

            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,

                    'meal' => [
                        'id' => $item->meal->id,
                        'title' => $item->meal->title,

                        'category' => $item->meal->category ? [
                            'id' => $item->meal->category->id,
                            'name' => $item->meal->category->name,
                        ] : null,

                        'subcategory' => $item->meal->subcategory ? [
                            'id' => $item->meal->subcategory->id,
                            'name' => $item->meal->subcategory->name,
                        ] : null,
                    ],

                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount_amount' => (float) $item->discount_amount,
                    'subtotal' => (float) $item->subtotal,
                ];
            }),

            'pricing' => [
                'subtotal' => (float) $this->subtotal,
                'tax' => (float) $this->tax,

                'tax_rate' => $this->subtotal > 0
                    ? round(($this->tax / $this->subtotal) * 100, 2)
                    : 0,

                'discount' => (float) $this->discount,
                'total' => (float) $this->total,
            ],

            'delivery' => [
                'type' => $this->delivery_type,
                'estimated_delivery_time' => $this->estimated_delivery_time,
                'placed_at' => $this->placed_at,
                'processing_at' => $this->processing_at,
                'shipping_at' => $this->shipping_at,
                'out_for_delivery_at' => $this->out_for_delivery_at,
                'delivered_at' => $this->delivered_at,
            ],

            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function getPaymentMethodDisplay(?string $method): string
    {
        return match ($method) {
            'cash' => 'Cash on Delivery',
            'card' => 'Credit/Debit Card',
            'stripe' => 'Stripe',
            default => ucfirst($method ?? 'Unknown'),
        };
    }
}