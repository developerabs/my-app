<?php

namespace App\Http\Requests\Bills;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBillRequest extends FormRequest
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
        $hasLateFee = $this->boolean('has_late_fee');

        return [
            'bill_date' => ['required', 'string'],
            'due_date' => ['nullable', 'string'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.00000001'],
            'project_id' => ['nullable', 'string', 'max:50'],
            'vendor_invoice_no' => ['nullable', 'string', 'max:100'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],

            // 🛑 LATE FEE CONDITIONAL VALIDATION RULES
            'has_late_fee' => ['nullable', 'boolean'],
            'late_fee_config' => [$hasLateFee ? 'required' : 'nullable', 'array'],
            'late_fee_config.grace_days' => [$hasLateFee ? 'required' : 'nullable', 'integer', 'min:0'],
            'late_fee_config.fee_type' => [$hasLateFee ? 'required' : 'nullable', 'in:fixed,percentage'],
            'late_fee_config.rate' => [$hasLateFee ? 'required' : 'nullable', 'numeric', 'min:0'],
            'late_fee_config.calculation_method' => [$hasLateFee ? 'required' : 'nullable', 'in:simple,compound'],
            'late_fee_config.frequency' => [$hasLateFee ? 'required' : 'nullable', 'in:one_time,monthly'],
            'late_fee_config.max_fee_limit' => ['nullable', 'numeric', 'min:0'], // অপশনাল ক্যাপ লিমিট

            'items' => ['required', 'array', 'min:1'],
            'items.*.expense_account_id' => ['required', 'exists:accounts,id'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.project_id' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier for this vendor bill.',
            'branch_id.required' => 'Please select a branch.',
            'currency_id.required' => 'Please select a currency.',
            'items.required' => 'At least one bill expense item is required.',
            'items.min' => 'At least one bill expense item is required.',
            'items.*.expense_account_id.required' => 'Please select an expense category for all bill items.',
            'items.*.amount.required' => 'Amount is required for all bill items.',
            'items.*.amount.min' => 'Item amount must be greater than 0.',

            // Late fee error messages
            'late_fee_config.grace_days.required' => 'Grace period (days) is required when late fee is enabled.',
            'late_fee_config.fee_type.required' => 'Fee type is required when late fee is enabled.',
            'late_fee_config.rate.required' => 'Fee rate or amount is required when late fee is enabled.',
            'late_fee_config.calculation_method.required' => 'Calculation method is required when late fee is enabled.',
            'late_fee_config.frequency.required' => 'Charge frequency is required when late fee is enabled.',
        ];
    }
}
