<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    use ApiResponse;

    /**
     * Track the last order with status positions.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $order = Order::where('user_id', $user->id)
                ->whereNotIn('status', ['cancelled', 'delivered'])
                ->with(['items.meal.category', 'items.meal.subcategory', 'address'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $order) {
                return $this->error('No active order found', null, 404);
            }

            if ($order->status === 'awaiting_payment') {
                return $this->success('Order is waiting for payment. Complete checkout to continue.', [
                    'order' => OrderResource::make($order)->toArray($request),
                    'awaiting_payment' => true,
                    'tracking' => null,
                ]);
            }

            return $this->success('Order tracking retrieved successfully', [
                'order' => OrderResource::make($order)->toArray($request),
                'tracking' => [
                    'position' => $order->status_position,
                    'status' => $order->status,
                    'status_description' => $order->status_description,
                    'positions' => [
                        [
                            'position' => 1,
                            'status' => 'placed',
                            'label' => 'Order Placed',
                            'description' => 'Your order has been placed',
                            'completed' => in_array($order->status, ['placed', 'processing', 'shipping', 'out_for_delivery', 'delivered']),
                            'timestamp' => $order->placed_at,
                        ],
                        [
                            'position' => 2,
                            'status' => 'processing',
                            'label' => 'Processing',
                            'description' => 'Your order is being processed',
                            'completed' => in_array($order->status, ['processing', 'shipping', 'out_for_delivery', 'delivered']),
                            'timestamp' => $order->processing_at,
                        ],
                        [
                            'position' => 3,
                            'status' => 'shipping',
                            'label' => 'Shipping',
                            'description' => 'Your order is being shipped',
                            'completed' => in_array($order->status, ['shipping', 'out_for_delivery', 'delivered']),
                            'timestamp' => $order->shipping_at,
                        ],
                        [
                            'position' => 4,
                            'status' => 'out_for_delivery',
                            'label' => 'Out for Delivery',
                            'description' => 'Your order is on the way',
                            'completed' => in_array($order->status, ['out_for_delivery', 'delivered']),
                            'timestamp' => $order->out_for_delivery_at,
                        ],
                        [
                            'position' => 5,
                            'status' => 'delivered',
                            'label' => 'Delivered',
                            'description' => 'Your order has been delivered',
                            'completed' => $order->status === 'delivered',
                            'timestamp' => $order->delivered_at,
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to track order', ['error' => $e->getMessage()], 500);
        }
    }
}
