<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStripeCheckoutSessionRequest;
use App\Models\Order;
use App\Services\StripeCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Throwable;

class StripeCheckoutController extends Controller
{
    use App\Traits\V1\ApiResponse;
    public function __construct(
        private readonly StripeCheckoutService $checkoutService
    ) {}

    public function verifySession(Request $request, string $sessionId): JsonResponse
    {
        $user = $request->user();

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = Session::retrieve($sessionId);
        } catch (Throwable $e) {
            report($e);

            return self::errorResponse('Unable to verify payment session.', null, 502);
        }

        if ($session->payment_status !== 'paid') {
            return self::errorResponse('Payment has not been completed.', ['payment_status' => $session->payment_status], 402);
        }

        $orderId = $session->metadata->order_id ?? $session->client_reference_id ?? null;
        $order = $orderId
            ? Order::query()->whereKey((int) $orderId)->where('user_id', $user->id)->first()
            : null;

        if (! $order) {
            return self::errorResponse('Order not found for this payment session.', null, 404);
        }

        if ($order->status === 'awaiting_payment') {
            $pi = $session->payment_intent;
            $paymentIntentId = is_string($pi) ? $pi : ($pi->id ?? null);

            DB::transaction(function () use ($order, $paymentIntentId, $session) {
                $order->refresh();
                if ($order->status !== 'awaiting_payment') {
                    return;
                }

                $order->update([
                    'status' => 'placed',
                    'placed_at' => now(),
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'stripe_checkout_session_id' => $session->id,
                ]);
            });

            $order->refresh();
        }

        return self::successResponse('Payment verified successfully.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
        ]);
    }

    public function store(CreateStripeCheckoutSessionRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $order = Order::query()->whereKey($data['order_id'])->where('user_id', $user->id)->first();
        if (! $order) {
            return self::errorResponse('Order not found.', null, 404);
        }

        try {
            $session = $this->checkoutService->createSessionForOrder($order, $user, (float) $data['amount']);
        } catch (\InvalidArgumentException $e) {
            return self::errorResponse($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            report($e);

            return self::errorResponse('Failed to create Stripe checkout session.', null, 500);
        }

        $order->update(['stripe_checkout_session_id' => $session->id]);

        return self::successResponse('Checkout session created. Open checkout_url in your WebView.', [
            'checkout_url' => $session->url,
            'session_id' => $session->id,
            'order_id' => $order->id,
        ]);
    }
}
