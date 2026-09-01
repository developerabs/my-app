<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class OpeningBalanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'voucher_date' => [
                'required',
                'date',
            ],

            'branch_id' => [
                'required',
                'exists:branches,id',
            ],

            'currency_id' => [
                'required',
                'exists:currencies,id',
            ],

            'exchange_rate' => [
                'nullable',
                'numeric',
                'gt:0',
            ],

            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'narration' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'entries' => [
                'required',
                'array',
                'min:1',
            ],

            'entries.*.account_id' => [
                'required',
                'exists:accounts,id',
            ],

            'entries.*.debit' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'entries.*.credit' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'entries.*.reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'entries.*.description' => [
                'nullable',
                'string',
                'max:500',
            ],

        ];
    }

    public function attributes(): array
    {
        return [

            'voucher_date' => 'voucher date',
            'branch_id' => 'branch',
            'currency_id' => 'currency',
            'exchange_rate' => 'exchange rate',
            'reference_no' => 'reference no',
            'narration' => 'narration',
            'entries' => 'journal entries',
            'entries.*.account_id' => 'account',
            'entries.*.debit' => 'debit amount',
            'entries.*.credit' => 'credit amount',
            'entries.*.reference_no' => 'reference no',
            'entries.*.description' => 'description',

        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'exchange_rate' => $this->exchange_rate ?: 1,
        ]);
    }
}
