<?php

namespace App\Http\Requests;

use App\Enums\DrugType;
use App\Models\Product;
use App\Rules\UniqueWithTrashCheck;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('product_create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // কন্ডিশনাল ভেরিয়েবল সেট করা
        $isPhysicalOrDropship = 'required_if:type,physical,dropship';
        $hasSpec = 'required_if:has_specification,1,true';
        $hasWarranty = 'required_if:has_warranty,1,true';
        $isDigital = 'required_if:type,digital';
        $isDropship = 'required_if:type,dropship';

        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'type' => ['required', 'in:physical,service,digital,combo,dropship', 'string', 'max:50'],
            'barcode_type' => ['required', 'in:standard,barcode_with_batch,dynamic,master,barcode_with_serial', 'string', 'max:50'],
            'barcode_symbology' => ['required', 'string', 'max:50'],

            // Pricing
            'cost' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'tax_type' => ['required', 'in:exclusive,inclusive', 'string'],
            'tax_id' => ['nullable', 'exists:taxes,id'],
            'profit_margin' => ['required', 'numeric', 'min:0'],

            // Description
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:255'],

            // Flags (Boolean values are handled in prepareForValidation)
            'manage_stock' => 'boolean',
            'allow_oversale' => 'boolean',
            'has_variants' => 'boolean',
            'has_imei' => 'boolean',
            'has_expire_date' => 'boolean',
            'is_featured' => 'boolean',
            'has_specification' => 'boolean',
            'sale_online' => 'boolean',
            'has_warranty' => 'boolean',
            'enable_preorder' => 'boolean',
            'has_opening_stock' => 'boolean',
            'warranty_details' => 'nullable|json',

            // Dynamic Specification Validation
            'specification_name' => [$hasSpec, 'array'],
            'specification_name.*' => ['nullable', $hasSpec, 'string', 'max:255'],
            'specification_value' => [$hasSpec, 'array'],
            'specification_value.*' => ['nullable', $hasSpec, 'string'],

            // Warranty Validation
            'warranty_type' => ['nullable', $hasWarranty, 'in:replacement,service,lifetime,limited'],
            'warranty_provider' => ['nullable', $hasWarranty, 'in:seller,manufacturer,thirdparty'],
            'warranty_period' => ['nullable', $hasWarranty, 'numeric', 'min:0'],
            'period_type' => ['nullable', $hasWarranty, 'in:day,month,year'],
            'warranty_terms_and_conditions' => ['nullable', 'string'],
            
            // Unit Vars
            'unit_vars' => 'nullable|array',
            'unit_vars.*' => 'array',
            'unit_vars.*.*' => 'required|numeric|min:0.01',

            // Relationships & Units (Only required for physical or dropship)
            'category_id' => 'nullable|array|min:1',
            'category_id.*' => 'uuid|exists:categories,id',
            'brand_id' => 'nullable|uuid|exists:brands,id',
            'generic_id' => 'nullable|uuid|exists:generics,id',
            'drug_type' => ['nullable', 'string', Rule::enum(DrugType::class)],
            
            'unit_group_id'    => [$isPhysicalOrDropship, 'nullable', 'uuid', 'exists:unit_groups,id'],
            'base_unit_id'     => [$isPhysicalOrDropship, 'nullable', 'uuid', 'exists:units,id'],
            'purchase_unit_id' => [$isPhysicalOrDropship, 'nullable', 'uuid', 'exists:units,id'],
            'sale_unit_id'     => [$isPhysicalOrDropship, 'nullable', 'uuid', 'exists:units,id'],
            
            'alert_quantity' => 'nullable|numeric|min:0',
            'max_sale_commision' => 'nullable|numeric|min:0|max:100',
            'unit_details' => 'nullable|json',

            'product_seo'                  => 'nullable|array',
            'product_seo.meta_title'       => 'nullable|string|max:255',
            'product_seo.meta_keywords'    => 'nullable|string|max:255',
            'product_seo.meta_description' => 'nullable|string|max:500',

            'digital_file' => [$isDigital, 'nullable', 'string'],
            'digital_external_link' => [$isDigital, 'nullable', 'url'],

            'platform_name' => [$isDropship, 'nullable', 'string'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // বুলিয়ান ফিল্ডগুলোকে ফিল্টার করে কনভার্ট করা হচ্ছে যেন ভ্যালিডেশন এরর না দেয়
        $this->merge([
            'manage_stock'      => filter_var($this->manage_stock, FILTER_VALIDATE_BOOLEAN),
            'allow_oversale'    => filter_var($this->allow_oversale, FILTER_VALIDATE_BOOLEAN),
            'has_variants'      => filter_var($this->has_variants, FILTER_VALIDATE_BOOLEAN),
            'has_imei'          => filter_var($this->has_imei, FILTER_VALIDATE_BOOLEAN),
            'has_expire_date'   => filter_var($this->has_expire_date, FILTER_VALIDATE_BOOLEAN),
            'is_featured'       => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN),
            'has_specification' => filter_var($this->has_specification, FILTER_VALIDATE_BOOLEAN),
            'sale_online'       => filter_var($this->sale_online, FILTER_VALIDATE_BOOLEAN),
            'has_warranty'      => filter_var($this->has_warranty, FILTER_VALIDATE_BOOLEAN),
            'enable_preorder'   => filter_var($this->enable_preorder, FILTER_VALIDATE_BOOLEAN),
        ]);

        // unit_vars খালি থাকলে রিকোয়েস্ট থেকে রিমুভ করা
        if (empty($this->unit_vars)) {
            $this->request->remove('unit_vars');
        }
    }
}