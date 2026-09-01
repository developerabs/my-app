<?php

namespace App\Payments\Gateways;

use App\Models\landlord\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Resolvers\CredentialResolver;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Stripe;

class StripeGateway implements PaymentGateway
{
    protected string $gatewayName = 'stripe';
    public function processPayment(array $data)
    {
        $credentials = CredentialResolver::resolve($this->gatewayName, $data['forceLandlord'] ?? false);

        Stripe::setApiKey($credentials['SECRET_KEY']);

        $payment = Payment::create([
            'invoice_number' => $data['invoice_number'],
            'tenant_id' => $data['tenant'],
            'base_amount' => $data['base_amount'],
            'base_currency' => $data['base_currency'],
            'pay_amount' => $data['pay_amount'],
            'pay_currency' => $data['pay_currency'],
            'exchange_rate' => $data['exchange_rate'],
            'payment_method' => 'online',
            'payment_id' => '',
            'gateway' => $this->gatewayName,
            'credential_owner' => ($data['forceLandlord'] ?? false) ? 'landlord' : 'tenant',
            'paid_for' => $data['paid_for'],
        ]);

        $session = CheckoutSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($data['currency'] ?? 'usd'),
                    'product_data' => [
                        'name' => $data['package'] ?? $data['item_name'],
                    ],
                    'unit_amount' => intval($data['pay_amount'] * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.stripe.success', ['payment' => $payment->id]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.stripe.cancel', ['payment' => $payment->id]),
            'metadata' => [
                'tenant_id' => $data['tenant'],
                'invoice_number' => $data['invoice_number'],
                'subscription_type' => $data['subscription_type'],
                'paid_for' => $data['paid_for'],
            ],
        ]);

        return redirect($session->url);

    }

    public function verifyPayment(array $data)
    {
        $credentials = CredentialResolver::resolve($this->gatewayName, $data['forceLandlord'] ?? false);
        Stripe::setApiKey($credentials['SECRET_KEY']);

        $session = CheckoutSession::retrieve($data['session_id']);

        return $session;
    }

    public function cancelPayment(array $data)
    {
        // Implement Stripe payment cancellation logic here
    }

    public function refundPayment(array $data)
    {
        // Implement Stripe payment refund logic here
    }
}