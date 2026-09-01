<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Payment;
use App\Models\landlord\Tenant;
use App\Payments\PaymentService;
use App\Services\Central\LandlordService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BillingController extends Controller
{
    protected $landlordService;

    public function __construct(LandlordService $landlordService)
    {
        $this->landlordService = $landlordService;
    }

    public function index($tenantId = null)
    {
        if (!$tenantId) {
            return abort(404, 'Tenant not specified.');
        }
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return abort(404, 'Tenant not found.');
        }

        // Initialize tenancy
        tenancy()->initialize($tenant);

        $packages = $this->landlordService->allActivePackages();
        $current_package = $this->landlordService->getPackageById(tenant()->package_id);
        $payments_gateways = $this->landlordService->activePaymentsGateways();

        return view('billing.index', compact('packages', 'current_package', 'payments_gateways'));
    }

    public function getPackageInfo($tenantId, $packageId)
    {
        tenancy()->initialize($tenantId);
        //dd(tenant());
        $package = $this->landlordService->getPackageById($packageId);
        if ($package) {
            $warnings = $this->landlordService->checkFeatureLimits(tenant()->package, $package);
            return response()->json([
                'status' => 'success',
                'data' => $package->pricing,
                'warnings' => $warnings
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Package not found',
            ], 404);
        }
    }

    public function checkPackageLimits(Request $request)
    {
        $currentPackage = tenant()->package;
        $newPackage = $this->landlordService->getPackageById($request->package_id);

        $warnings = $this->landlordService->checkFeatureLimits($currentPackage, $newPackage);

        return response()->json([
            'warnings' => $warnings
        ]);
    }

    public function checkout(Request $request, $tenantId)
    {
        $gateway = $this->landlordService->getPaymentGatewayById($request->payment_gateway);
        if (!$gateway) {
            return redirect()->back()->withErrors(['payment_gateway' => 'Invalid payment gateway selected.']);
        }

        $package = $this->landlordService->getPackageById($request->package_id);
        $pricendays = $package->pricing->firstWhere('type', $request->subscription_type);

        $tenant = Tenant::find($tenantId);

        // Calculate new expires_at
        $currentExpire = $tenant->expires_at ? Carbon::parse($tenant->expires_at) : null;
        $today = Carbon::today();
        $baseDate = ($currentExpire && $currentExpire->greaterThan($today)) ? $currentExpire : $today;
        $newExpireDate = $baseDate->copy()->addDays($pricendays->duration_days);
        $rate = $this->landlordService->getCurrencyRateByCode($request->currency);

        $gatewayPrice = $pricendays->price * $rate;
        // Backup old data and update tenant
        $tenant->update([
            'temp_data' => json_encode([
                'package_id' => $package->id,
                'subscription_type' => $request->subscription_type,
                'subscription_fee' => $pricendays->price,
                'expires_at' => $newExpireDate,
            ])
        ]);

        $invoiceNumber = 'INV-' . strtoupper(uniqid());
        $data = [
            'tenant' => $tenantId,
            'item_name' => $package->name,
            'base_amount' => $pricendays->price,
            'base_currency' => 'BDT',
            'pay_amount' => $gatewayPrice,
            'pay_currency' => $request->currency,
            'exchange_rate' => $rate,
            'subscription_type' => $request->subscription_type,
            'currency' => $request->currency,
            'gateway' => $gateway->name,
            'callback_url' => url('api/payment/bkash/callback'),
            'invoice_number' => $invoiceNumber,
            'paid_for' => 'subscription',
        ];

        $payment_service = app(PaymentService::class);
        return $payment_service->initialize($gateway->name, $data, true);
    }

    public function paymentSuccess($tenant)
    {
        $tenant = Tenant::find($tenant);
        return view('billing.success', compact('tenant'));
    }

    public function paymentFailed($tenant)
    {
        $tenant = Tenant::find($tenant);
        return view('billing.failed', compact('tenant'));
    }
    public function paymentCancel($tenant)
    {
        return view('billing.cancel', compact('tenant'));
    }
}
