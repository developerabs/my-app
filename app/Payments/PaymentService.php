<?php

namespace App\Payments;

use App\Payments\Gateways\BkashGateway;
use App\Payments\Gateways\SslcommerzGateway;
use App\Payments\Gateways\StripeGateway;

class PaymentService
{
    public function resolve(string $gatewayType)
    {
        return match ($gatewayType) {
            'bkash' => app(BkashGateway::class),
            'sslcommerze' => app(SslcommerzGateway::class),
            'stripe' => app(StripeGateway::class),
            // Add other gateways here
            default => throw new \Exception("Unsupported payment gateway: $gatewayType"),
        };
    }

    public function initialize($gateway, array $data, $forceLandlord = false)
    {
        $data['forceLandlord'] = $forceLandlord;
        return $this->resolve($gateway)->processPayment($data);
    }

    public function verify($gateway, array $data)
    {
        return $this->resolve($gateway)->verifyPayment($data);
    }
}