<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGateway;

class SslcommerzGateway implements PaymentGateway
{
    protected $config;

    public function __construct(array $credentials)
    {
        $this->config = [
            'store_id' => $credentials['store_id'],
            'store_password' => $credentials['store_password'],
            'sandbox_mode' => $credentials['sandbox_mode'],
        ];
    }

    public function processPayment(array $data)
    {
        return $this->config;
    }

    public function verifyPayment(array $data)
    {
        // Implement Sslcommerz payment verification logic here
    }

    public function cancelPayment(array $data)
    {
        // Implement Sslcommerz payment cancellation logic here
    }

    public function refundPayment(array $data)
    {
        // Implement Sslcommerz payment refund logic here
    }
}