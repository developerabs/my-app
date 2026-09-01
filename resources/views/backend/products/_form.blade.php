@php
    $product = $product ?? '';
    $isEdit = $isEdit ?? false;
@endphp

<div class="row">
    <div class="col-md-8">
        <div class="card mb-2">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-3 order-md-last">
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="fw-bold" for="type">{{ __('file.field.product_type') }}</label>
                        <select class="form-select" name="type" id="product_type" {{ $isEdit ? 'disabled' : '' }}
                            required>
                            {{-- Check if unit groups are empty or if the total sum of units is zero --}}
                            @php
                                $isUnitDisabled = $unitGroups->isEmpty() || $unitGroups->sum('units_count') == 0;
                                $currentType = $product->type ?? '';
                            @endphp
                            <option value="physical" {{ $isUnitDisabled ? 'disabled' : '' }}
                                {{ old('type', $currentType) == 'physical' ? 'selected' : '' }}>
                                Physical {{ $isUnitDisabled ? '(Unit Required)' : '' }}
                            </option>
                            {{-- <option value="digital" disabled {{ old('type', $currentType) == 'digital' ? 'selected' : '' }}>
                                Digital (Coming Soon)</option> --}}
                            <option value="service" {{ old('type', $currentType) == 'service' ? 'selected' : '' }}>
                                Service</option>
                            <option value="combo" {{ old('type', $currentType) == 'combo' ? 'selected' : '' }}>Combo
                            </option>
                            <option value="dropship" {{ $isUnitDisabled ? 'disabled' : '' }}
                                {{ old('type', $currentType) == 'dropship' ? 'selected' : '' }}>
                                Dropship {{ $isUnitDisabled ? '(Unit Required)' : '' }}
                            </option>
                        </select>
                        @if ($isEdit)
                            <input type="hidden" name="type" value="{{ $product->type ?? '' }}">
                        @endif
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="fw-bold" for="barcode_type">{{ __('file.field.barcode_type') }} </label>
                        @php
                            $currentBarcodeType = $product->barcode_type ?? '';
                        @endphp
                        <select name="barcode_type" id="barcode_type" class="form-select">
                            <option value="standard"
                                {{ old('barcode_type', $currentBarcodeType) == 'standard' ? 'selected' : '' }}>Standard
                            </option>
                            <option value="barcode_with_batch"
                                {{ old('barcode_type', $currentBarcodeType) == 'barcode_with_batch' ? 'selected' : '' }}>
                                Barcode with Batch
                            </option>
                            <option value="dynamic"
                                {{ old('barcode_type', $currentBarcodeType) == 'dynamic' ? 'selected' : '' }}>Dynamic
                                (Weight Scale)</option>
                            <option value="master"
                                {{ old('barcode_type', $currentBarcodeType) == 'master' ? 'selected' : '' }}>Master
                                (Existing on Product)</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold" for="barcode_symbology">{{ __('file.field.barcode_symbology') }}
                        </label>
                        @php
                            $currentBarcodeSymbology = $product->barcode_symbology ?? '';
                        @endphp
                        <select name="barcode_symbology" id="barcode_symbology" class="form-select select2">
                            <option value="C128"
                                {{ old('barcode_symbology', $currentBarcodeSymbology) == 'C128' ? 'selected' : '' }}>
                                Code 128
                                (Standard)</option>
                            <option value="C39"
                                {{ old('barcode_symbology', $currentBarcodeSymbology) == 'C39' ? 'selected' : '' }}>
                                Code 39
                            </option>
                            <option value="EAN13"
                                {{ old('barcode_symbology', $currentBarcodeSymbology) == 'EAN13' ? 'selected' : '' }}>
                                EAN-13
                                (Weight Scale)</option>
                            <option value="EAN8"
                                {{ old('barcode_symbology', $currentBarcodeSymbology) == 'EAN8' ? 'selected' : '' }}>
                                EAN-8
                            </option>
                            <option value="UPCA"
                                {{ old('barcode_symbology', $currentBarcodeSymbology) == 'UPCA' ? 'selected' : '' }}>
                                UPC-A
                            </option>
                            <option value="ITF14"
                                {{ old('barcode_symbology', $currentBarcodeSymbology) == 'ITF14' ? 'selected' : '' }}>
                                ITF-14
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold" for="hs_code">{{ __('file.field.hs_code') }} </label>
                        <input type="text" name="hs_code" class="form-control"
                            value="{{ old('hs_code', $product->hs_code ?? '') }}" placeholder="HS Code">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-9 mb-3">
                        <label class="fw-bold" for="product_name">{{ __('file.field.name') }} <span
                                class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                            value="{{ old('name', $product->name ?? '') }}" required
                            placeholder="{{ __('file.placeholder.product_name_hint') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold" for="sku">{{ __('file.field.sku') }} <span
                                class="text-danger">*</span></label>
                        <input type="text" name="sku" class="form-control"
                            value="{{ old('sku', $product->sku ?? '') }}" required placeholder="SKU-TST001">
                    </div>
                </div>
                <div class="row" id="price_section">
                    <div class="col-md-3 col-12 mb-3">
                        <label class="fw-bold" for="profit_margin">
                            {{ __('file.field.profit_margin') }} (%)
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="profit_margin" id="profit_margin" class="form-control"
                            value="{{ old('profit_margin', $product->profit_margin ?? 0) }}" step="0.01"
                            min="0">
                    </div>

                    <div class="col-md-3 col-12 mb-3">
                        <label class="fw-bold" for="product_cost">{{ __('file.field.cost') }} <span
                                class="text-danger">*</span></label>
                        <input type="number" name="cost" id="product_cost" class="form-control"
                            value="{{ old('cost', $product->cost ?? '') }}" step="0.01" required>
                    </div>

                    <div class="col-md-3 col-12 mb-3">
                        <label class="fw-bold" for="product_price">{{ __('file.field.price') }} <span
                                class="text-danger">*</span></label>
                        <input type="number" name="price" id="product_price" class="form-control"
                            value="{{ old('price', $product->price ?? '') }}" step="0.01" required>
                    </div>

                    <div class="col-md-3 col-12 mb-3">
                        <label class="fw-bold" for="wholesale_price">{{ __('file.field.wholesale_price') }}</label>
                        <input type="number" name="wholesale_price" id="wholesale_price" class="form-control"
                            value="{{ old('wholesale_price', $product->wholesale_price ?? '') }}" step="0.01">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="fw-bold"
                            for="short_description">{{ __('file.field.short_description') }}</label>
                        <textarea name="short_description" class="form-control" rows="2">{{ old('short_description', $product->short_description ?? '') }}</textarea>
                    </div>
                </div>
                <div class="row mb-3">
                    @include('backend.layouts.partials._render_custom_fields', [
                        'custom_fields' => $custom_fields,
                        'model' => null,
                        'grid_class' => 'col-md-4',
                    ])
                </div>
                <div class="row mb-2">
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="manage_stock" id="manage_stock"
                                value="1"
                                {{ old('manage_stock', $produt->manage_stock ?? false) == true ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold"
                                for="manage_stock">{{ __('file.field.manage_stock') }}
                                <span class="tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ __('file.field.manage_stock_tooltip') }}">
                                    <i class="fa-solid fa-info-circle "></i>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="allow_oversale"
                                id="allow_oversale" value="1"
                                {{ old('allow_oversale', $product->allow_oversale ?? false) == true ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold"
                                for="allow_oversale">{{ __('file.field.allow_oversale') }}
                                <span class="tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ __('file.field.allow_oversale_tooltip') }}">
                                    <i class="fa-solid fa-info-circle"></i>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="has_variants" id="has_variants"
                                value="1"
                                {{ old('has_variants', $product->has_variants ?? false) == true ? 'checked' : '' }}
                                {{ $isEdit ? 'disabled' : '' }}>
                            @if ($isEdit)
                                <input type="hidden" name="has_variants"
                                    value="{{ $product->has_variants ?? '0' }}">
                            @endif
                            <label class="form-check-label fw-bold"
                                for="has_variants">{{ __('file.field.has_variants') }}
                                <span class="tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ __('file.field.has_variants_tooltip') }}">
                                    <i class="fa-solid fa-info-circle"></i>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                                value="1"
                                {{ old('is_featured', $product->is_features ?? false) == true ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold"
                                for="is_featured">{{ __('file.field.is_featured') }}
                                <span class="tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ __('file.field.is_featured_tooltip') }}">
                                    <i class="fa-solid fa-info-circle"></i>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="has_expire_date" id="expire_date"
                                value="1"
                                {{ old('has_expire_date', $product->has_expire_date ?? false) == true ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold"
                                for="expire_date">{{ __('file.field.has_expire_date') }}</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="has_imei" id="has_imei"
                                value="1" {{ $isEdit ? 'disabled' : '' }}
                                {{ old('has_imei', $product->has_imei ?? false) == true ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold"
                                for="has_imei">{{ __('file.field.has_imei_or_serial') }}
                                <span class="tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ __('file.field.has_imei_tooltip') }}">
                                    <i class="fa-solid fa-info-circle"></i>
                                </span>
                            </label>
                            @if ($isEdit)
                                <input type="hidden" name="has_imei"
                                    value="{{ $product->has_imei ?? '0' }}">
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="has_specification"
                                id="has_specification" value="1"
                                {{ old('has_specification', $product->has_specification ?? false) == true ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold"
                                for="has_specification">{{ __('file.field.has_specification') }}
                                <span class="tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ __('file.field.has_specification_tooltip') }}">
                                    <i class="fa-solid fa-info-circle"></i>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sale_online" id="sale_online"
                                value="1"
                                {{ old('sale_online', $product->sale_online ?? false) == true ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold"
                                for="sale_online">{{ __('file.field.sale_online') }}
                                <span class="tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ __('file.field.sale_online_tooltip') }}">
                                    <i class="fa-solid fa-info-circle"></i>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="has_warranty" id="has_warranty"
                                value="1"
                                {{ old('has_warranty', $product->has_warranty ?? false) == true ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold"
                                for="has_warranty">{{ __('file.field.has_warranty') }}
                                <span class="tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ __('file.field.has_warranty_tooltip') }}">
                                    <i class="fa-solid fa-info-circle"></i>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_preorder"
                                id="enable_preorder" value="1"
                                {{ old('enable_preorder', $product->enable_preorder ?? false) == true ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold"
                                for="enable_preorder">{{ __('file.field.enable_preorder') }}
                                <span class="tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ __('file.field.enable_preorder_tooltip') }}">
                                    <i class="fa-solid fa-info-circle"></i>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" name="has_opening_stock" id="has_opening_stock" value="1" class="form-check-input" {{ old('has_opening_stock', $product->has_opening_stock ?? false) == true ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold"
                                for="has_opening_stock">{{ __('file.field.has_opening_stock') }}
                                <span class="tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ __('file.field.has_opening_stock_tooltip') }}">
                                    <i class="fa-solid fa-info-circle"></i>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-2 d-none" id="dynamic_product_card">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-primary" id="dynamic_card_title">Type Details</h6>
            </div>
            <div class="card-body" id="dynamic_field_container">
            </div>
        </div>

        <div class="card mb-2" id="dropship_product_card">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-primary">Dropship Details</h6>
            </div>
            <div class="card-body" id="dropship_field_container">
            </div>
        </div>

        <div class="card mb-2">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ __('Additional Details') }}</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="toggle_details">
                    @if ($isEdit)
                        <i class="fa fa-minus"></i> Hide Details
                    @else
                        <i class="fa fa-plus"></i> Add More Details
                    @endif
                </button>
            </div>
            <div class="card-body {{ $isEdit ? '' : 'd-none' }}" id="additional_details_section">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold" for="short_name">{{ __('file.field.short_name') }}</label>
                        <input type="text" class="form-control" name="short_name"
                            value="{{ old('short_name', $product->short_name ?? '') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold" for="tax_id">{{ __('file.field.tax') }}</label>
                        <div class="input-group">
                            <select name="tax_type" class="form-select" style="max-width: 35%;">
                                <option value="inclusive"
                                    {{ old('tax_type', $product->tax_type ?? '') == 'inclusive' ? 'selected' : '' }}>
                                    Inclusive</option>
                                <option value="exclusive"
                                    {{ old('tax_type', $product->tax_type ?? '') == 'exclusive' ? 'selected' : '' }}>
                                    Exclusive</option>
                            </select>

                            <select name="tax_id" class="form-select">
                                <option value="">No Tax</option>
                                @foreach ($taxes as $tax)
                                    <option value="{{ $tax->id }}"
                                        {{ old('tax_id', $product->tax_id ?? '') == $tax->id ? 'selected' : '' }}>
                                        {{ $tax->name }} ({{ $tax->rate }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold" for="alert_quantity">Alert Quantity</label>
                        <input type="number" name="alert_quantity"
                            value="{{ old('alert_quantity', $product->alert_quantity ?? '') }}" class="form-control"
                            placeholder="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold" for="max_sale_commision">Max Sale Commission (%)</label>
                        <input type="number" step="any" name="max_sale_commision"
                            value="{{ old('max_sale_commision', $product->max_sale_commision ?? '') }}"
                            class="form-control" placeholder="0.00">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="fw-bold" for="content"
                            class="form-label">{{ __('file.field.content') }}</label>
                        <button type="button" class="btn btn-sm btn-info mb-1" id="toggle-source">Toggle
                            HTML</button>
                        <div id="content" style="height: auto;">
                            {!! old('description', $product->description ?? '') !!}</div>
                        <textarea id="source-container" style="display:none; width:100%; height:200px;"></textarea>
                        <input type="hidden" name="description" id="content_input"
                            value="{{ old('description', $product->description ?? '') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold" for="meta_title">Meta Title</label>
                        <input type="text" name="product_seo[meta_title]" class="form-control"
                            value="{{ old('product_seo.meta_title', $product->product_seo['meta_title'] ?? '') }}"
                            placeholder="Meta Title">
                    </div>
                    <div class="col-md-6 mb3">
                        <label class="fw-bold" for="meta_keywords">Meta Keywords</label>
                        <input type="text" name="product_seo[meta_keywords]" class="form-control"
                            value="{{ old('product_seo.meta_keywords', $product->product_seo['meta_keywords'] ?? '') }}"
                            placeholder="Meta Keywords">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="fw-bold" for="meta_description">Meta Description</label>
                        <textarea name="product_seo[meta_description]" class="form-control" rows="2" placeholder="Meta Description">{{ old('product_seo.meta_description', $product->product_seo['meta_description'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="col-md-4">
        @feature('pharmacy')
            <div class="card mb-2" id="pharmacy_item_section">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="fw-bold" for="generic_id">{{ __('file.field.generic') }} </label>
                            <select name="generic_id" id="generic_id" class="form-control selectpicker"
                                data-live-search="true">
                                <option value="">{{ __('file.option.select_generic') }}</option>
                                @isset($generics)
                                    @foreach ($generics as $generic)
                                        <option value="{{ $generic->id }}"
                                            {{ old('generic_id', $product->generic_id ?? '') == $generic->id ? 'selected' : '' }}>
                                            {{ $generic->name }}</option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold" for="drug_type">{{ __('file.field.medicine_type') }} </label>
                            <select name="drug_type" id="drug_type" class="form-control selectpicker">
                                <option value="">{{ __('file.option.select_drug_type') }}</option>
                                @isset($drug_types)
                                    @foreach($drug_types as $type)
                                        <option value="{{ $type->value }}" 
                                            {{ (isset($product) && is_object($product) && ($product->drug_type->value ?? $product->drug_type) === $type->value) ? 'selected' : (old('drug_type') === $type->value ? 'selected' : '') }}>
                                            {{ ucfirst($type->name) }}
                                        </option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        @endfeature
        <div class="card mb-2">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="fw-bold" for="category_id">{{ __('file.field.category') }} </label>
                        @php
                            $selectedIds = $product->selected_categories ?? [];
                        @endphp
                        <select name="category_id[]" id="category_id" class="form-control selectpicker"
                            multiple="multiple">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ in_array($category->id, old('category_id', $selectedIds)) ? 'selected' : '' }}>
                                    {{ $category->name }}</option>

                                @if ($category->allChildren->isNotEmpty())
                                    @include('backend.products._category_options', [
                                        'children' => $category->allChildren,
                                        'prefix' => '—',
                                        'selectedCategories' => $selectedIds,
                                    ])
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold" for="brand_id">{{ __('file.field.brand') }} </label>
                        <select name="brand_id" id="brand_id" class="form-control selectpicker"
                            data-live-search="true">
                            <option value="">{{ __('file.option.select_brand') }}</option>
                            @isset($brands)
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}"
                                        {{ old('brand_id', $product->brand_id ?? '') == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-2" id="unit_section">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="fw-bold" for="unit_group">{{ __('file.field.unit_group') }} <span
                                class="text-danger">*</span></label>
                        <select name="unit_group_id" id="unit_group" class="form-control selectpicker" required
                            data-live-search="true" {{ $isEdit ? 'disabled readonly' : '' }}>
                            <option value="">{{ __('file.option.select_unit_group') }}</option>
                            @isset($unitGroups)
                                @foreach ($unitGroups as $unitGroup)
                                    <option value="{{ $unitGroup->id }}"
                                        {{ old('unit_group_id', $product->unit_group_id ?? '') == $unitGroup->id ? 'selected' : '' }}>
                                        {{ $unitGroup->name }}</option>
                                @endforeach
                            @endisset
                        </select>
                        @if ($isEdit)
                            <input type="hidden" name="unit_group_id" value="{{ $product->unit_group_id ?? '' }}">
                        @endif
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="fw-bold" for="base_unit_id">{{ __('file.field.base_unit') }} <span
                                class="text-danger">*</span></label>
                        <select name="base_unit_id" id="base_unit_id" class="form-control" required>
                            <option value="">{{ __('file.option.select_base_unit') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="fw-bold" for="purchase_unit_id">{{ __('file.field.purchase_unit') }} </label>
                        <select name="purchase_unit_id" id="purchase_unit_id" class="form-control">
                            <option value="">{{ __('file.option.select_purchase_unit') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="fw-bold" for="sale_unit_id">{{ __('file.field.sale_unit') }} </label>
                        <select name="sale_unit_id" id="sale_unit_id" class="form-control">
                            <option value="">{{ __('file.option.select_sale_unit') }}</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <div id="unit_variables_container"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-2 d-none" id="digital_part">
            <div class="card-header bg-light pb-0">
                <h6 class="mb-0 text-primary" id="dynamic_digital_card_title">{{ __('file.field.digital_part') }}
                </h6>
            </div>
            <div class="card-body" id="digital_part_container">
            </div>
        </div>
        <div class="card mb-3 {{ isset($product->has_specification) && $product->has_specification ? '' : 'd-none' }} shadow-sm border-0"
            id="specification_section">
            <div class="card-body">
                <h5 class="card-title mb-3">{{ __('file.product_specification') }}</h5>
                <div id="product_specification_container">
                    @if (isset($product->specifications) && $product->specifications->count() > 0)
                        @foreach ($product->specifications as $specification)
                            <div class="spec-row row align-items-end mb-2">
                                <div class="col-md-5">
                                    <input type="text" name="specification_name[]"
                                        class="form-control form-control-sm" value="{{ $specification->key }}"
                                        placeholder="Name">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="specification_value[]"
                                        class="form-control form-control-sm" value="{{ $specification->value }}"
                                        placeholder="Value">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-spec w-100">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        {{-- যদি কোন স্পেসিফিকেশন না থাকে তবে একটি খালি রো দেখাবে (Default) --}}
                        <div class="spec-row row align-items-end mb-2">
                            <div class="col-md-5">
                                <input type="text" name="specification_name[]"
                                    class="form-control form-control-sm" placeholder="Name">
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="specification_value[]"
                                    class="form-control form-control-sm" placeholder="Value">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-spec w-100">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn btn-primary btn-sm mt-2" id="add_specification_button">
                    <i class="fa fa-plus"></i> {{ __('file.button.add_specification') }}
                </button>
            </div>
        </div>
        <div class="card mb-3 {{ isset($product->has_warranty) && $product->has_warranty ? '' : 'd-none' }} shadow-sm border-0"
            id="warranty_section">
            <div class="card-body">
                <h5 class="card-title mb-3">{{ __('file.warranty_details') }}</h5>
                <div id="warranty_container">
                    <div class="warranty-row row align-items-end mb-2">
                        <div class="col-md-6 mb-2">
                            <label class="fw-bold"
                                class="form-label small fw-bold">{{ __('file.field.warranty_type') }}</label>
                            <select name="warranty_type" class="form-control form-control-sm">
                                <option value="">{{ __('file.option.select_warranty_type') }}</option>
                                <option value="replacement"
                                    {{ old('warranty_type', $product->warranty_details['warranty_type'] ?? '') == 'replacement' ? 'selected' : '' }}>
                                    {{ __('file.option.replacement') }}</option>
                                <option value="service"
                                    {{ old('warranty_type', $product->warranty_details['warranty_type'] ?? '') == 'service' ? 'selected' : '' }}>
                                    {{ __('file.option.service') }}</option>
                                <option value="lifetime"
                                    {{ old('warranty_type', $product->warranty_details['warranty_type'] ?? '') == 'lifetime' ? 'selected' : '' }}>
                                    {{ __('file.option.lifetime') }}</option>
                                <option value="limited"
                                    {{ old('warranty_type', $product->warranty_details['warranty_type'] ?? '') == 'limited' ? 'selected' : '' }}>
                                    {{ __('file.option.limited') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="fw-bold"
                                class="form-label small fw-bold">{{ __('file.field.warranty_provider') }}</label>
                            <select name="warranty_provider" class="form-control form-control-sm">
                                <option value="">{{ __('file.option.select_warranty_provider') }}</option>
                                <option value="seller"
                                    {{ old('warranty_provider', $product->warranty_details['warranty_provider'] ?? '') == 'seller' ? 'selected' : '' }}>
                                    {{ __('file.option.seller') }}</option>
                                <option value="manufacturer"
                                    {{ old('warranty_provider', $product->warranty_details['warranty_provider'] ?? '') == 'manufacturer' ? 'selected' : '' }}>
                                    {{ __('file.option.manufacturer') }}</option>
                                <option value="thirdparty"
                                    {{ old('warranty_provider', $product->warranty_details['warranty_provider'] ?? '') == 'thirdparty' ? 'selected' : '' }}>
                                    {{ __('file.option.third_party') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="fw-bold"
                                class="form-label small fw-bold">{{ __('file.field.warranty_period') }} </label>
                            <input type="text" name="warranty_period" class="form-control form-control-sm"
                                placeholder="Period"
                                value="{{ old('warranty_period', $product->warranty_details['warranty_period'] ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="fw-bold"
                                class="form-label small fw-bold">{{ __('file.field.period_type') }} </label>
                            <select name="period_type" class="form-control form-control-sm">
                                <option value="">{{ __('file.option.select_period_type') }}</option>
                                <option value="day"
                                    {{ old('period_type', $product->warranty_details['period_type'] ?? '') == 'day' ? 'selected' : '' }}>
                                    {{ __('file.option.days') }}</option>
                                <option value="month"
                                    {{ old('period_type', $product->warranty_details['period_type'] ?? '') == 'month' ? 'selected' : '' }}>
                                    {{ __('file.option.months') }}</option>
                                <option value="year"
                                    {{ old('period_type', $product->warranty_details['period_type'] ?? '') == 'year' ? 'selected' : '' }}>
                                    {{ __('file.option.years') }}</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="fw-bold"
                                class="form-label small fw-bold">{{ __('file.field.warranty_terms_and_conditions') }}</label>
                            <textarea name="warranty_terms_and_conditions" class="form-control" rows="5">{{ old('warranty_terms_and_conditions', $product->warranty_details['warranty_terms_and_conditions'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-2">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label class="fw-bold" for="video_url">{{ __('file.field.video_url') }} </label>
                        <input type="text" name="video_url" class="form-control"
                            placeholder="https://youtube.com/watch?v=..."
                            value="{{ old('video_url', $product->video_url ?? '') }}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="fw-bold" for="thumbnail">{{ __('file.field.thumbnail') }}</label>
                        <input type="file" class="filepond-thumbnail" name="thumbnail" accept="image/*">
                    </div>

                    <div class="col-md-8">
                        <label class="fw-bold" for="gallery">{{ __('file.field.gallery_images') }}</label>
                        <input type="file" class="filepond-gallery" name="gallery[]" multiple accept="image/*">
                    </div>
                </div>
                @if ($isEdit)
                    <div class="row">
                        <div class="col-md-12 mb-2 d-flex justify-content-end align-items-center">
                            <button type="submit" name="action" value="update" class="btn btn-primary">
                                {{ __('file.button.update') }}
                            </button>
                            @if ($product->status == 'draft')
                                <button type="submit" name="action" value="publish" class="btn btn-success ms-2">
                                    {{ __('file.button.update_and_publish') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="row">
                        <div class="col-md-12 mb-2 d-flex justify-content-end align-items-center">
                            <button type="submit" name="action" value="draft" class="btn btn-warning me-2">
                                {{ __('file.button.save_as_draft') }}
                            </button>

                            <div class="btn-group" id="save_button_group">
                                <button type="submit" name="action" value="save" id="main_save_btn"
                                    class="btn btn-primary">
                                    {{ __('file.button.save') }}
                                </button>

                                <button type="button" id="dropdown_toggle"
                                    class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end" id="dropdown_menu">
                                    <li>
                                        <button type="submit" name="action" value="save_and_copy"
                                            class="dropdown-item">
                                            {{ __('file.button.save_and_copy') }}
                                        </button>
                                    </li>
                                    <li>
                                        <button type="submit" name="action" value="save_and_purchase"
                                            class="dropdown-item">
                                            {{ __('file.button.save_and_purchase') }}
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
