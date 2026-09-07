<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class ProcessPaymentController extends Controller
{
    use ApiResponse;
    /**
     * Process payment for card orders.
     */
    private function __invoke($user, array $validated, float $total): array
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
}
