<?php

namespace App\Http\Requests\Purchases;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
     public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Header Fields
            'purchase_date'         => ['required', 'date'],
            'due_date'              => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'supplier_id'           => ['required', 'uuid', 'exists:suppliers,id'],
            'branch_id'             => ['required', 'uuid', 'exists:branches,id'],
            'currency_id'           => ['required', 'integer', 'exists:currencies,id'],
            'exchange_rate'         => ['nullable', 'numeric', 'min:0.00000001'],
            'purchase_status'       => ['required', 'in:received,partial,pending,ordered'],
            'reference'             => ['nullable', 'string', 'max:100'],
            'memo_number'           => ['nullable', 'string', 'max:100'],
            'note'                  => ['nullable', 'string'],
            'document'              => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:10240'],

            // Payment Source (Required if paid_amount > 0)
            'paid_amount'           => ['nullable', 'numeric', 'min:0'],
            'payment_account_id'    => [
                'nullable',
                'required_if_accepted:paid_amount',
                function ($attribute, $value, $fail) {
                    if ((float) $this->input('paid_amount', 0) > 0 && empty($value)) {
                        $fail(__('Please select a payment source account since paid amount is greater than zero.'));
                    }
                },
                'integer',
                'exists:accounts,id'
            ],

            // Order Discounts & Taxes
            'order_discount_method' => ['nullable', 'in:flat,percentage'],
            'order_discount_rate'   => ['nullable', 'numeric', 'min:0'],
            'order_tax_method'      => ['nullable'],
            'order_tax_rate'        => ['nullable', 'numeric', 'min:0'],
            'shipping_cost'         => ['nullable', 'numeric', 'min:0'],
            'shipping_carrier_id'   => ['nullable', 'uuid', 'exists:suppliers,id'],

            // Line Items Grid (matches JS: name="products[UID][...]")
            'products'                          => ['required', 'array', 'min:1'],
            'products.*.product_id'             => ['required', 'uuid', 'exists:products,id'],
            'products.*.batch_id'               => ['nullable', 'uuid', 'exists:product_batches,id'],
            'products.*.batch_number'           => ['nullable', 'string', 'max:100'],
            'products.*.expire_date'            => ['nullable', 'date'],
            'products.*.quantity'               => ['required', 'numeric', 'min:0.0001'],
            'products.*.received_qty'           => ['nullable', 'numeric', 'min:0'],
            'products.*.unit_id'                => ['required', 'uuid', 'exists:units,id'],
            'products.*.price'                  => ['required', 'numeric', 'min:0'],
            'products.*.base_unit_price'        => ['nullable', 'numeric'],
            'products.*.discount_method'        => ['nullable', 'in:flat,percentage'],
            'products.*.unit_discount'          => ['nullable', 'numeric', 'min:0'],
            'products.*.tax_method'             => ['nullable', 'in:exclusive,inclusive'],
            'products.*.tax_rate'               => ['nullable', 'numeric', 'min:0'],
            'products.*.imei_list'              => ['nullable', 'string'],
            'products.*.barcodes'               => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'products.required'     => __('Please add at least one product item to the purchase table.'),
            'products.min'          => __('Please add at least one product item to the purchase table.'),
            'supplier_id.required'  => __('Please select a supplier.'),
            'branch_id.required'    => __('Branch is required.'),
            'currency_id.required'  => __('Currency is required.'),
            'purchase_date.required'=> __('Purchase date is required.'),
        ];
    }
}
