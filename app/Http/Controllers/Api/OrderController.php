<?php

namespace App\Http\Controllers\Api;

use App\Actions\Api\Order\StoreOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class OrderController extends Controller
{
    use ApiResponse;
    
    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $order = $order->load(['items.meal', 'address']);

        return $this->success('Order retrieved successfully', $this->formatOrder($order));
    }

    /**
     * Create a new order.
     */
    public function store(StoreOrderRequest $request, StoreOrderAction $action): JsonResponse
    {
        try {
            $order = $action->execute($request->validated(), $request->user());

            return $this->success('Order created successfully', $this->formatOrder($order));
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Failed to create order', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Process payment for card orders.
     */
    private function processPayment($user, array $validated, float $total): array
    {
        if ($validated['payment_method'] !== 'card') {
            return ['success' => true];
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        if (! $user->stripe_customer_id) {
            return [
                'success' => false,
                'response' => [
                    'success' => false,
                    'message' => 'Stripe customer not found. Please add a payment method first.',
                ],
            ];
        }

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($total * 100),
                'currency' => 'usd',
                'customer' => $user->stripe_customer_id,
                'payment_method' => $validated['payment_method_id'],
                'off_session' => true,
                'confirm' => true,
            ]);

            if ($paymentIntent->status !== 'succeeded') {
                return [
                    'success' => false,
                    'response' => [
                        'success' => false,
                        'message' => 'Payment failed: '.$paymentIntent->status,
                    ],
                ];
            }

            return $this->success('Payment processed successfully', ['stripe_payment_intent_id' => $paymentIntent->id]);
        } catch (\Exception $e) {
            return $this->error('Payment processing failed: '.$e->getMessage(), null, 402);
        }
    }

    /**
     * Get all user orders.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $orders = Order::with(['items.meal.category', 'items.meal.subcategory', 'address'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return $this->formatOrder($order);
                });

            return $this->success('Orders retrieved successfully', [
                'orders' => $orders,
                'total_count' => $orders->count(),
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve orders', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Track the last order with status positions.
     */
    public function track(Request $request): JsonResponse
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
                return response()->json([
                    'success' => true,
                    'message' => 'Order is waiting for payment. Complete checkout to continue.',
                    'data' => [
                        'order' => $this->formatOrder($order),
                        'awaiting_payment' => true,
                        'tracking' => null,
                    ],
                ]);
            }

            return $this->success('Order tracking retrieved successfully', [
                'order' => $this->formatOrder($order),
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

    /**
     * Format order data for response.
     */
    private function formatOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'payment_method' => $order->payment_method,
            'stripe_payment_intent_id' => $order->stripe_payment_intent_id,
            'delivery_type' => $order->delivery_type,
            'status' => $order->status,
            'status_position' => $order->status_position,
            'status_description' => $order->status_description,
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'meal' => [
                        'id' => $item->meal->id,
                        'title' => $item->meal->title,
                        'slug' => $item->meal->slug,
                        'image_url' => $item->meal->image_url,
                        ...$item->meal->getApiPriceAttributes(),
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
            'address' => $order->address ? [
                'id' => $order->address->id,
                'label' => $order->address->label,
                'full_name' => $order->address->full_name,
                'phone' => $order->address->phone,
                'country_code' => $order->address->country_code,
                'street_address' => $order->address->street_address,
                'building_number' => $order->address->building_number,
                'floor' => $order->address->floor,
                'apartment' => $order->address->apartment,
                'landmark' => $order->address->landmark,
                'city' => $order->address->city,
                'state' => $order->address->state,
                'postal_code' => $order->address->postal_code,
                'country' => $order->address->country,
                'full_address' => $order->address->full_address,
                'latitude' => $order->address->latitude,
                'longitude' => $order->address->longitude,
            ] : null,
            'subtotal' => $order->subtotal,
            'tax' => $order->tax,
            'discount' => $order->discount,
            'shipping_fee' => (float) ($order->shipping_fee ?? 0),
            'total' => $order->total,
            'notes' => $order->notes,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'placed_at' => $order->placed_at,
            'processing_at' => $order->processing_at,
            'shipping_at' => $order->shipping_at,
            'out_for_delivery_at' => $order->out_for_delivery_at,
            'delivered_at' => $order->delivered_at,
            'estimated_delivery_time' => $order->estimated_delivery_time,
            'special_note' => $order->special_note,
            'schedule_delivery' => $order->schedule_delivery,
            'delivery_speed' => $order->delivery_speed,
        ];
    }
}
