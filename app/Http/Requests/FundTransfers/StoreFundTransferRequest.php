<?php

namespace App\Http\Requests\FundTransfers;

use Illuminate\Foundation\Http\FormRequest;

class StoreFundTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transfer_date'   => ['required', 'string'],
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id'   => ['required', 'exists:accounts,id', 'different:from_account_id'], // 🛑 একই একাউন্টে ট্রান্সফার ব্লক
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'branch_id'       => ['required', 'uuid', 'exists:branches,id'],
            'currency_id'     => ['required', 'exists:currencies,id'],
            'exchange_rate'   => ['nullable', 'numeric', 'min:0.00000001'],
            'payment_method'  => ['nullable', 'string', 'max:50'],
            'reference_no'    => ['nullable', 'string', 'max:100'],
            'attachment'      => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:5120'],
            'note'            => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_account_id.required' => 'Please select the source payment account.',
            'to_account_id.required'   => 'Please select the destination payment account.',
            'to_account_id.different'  => 'Source and destination payment accounts must be different.',
            'amount.required'          => 'Transfer amount is required.',
            'amount.min'               => 'Amount must be greater than zero.',
        ];
    }
}