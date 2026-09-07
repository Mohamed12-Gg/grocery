<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\ProfileUserResource;
use App\Http\Resources\Api\AddressResource;
use App\Http\Resources\Api\OrderSummaryResource;
use App\Http\Resources\Api\OrderTrackingResource;
use App\Http\Resources\Api\NotificationResource;
use App\Http\Resources\Api\WishlistItemResource;
use App\Http\Resources\Api\SessionResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $orders = $this->resource['orders'];
        $notifications = $this->resource['notifications'];
        $sessions = $this->resource['sessions'];

        $inProgressOrders = $orders->whereNotIn('status', ['cancelled', 'delivered']);

        return [
            'me' => new ProfileUserResource($user),

            'addresses' => AddressResource::collection($this->resource['addresses']),

            'order_history' => [
                'orders' => OrderSummaryResource::collection($orders),

                'ordered_at' => $orders->map(fn($order) => $order->placed_at ?? $order->created_at)->values(),
            ],

            'in_progress_orders' => OrderTrackingResource::collection($inProgressOrders),

            'order_notifications' => NotificationResource::collection($notifications),

            'settings' => [
                'privacy_and_security' => [
                    'active_sessions' => SessionResource::collection($sessions),

                    'change_password' => [
                        'available' => true,
                    ],

                    'change_username' => [
                        'available' => true,
                    ],
                ],
            ],

            'wishlist' => WishlistItemResource::collection($user->favorites),
        ];
    }
}
