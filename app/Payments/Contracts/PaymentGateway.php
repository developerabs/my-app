<?php

namespace App\Payments\Contracts;

interface PaymentGateway
{
    public function processPayment(array $data);
    public function verifyPayment(array $data);
    public function cancelPayment(array $data);
    public function refundPayment(array $data);
}