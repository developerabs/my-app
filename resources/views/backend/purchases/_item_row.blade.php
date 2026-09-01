@php
    $uid = $item->product_variant_id ? 'v-' . $item->product_variant_id : 'p-' . $item->product_id;
    $product = $item->product;
    $variant = $item->variant;
    
    $rawUnitDetails = $variant?->unit_details ?: $product?->unit_details;
    $unitDetails = is_array($rawUnitDetails) ? $rawUnitDetails : (json_decode($rawUnitDetails, true) ?? []);
    
    $defaultUnitId = $item->purchase_unit_id ?: ($product->purchase_unit_id ?: $product->base_unit_id);
    $isPartial = (isset($purchase) && in_array($purchase->purchase_status, ['partial', 'partial_received']));
    $hasImei = (bool) ($product->has_imei || !empty($item->imei_list));
    $isMasterBarcode = ($product->barcode_type === 'master');
    $isOpenModal = $hasImei || $isMasterBarcode;

    // Resolve formatted expiry date for input value
    $expiryDateVal = $item->expiry_date 
        ? $item->expiry_date->format('Y-m-d') 
        : ($item->batch?->expiry_date ? $item->batch->expiry_date->format('Y-m-d') : '');
@endphp

<tr id="purchase-row-{{ $uid }}" class="item-row" data-uid="{{ $uid }}">
    <!-- 1. Product Name & SKU -->
    <td>
        <div class="d-flex align-items-center">
            <input type="hidden" name="products[{{ $uid }}][product_id]" value="{{ $item->product_id }}">
            <span class="fw-bold d-block editProduct">{{ $product->name ?? 'Product' }}</span>
            @if($isOpenModal)
                <span class="badge bg-primary imei-list ms-2" onclick="ImeiBarcodeManager.openModal($(this).closest('tr'))" style="cursor:pointer;">
                    IMEIs / Barcode
                </span>
            @endif
        </div>
        <small class="text-muted">
            {{ $variant ? $variant->name . ' | ' : '' }}
            {{ $variant->sku ?? ($variant->code ?? ($product->sku ?? ($product->code ?? 'N/A'))) }}
        </small>
    </td>

    <!-- 2. Batch & Expire Date -->
    <td>
        <div class="d-flex gap-1">
            <input type="text" name="products[{{ $uid }}][batch_number]" 
                class="form-control form-control-sm batch-input" 
                value="{{ $item->batch_number ?? ($item->batch->batch_no ?? '') }}" 
                placeholder="Select Batch" 
                oninput="if(typeof ProductManager !== 'undefined') ProductManager.handleBatchChange($(this))" 
                onblur="if(typeof ProductManager !== 'undefined') ProductManager.handleBatchChange($(this))">
            <input type="hidden" name="products[{{ $uid }}][batch_id]" class="batch-id-hidden" value="{{ $item->product_batch_id }}">
            <input type="text" name="products[{{ $uid }}][expire_date]" 
                class="form-control form-control-sm expire-date-picker" 
                value="{{ $expiryDateVal }}" 
                placeholder="Exp Date" {{ !$product->has_expire_date ? 'disabled' : '' }}>
        </div>
    </td>

    <!-- 3. Quantity & Unit Selection -->
    <td>
        <div class="d-flex gap-1">
            <input type="number" name="products[{{ $uid }}][quantity]" 
                class="form-control form-control-sm item-qty text-center" 
                value="{{ (float)$item->quantity }}" min="0.0001" step="any" 
                onchange="if(typeof ProductManager !== 'undefined') ProductManager.handleQtyChange($(this).closest('tr'))">
            
            <input type="number" name="products[{{ $uid }}][received_qty]" 
                class="form-control form-control-sm received-qty text-center" 
                value="{{ (float)$item->received_qty }}" min="0" step="any" placeholder="Received" 
                style="{{ $isPartial ? '' : 'display:none;' }}" 
                onchange="if(typeof handleRcvQty === 'function') handleRcvQty($(this).closest('tr'))">
            
            <select name="products[{{ $uid }}][unit_id]" class="form-control form-control-sm item-unit-selector" style="width: auto;" onchange="handleUnitChange($(this))">
                @if(!empty($unitDetails))
                    @foreach($unitDetails as $u)
                        <option value="{{ $u['unit_id'] }}" {{ $u['unit_id'] == $defaultUnitId ? 'selected' : '' }}>
                            {{ $u['short_name'] ?? $u['name'] }}
                        </option>
                    @endforeach
                @else
                    <option value="{{ $defaultUnitId }}" selected>Unit</option>
                @endif
            </select>
        </div>
    </td>

    <!-- 4. Unit Cost Price -->
    <td>
        <div class="d-flex gap-1">
            <input type="hidden" name="products[{{ $uid }}][base_unit_price]" class="base-unit-price" value="{{ (float)$item->base_unit_cost }}">
            <input type="number" name="products[{{ $uid }}][price]" 
                class="form-control form-control-sm item-price text-end" 
                value="{{ number_format((float)$item->unit_cost, 2, '.', '') }}" step="any" min="0" 
                oninput="if(typeof ProductManager !== 'undefined') ProductManager.updateRowSubtotal($(this).closest('tr'))">
        </div>
    </td>

    <!-- 5. Discount -->
    <td>
        <div class="d-flex gap-1">
            <select name="products[{{ $uid }}][discount_method]" class="form-control form-control-sm discount-method" style="width: auto;" onchange="calculateDiscount($(this))">
                <option value="flat" {{ $item->discount_method == 'flat' ? 'selected' : '' }}>Flat</option>
                <option value="percentage" {{ $item->discount_method == 'percentage' ? 'selected' : '' }}>Percent</option>
            </select>
            <input type="number" name="products[{{ $uid }}][unit_discount]" 
                class="form-control form-control-sm item-unit-discount text-end" 
                min="0" step="any" value="{{ (float)$item->discount_rate }}" placeholder="Unit Disc" 
                oninput="calculateDiscount($(this))">
            <input type="number" name="products[{{ $uid }}][total_discount]" 
                class="form-control form-control-sm item-total-discount text-end" 
                value="{{ number_format((float)$item->total_discount, 2, '.', '') }}" readonly placeholder="Total">
        </div>
    </td>

    <!-- 6. Tax -->
    <td>
        <div class="d-flex gap-1">
            <select name="products[{{ $uid }}][tax_method]" class="form-control form-control-sm tax-method" style="width: 80px;" onchange="if(typeof ProductManager !== 'undefined') ProductManager.updateRowSubtotal($(this).closest('tr'))">
                <option value="exclusive" {{ $item->tax_method == 'exclusive' ? 'selected' : '' }}>Excl</option>
                <option value="inclusive" {{ $item->tax_method == 'inclusive' ? 'selected' : '' }}>Incl</option>
            </select>
            <input type="number" name="products[{{ $uid }}][tax_rate]" 
                class="form-control form-control-sm tax-rate text-end" 
                min="0" step="any" placeholder="Rate%" value="{{ (float)$item->tax_rate }}" 
                oninput="if(typeof ProductManager !== 'undefined') ProductManager.updateRowSubtotal($(this).closest('tr'))">
            <input type="text" name="products[{{ $uid }}][tax_total]" 
                class="form-control form-control-sm tax-total text-end" 
                readonly value="{{ number_format((float)$item->tax_amount, 2, '.', '') }}">
        </div>
    </td>

    <!-- 7. Line Subtotal -->
    <td class="text-end align-middle fw-bold">
        <span class="item-subtotal">{{ number_format((float)$item->subtotal, 2, '.', '') }}</span>
    </td>
    <input type="hidden" class="item-subtotal-hidden" name="products[{{ $uid }}][subtotal]" value="{{ (float)$item->subtotal }}">

    <!-- 8. Remove Action -->
    <td class="text-center align-middle">
        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="$(this).closest('tr').remove(); if(typeof ProductManager !== 'undefined') ProductManager.updateGrandTotal($('#purchase-table'));">
            <i class="fa fa-trash"></i>
        </button>
    </td>
    <input type="hidden" class="item-imeis" name="products[{{ $uid }}][imei_list]" value="{{ $item->imei_list }}">
    <input type="hidden" class="item-barcodes" name="products[{{ $uid }}][barcodes]" value="{{ $item->barcodes }}">
</tr>