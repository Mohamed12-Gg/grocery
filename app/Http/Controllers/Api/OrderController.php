<?php

namespace App\Http\Controllers\Api;

use App\Actions\Api\Order\StoreOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
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
        $data = OrderResource::make($order)->toArray($request);

        return $this->success('Order retrieved successfully', $data);
    }

    /**
     * Create a new order.
     */
    public function store(StoreOrderRequest $request, StoreOrderAction $action): JsonResponse
    {
        try {
            $order = $action->execute($request->validated(), $request->user());

            $data = OrderResource::make($order)->toArray($request);

            return $this->success('Order created successfully', $data);
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
                    return OrderResource::make($order)->toArray($request);
                });

            return $this->success('Orders retrieved successfully', [
                'orders' => $orders,
                'total_count' => $orders->count(),
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve orders', ['error' => $e->getMessage()], 500);
        }
    }
}
