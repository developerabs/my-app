<?php

namespace App\Http\Controllers\Landlord;

use App\DataTables\Landlord\PaymentGatewayDataTable;
use App\Http\Controllers\Controller;
use App\Models\landlord\Gateway;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GatewayController extends Controller
{
    use HasFiles;
    public function paymentGateway(PaymentGatewayDataTable $dataTable)
    {
        return $dataTable->render('landlord.dashboard.settings.payment-gateway');
    }

    public function editPaymentGateway(Gateway $gateway)
    {
        return response()->json([
            'status' => true,
            'data' => $gateway,
            'image_url' => $gateway->logo_url,
        ]);
    }

    public function storePaymentGateway(Request $request)
    {
        try {
            $validateData = $request->validate([
                'name' => 'required|string|unique:gateways,name',
                'display_name' => 'nullable|string',
                'parameters' => 'required|array',
                'parameters.*' => 'required|string',
                'values' => 'required|array',
                'values.*' => 'nullable|string',
                'logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            ]);

            // ✅ Combine parameters and values
            $credentials = [];
            foreach ($validateData['parameters'] as $index => $parameter) {
                $credentials[$parameter] = $validateData['values'][$index] ?? null;
            }

            // ✅ Handle file upload securely
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $this->uploadFiles($request, 'logo', 'landlord/gateways');
            }

            // ✅ Store gateway
            $gateway = Gateway::create([
                'type' => 'payment',
                'name' => $validateData['name'],
                'display_name' => $validateData['display_name'] ?? null,
                'credentials' => encrypt(json_encode($credentials)),
                'logo' => $logoPath,
                'added_by' => Auth::id(),
            ]);

            // ✅ Return AJAX-friendly success response
            return response()->json([
                'status' => true,
                'message' => 'Payment gateway created successfully!',
                'data' => $gateway,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // ✅ Validation Error (422)
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // ✅ Any other exception
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updatePaymentGateway(Request $request)
    {
        try {
            // ✅ Validation
            $validated = $request->validate([
                'id' => 'required|integer|exists:gateways,id',
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('gateways', 'name')->ignore($request->id),
                ],
                'display_name' => 'nullable|string|max:150',
                'parameters' => 'required|array|min:1',
                'parameters.*' => 'required|string|max:100',
                'values' => 'required|array',
                'values.*' => 'nullable|string|max:500',
                'logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            ]);

            // ✅ Prepare credentials key-value pair
            $credentials = collect($validated['parameters'])
                ->mapWithKeys(fn($param, $i) => [$param => $validated['values'][$i] ?? null])
                ->toArray();

            // ✅ Find Gateway
            $gateway = Gateway::findOrFail($validated['id']);

            // ✅ Handle logo upload (using HasFiles trait)
            $logoPath = $gateway->logo;
            if ($request->hasFile('logo')) {
                // This method should delete old and upload new automatically if your trait supports it
                $logoPath = $this->updateFile($request, 'logo', $gateway->logo, 'landlord/gateways');
            }

            // ✅ Update Gateway
            $gateway->update([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'] ?? $gateway->display_name,
                'credentials' => encrypt(json_encode($credentials)),
                'logo' => $logoPath,
            ]);

            // ✅ Response
            return response()->json([
                'status' => true,
                'message' => 'Payment gateway updated successfully!',
                'data' => $gateway,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyPaymentGateway(Gateway $gateway)
    {
        try {
            // ✅ Delete logo if exists
            if ($gateway->logo) {
                $this->deleteFile($gateway->logo);
            }

            // ✅ Delete gateway
            $gateway->delete();

            // ✅ Response
            return response()->json([
                'status' => true,
                'message' => 'Payment gateway deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete payment gateway.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function storeSmsGateway(Request $request)
    {
        // Logic to store SMS gateway settings
        return back()->with('success', 'SMS gateway settings updated successfully.');
    }
}
