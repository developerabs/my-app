<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Package;
use App\Models\landlord\Payment;
use App\Models\landlord\Tenant;
use App\Payments\PaymentService;
use App\Traits\ManageTenants;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    use ManageTenants;
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function bkashCallback(Request $request)
    {
        $payment = Payment::where('payment_id', $request->paymentID)->first();
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        $request['forceLandlord'] = $payment->credential_owner == 'landlord';
        $response = $this->paymentService->verify($payment->gateway, $request->all());

        if ($response['statusCode'] !== '0000') {
            $payment->update(['status' => 'failed']);
            return redirect()->route('billing.failed', [$payment->tenant_id]);
        }

        return $this->finalizePayment($payment);
    }

    public function stripeSuccess(Payment $payment, Request $request)
    {
        $data = [
            'forceLandlord' => $payment->credential_owner == 'landlord',
            'session_id' => $request->session_id
        ];

        $response = $this->paymentService->verify($payment->gateway, $data);

        if (($response['payment_status'] ?? '') != 'paid') {
            $payment->update([
                'payment_id' => $response['payment_intent'] ?? null,
                'status' => 'failed'
            ]);
            return redirect()->route('billing.failed', [$payment->tenant_id]);
        }

        return $this->finalizePayment($payment);
    }

    /**
     * Common logic to update tenant subscription and payment status
     */
    private function finalizePayment(Payment $payment)
    {
        $payment->update(['status' => 'completed']);
        $tenant = Tenant::find($payment->tenant_id);

        if ($tenant && $payment->paid_for == 'subscription') {
            $tenantNewData = is_array($tenant->temp_data) ? $tenant->temp_data : json_decode($tenant->temp_data, true);
            if ($tenantNewData) {
                $this->updateTenantSubscription($tenant, $tenantNewData);
            }
        } elseif( $tenant && $payment->paid_for == 'store_purchase') {
            $tenantNewData = is_array($tenant->pending_purchase_data) ? $tenant->pending_purchase_data : json_decode($tenant->pending_purchase_data, true);
            if ($tenantNewData) {
                $this->manageStorePurchase($tenant, $tenantNewData);
            }
        }

        return redirect()->route('billing.success', [$payment->tenant_id]);
    }

    public function stripeCancel(Payment $payment)
    {
        $payment->update(['status' => 'failed']);
        return redirect()->route('billing.failed', [$payment->tenant_id]);
    }
}