<?php

namespace App\Http\Requests\Payments;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'          => ['required', 'uuid', 'exists:suppliers,id'],
            'payment_date'         => ['required', 'date'],
            'amount'               => ['required', 'numeric', 'min:0.01'],
            'payment_account_id'   => ['required', 'integer', 'exists:accounts,id'],
            'payment_method'       => ['required', 'string', 'in:cash,bank_transfer,cheque,mfs'],
            'reference_no'         => ['nullable', 'string', 'max:100'],
            'attachment'           => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:10240'],
            'note'                 => ['nullable', 'string', 'max:500'],
            'payable_type'         => ['nullable', 'string'],
            'payable_id'           => ['nullable', 'string'],
            'allocations'          => ['nullable', 'array'],
            'allocations.*.type'   => ['required_with:allocations', 'string', 'in:bill,purchase,asset,asset_register'],
            'allocations.*.id'     => ['required_with:allocations', 'string'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0'],
        ];
    }
}
