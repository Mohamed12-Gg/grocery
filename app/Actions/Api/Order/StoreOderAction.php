<?php

namespace App\Actions\Api\Order;

class StoreOrderAction
{
    public function execute(array $validated, $user)
    {
        // Logic to store the order
        // This is a placeholder for the actual implementation
        // Get user's active cart
        $cart = $user->activeCart()->with('items.meal')->first();

        if (!$cart || $cart->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty. Please add items to your cart before placing an order.',
            ], 400);
        }

        // Validate and process items from cart
        $itemsResult = $this->validateAndProcessCartItems($cart->items);
        if (!$itemsResult['success']) {
            return response()->json($itemsResult['response'], 400);
        }

        $items = $itemsResult['items'];

        // Calculate totals and shipping (use cart totals; add shipping for delivery)
        $cart->calculateTotals();
        $shippingService = app(ShippingService::class);
        $shippingFee = $shippingService->calculateShippingFee((float) $cart->subtotal, $validated['delivery_type']);
        $totals = [
            'subtotal' => $cart->subtotal,
            'tax' => $cart->tax,
            'discount' => $cart->discount,
            'shipping_fee' => $shippingFee,
            'total' => (float) $cart->subtotal + (float) $cart->tax + $shippingFee,
        ];
        $total = $totals['total'];

        DB::beginTransaction();

        $stripePaymentIntentId = $paymentResult['stripe_payment_intent_id'] ?? null;

        // Create order
        $order = $this->createOrder($user, $validated, $totals['subtotal'], $totals, $stripePaymentIntentId);

        // Create order items and update stock
        $this->createOrderItems($order, $items);

        // Clear user's active cart
        $this->clearUserCart($user);


        if (isset($validated['special_note_id'])) {
            OrderNote::create([
                'order_id' => $order->id,
                'special_note_id' => $validated['special_note_id'],
                'notes' => $validated['notes'] ?? null,
            ]);
        }
        if (isset($validated['notes'])) {
            OrderNote::create([
                'order_id' => $order->id,
                'special_note_id' => null,
                'notes' => $validated['notes'],
            ]);
        }
        DB::commit();

        $order->load(['items.meal', 'address']);
    }
}