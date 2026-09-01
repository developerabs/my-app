<?php

namespace App\Payments\Gateways;

use App\Models\landlord\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Resolvers\CredentialResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BkashGateway implements PaymentGateway
{
    protected string $gatewayName = 'bkash';
    /**
     * Process a payment
     */
    public function processPayment(array $data)
    {
        $credentials = CredentialResolver::resolve($this->gatewayName, $data['forceLandlord'] ?? false);
        $token = $this->generateToken($credentials);
        $payload = $this->generatePayload($data);

        $baseUrl = baseUrlFormat($credentials['base_url']);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'x-app-key' => $credentials['app_key'],
        ])->post("{$baseUrl}/checkout/create", $payload);

        if (!$response->successful()) {
            throw new \Exception('bKash payment creation failed: ' . $response->body());
        }

        $respData = $response->json();

        if (empty($respData['bkashURL'])) {
            throw new \Exception('bKash payment URL missing in response');
        }

        $payment = Payment::create([
            'invoice_number' => $respData['merchantInvoiceNumber'],
            'tenant_id' => $data['tenant'],
            'base_amount' => $data['base_amount'],
            'base_currency' => $data['base_currency'],
            'pay_amount' => $respData['amount'],
            'pay_currency' => $respData['currency'],
            'exchange_rate' => $data['exchange_rate'],
            'payment_method' => 'online',
            'payment_id' => $respData['paymentID'],
            'gateway' => $this->gatewayName,
            'credential_owner' => ($data['forceLandlord'] ?? false) ? 'landlord' : 'tenant',
            'paid_for' => $data['paid_for'],
        ]);

        return redirect($respData['bkashURL']);
    }

    /**
     * Verify payment
     */
    public function verifyPayment(array $data)
    {
        $credentials = CredentialResolver::resolve($this->gatewayName, $data['forceLandlord'] ?? false);
        $token = $this->generateToken($credentials);
        $baseUrl = baseUrlFormat($credentials['base_url']);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'x-app-key' => $credentials['app_key'],
        ])->post("{$baseUrl}/checkout/execute", [
            'paymentID' => $data['paymentID'],
        ]);

        if (!$response->successful()) {
            throw new \Exception('bKash payment verification failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Cancel payment
     */
    public function cancelPayment(array $data)
    {
        $credentials = CredentialResolver::resolve('bkash');
        $token = $this->generateToken($credentials);
        $baseUrl = baseUrlFormat($credentials['base_url']);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'x-app-key' => $credentials['app_key'],
        ])->post("{$baseUrl}/checkout/cancel", [
            'paymentID' => $data['paymentID'],
        ]);

        if (!$response->successful()) {
            throw new \Exception('bKash payment cancellation failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refund payment
     */
    public function refundPayment(array $data)
    {
        $credentials = CredentialResolver::resolve('bkash');
        $token = $this->generateToken($credentials);
        $baseUrl = baseUrlFormat($credentials['base_url']);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'x-app-key' => $credentials['app_key'],
        ])->post("{$baseUrl}/checkout/refund", [
            'paymentID' => $data['paymentID'],
            'amount' => (string)$data['amount'],
            'trxID' => $data['trxID'] ?? null,
        ]);

        if (!$response->successful()) {
            throw new \Exception('bKash payment refund failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Generate payload for payment creation
     */
    public function generatePayload(array $data): array
    {
        return [
            'mode' => '0011',
            'payerReference' => (string) $data['tenant'],
            //'amount' => (string) $data['price'], //temporary static amount
            'amount' => '1', //temporary static amount
            'currency' => $data['currency'] ?? 'BDT',
            'callbackURL' => $data['callback_url'],
            'merchantInvoiceNumber' => (string) $data['invoice_number'],
            'intent' => 'sale', // can be 'sale' or 'subscription'
        ];
    }

    /**
     * Generate or get cached bKash token
     */
    private function generateToken(array $credentials): string
    {
        $baseUrl = baseUrlFormat($credentials['base_url']);
        $cacheKey = "bkash_{$credentials['app_key']}_token";

        return Cache::tags([landlord_tag()])->remember($cacheKey, now()->addMinutes(30), function () use ($credentials, $baseUrl) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'username' => $credentials['username'],
                'password' => $credentials['password'],
            ])->post("{$baseUrl}/checkout/token/grant", [
                'app_key' => $credentials['app_key'],
                'app_secret' => $credentials['app_secret'],
            ]);

            if (!$response->successful() || empty($response->json('id_token'))) {
                throw new \Exception('bKash token generation failed: ' . $response->body());
            }

            return $response->json('id_token');
        });
    }
}
