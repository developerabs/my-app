<?php

namespace App\Http\Controllers;

use App\Models\landlord\Tenant;
use App\Payments\PaymentService;
use App\Services\Central\LandlordService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StorePurchaseController extends Controller
{
    protected $landlordService;

    public function __construct(LandlordService $landlordService)
    {
        $this->landlordService = $landlordService;
    }

    public function index(Request $request)
    {
        $paymentGateways = $this->landlordService->activePaymentsGateways();
        $alladdons = $this->landlordService->getAddons();
        $allmodules = $this->landlordService->getModules();
        $myModules = tenant()->modules()->get()->keyBy('module_id')->toArray();

        $modules = $this->moduleForPurchase($allmodules, $myModules);

        //return $modules;
        return view('backend.store_purchase.store', compact('paymentGateways', 'alladdons', 'modules'));
    }

    public function getModuleDetails($moduleId)
    {
        return response()->json([
            'status' => true,
            'data' => $this->landlordService->getModuleDetailsById($moduleId),
        ]);
    }

    public function makePayment(Request $request)
    {
        $gateway = $this->landlordService->getPaymentGatewayById($request->payment_gateway);
        if (!$gateway) {
            return redirect()->back()->withErrors(['payment_gateway' => 'Invalid payment gateway selected.']);
        }
        //dd($request->all());
        if($request->item_type == 'module') {
            $item = $this->landlordService->getModuleDetailsById($request->item_id);
            $basePrice = $item->meta['pricing'][$request->payment_frequency] ?? 0;
        }elseif($request->item_type == 'addon') {
            $item = $this->landlordService->getAddonDetailsById($request->item_id);
            $basePrice = $item->meta['pricing'][$request->payment_frequency] ?? 0;
        } else {
            return redirect()->back()->withErrors(['item_type' => 'Invalid item type selected.']);
        }
        $rate = $this->landlordService->getCurrencyRateByCode($request->payment_currency);
        $gatewayPrice = $basePrice * $rate;

        $tenant = Tenant::find($request->tenant);
        if (!$tenant) {
            return redirect()->back()->withErrors(['tenant' => 'Tenant not found.']);
        }
        $tenant->update([
            'pending_purchase_data' => json_encode([
                'item_type' => $request->item_type,
                'item_id' => $item->id,
                'is_renewal' => $request->is_renewal,
                'base_price' => $basePrice,
                'subscription_type' => $request->payment_frequency,
            ])
        ]);
        $invoiceNumber = 'INV-' . strtoupper(uniqid());
        $data = [
            'tenant' => $tenant->id,
            'item_name' => $item->name,
            'base_amount' => $basePrice,
            'base_currency' => 'BDT',
            'pay_amount' => $gatewayPrice,
            'pay_currency' => $request->payment_currency,
            'exchange_rate' => $rate,
            'subscription_type' => $request->payment_frequency,
            'currency' => $request->payment_currency,
            'gateway' => $gateway->name,
            'callback_url' => url('api/payment/bkash/callback'),
            'invoice_number' => $invoiceNumber,
            'paid_for' => 'store_purchase',
        ];
        $payment_service = app(PaymentService::class);
        return $payment_service->initialize($gateway->name, $data, true);
    }

    private function moduleForPurchase($allmodules, $myModules)
    {
        $modules = [];
        foreach ($allmodules as $module) {
            $owned = $myModules[$module->id] ?? null;
            if($owned) {
                $module['owned'] = true;
                $module['owned_type'] = $myModules[$module->id]['type'] ?? 'addon';
                $module['owned_expires_at'] = isset($myModules[$module->id]['expires_at']) ? Carbon::parse($myModules[$module->id]['expires_at'])->toDateString() : null;
                $module['owned_is_active'] = $myModules[$module->id]['is_active'] ?? 0;
            } else {
                $module['owned'] = false;
                $module['owned_type'] = null;
                $module['owned_expires_at'] = null;
                $module['owned_is_active'] = 0;
            }
            $modules[] = (object) $module;
        }
        return $modules;
    }
}
