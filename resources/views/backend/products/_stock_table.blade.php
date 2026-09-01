@php
    // Filter existing stocks for this specific variant or for simple product (null variant)
    $filteredStocks = $existingStocks->where('product_variant_id', $variantId);

    // 💡 1. Prioritize Variant's own unit_details over Parent Product's unit_details
    $rawUnitDetails = ($variant && !empty($variant->unit_details)) 
        ? $variant->unit_details 
        : ($product->unit_details ?? []);

    $resolvedUnitDetails = is_array($rawUnitDetails) ? $rawUnitDetails : (json_decode($rawUnitDetails, true) ?? []);

    // 💡 2. Safe Price & Cost Resolution
    $rowCost = $variant ? $variant->cost : ($product->cost ?? 0);
    $rowPrice = $variant ? $variant->price : ($product->price ?? 0);
    $rowWholesale = $variant ? $variant->wholesale_price : ($product->wholesale_price ?? 0);

    // 💡 3. Sorted Units based on Variant's specific formula/values (e.g. 24 or 6)
    $sortedUnits = get_sorted_units($resolvedUnitDetails);
@endphp

<div class="border rounded overflow-hidden stock-table-container" 
     data-unit-details='@json($resolvedUnitDetails)'
     data-variant-id="{{ $variantId ?? '' }}">
    
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="bg-light fs-11 text-uppercase text-center">
                <tr>
                    <th style="width: 15%;">{{ __('file.field.branch') }}</th>
                    <th style="width: 10%;">{{ __('file.field.batch') }}</th>
                    <th style="width: 10%;">{{ __('file.field.expiry_date') }}</th>
                    <th style="width: 10%;">{{ __('file.field.cost') }}</th>
                    <th style="width: 10%;">{{ __('file.field.price') }}</th>
                    <th style="width: 10%;">{{ __('file.field.wholesale') }}</th>
                    <th style="width: 25%;">{{ __('file.field.quantity') }}</th>
                    <th style="width: 5%;">Action</th>
                </tr>
            </thead>
            <tbody class="stock-body">
                @if ($filteredStocks->count() > 0)
                    @foreach ($filteredStocks as $stock)
                        @php
                            $rowId = 'existing_' . $stock->id;
                            $batchNo = $stock->batch ? $stock->batch->batch_no : '';
                            $expiryDate = ($stock->batch && $stock->batch->expiry_date)
                                ? date('d-m-Y', strtotime($stock->batch->expiry_date))
                                : '';
                            $masterBarcode = '';
                            if ($stock->batch) {
                                $barcodeModel = $stock->batch->masterBarcode; 
                                $masterBarcode = $barcodeModel ? $barcodeModel->barcode : '';
                            }
                        @endphp

                        {{-- Main Row for Branch, Batch, Prices, and Quantity --}}
                        <tr class="stock-row" data-row-id="{{ $rowId }}">
                            <td>
                                <input type="hidden" name="stocks[{{ $rowId }}][id]" value="{{ $stock->id }}">
                                <select name="stocks[{{ $rowId }}][branch_id]" class="form-select select2-simple" required>
                                    <option value="">Select Branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $stock->branch_id == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($masterBarcode || ($product->barcode_type ?? '') === 'master')
                                    <div class="mt-2">
                                        <input type="text" name="stocks[{{ $rowId }}][master_barcode]" 
                                            class="form-control form-control-sm master-barcode-scanner" 
                                            value="{{ $masterBarcode }}"
                                            placeholder="Scan Master Barcode" 
                                            autocomplete="off">
                                    </div>
                                @endif
                            </td>
                            <td>
                                <input type="text" name="stocks[{{ $rowId }}][batch_no]" class="form-control form-control-sm"
                                    value="{{ $batchNo }}" placeholder="Batch#">
                            </td>
                            <td>
                                @if ($product->has_expire_date)
                                    <input type="text" name="stocks[{{ $rowId }}][expiry_date]"
                                        class="form-control form-control-sm expiry-datepicker" value="{{ $expiryDate }}" required>
                                @else
                                    <input type="text" class="form-control form-control-sm expiry-datepicker" disabled placeholder="N/A">
                                    <input type="hidden" name="stocks[{{ $rowId }}][expiry_date]" value="0000-00-00">
                                @endif
                            </td>
                            <td>
                                <input type="number" name="stocks[{{ $rowId }}][cost]" class="form-control form-control-sm text-end"
                                    value="{{ $stock->batch ? $stock->batch->cost : ($stock->prices ? $stock->prices->cost : $rowCost) }}" step="any">
                            </td>
                            <td>
                                <input type="number" name="stocks[{{ $rowId }}][price]" class="form-control form-control-sm text-end"
                                    value="{{ $stock->batch ? $stock->batch->price : ($stock->prices ? $stock->prices->price : $rowPrice) }}" step="any">
                            </td>
                            <td>
                                <input type="number" name="stocks[{{ $rowId }}][wholesale_price]" class="form-control form-control-sm text-end"
                                    value="{{ $stock->batch ? $stock->batch->wholesale_price : ($stock->prices ? $stock->prices->wholesale_price : $rowWholesale) }}" step="any">
                            </td>

                            {{-- Dynamic Quantity Column rendered from Variant's Own Units --}}
                            <td class="bg-light-transparent" style="min-width: 180px;">
                                <div class="d-flex flex-wrap gap-1 justify-content-center">
                                    @foreach ($sortedUnits as $unit)
                                        <div class="compound-unit-item" style="width: 50px;">
                                            <input type="number"
                                                class="form-control form-control-sm text-center compound-qty px-1"
                                                data-unit-id="{{ $unit['unit_id'] }}" 
                                                placeholder="0"
                                                step="any"
                                                style="font-size: 11px;">
                                            <div class="unit-label text-center bg-light border border-top-0"
                                                style="font-size: 9px; padding: 1px 0;">{{ $unit['short_name'] ?? $unit['name'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                                <input type="hidden" name="stocks[{{ $rowId }}][quantity]" class="final-base-qty" value="{{ $stock->quantity }}">
                                <input type="hidden" name="stocks[{{ $rowId }}][product_variant_id]" value="{{ $stock->product_variant_id }}">
                                <div class="text-center mt-1">
                                    <span class="badge bg-primary-transparent total-display" style="font-size: 10px;">Total: {{ number_format($stock->quantity, 2) }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-icon btn-sm btn-danger-transparent remove-stock-group">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>

                        {{-- Expanded Full-Width IMEI Sub-Row --}}
                        @if ($product->has_imei)
                            @php
                                $dbImeis = ($stock->batch && $stock->batch->imeis) ? $stock->batch->imeis : collect([]);
                                $dbImeiCount = $dbImeis->count();
                                $targetQuantity = (int)$stock->quantity;
                            @endphp
                            <tr class="imei-row" data-owner-id="{{ $rowId }}">
                                <td colspan="8" class="p-3 bg-light-transparent">
                                    <div class="card card-body shadow-none border-dashed mb-0 p-2 bg-white">
                                        <div class="d-flex align-items-center justify-content-between border-bottom pb-1 mb-2">
                                            <span class="fw-bold text-dark fs-11 text-uppercase">
                                                <i class="fa-solid fa-barcode text-primary me-1"></i> IMEI / Serial Inventory Allocation
                                            </span>
                                            <button type="button" class="btn btn-xs btn-outline-primary add-single-imei px-2 py-0" style="font-size: 10px;">
                                                <i class="fa-solid fa-plus me-1"></i> Add Manual Field
                                            </button>
                                        </div>
                                        <div class="imei-wrapper d-flex flex-wrap gap-2 pt-1">
                                            @foreach ($dbImeis as $imei)
                                                <div class="d-flex align-items-center dynamic-imei-group bg-light rounded p-1 border">
                                                    <input type="text"
                                                        name="stocks[{{ $rowId }}][imeis][]"
                                                        class="form-control form-control-sm imei-input border-0 bg-transparent p-0"
                                                        value="{{ $imei->imei_number }}" placeholder="IMEI#"
                                                        style="width: 140px; font-size: 11px;">
                                                    <input type="hidden" name="stocks[{{ $rowId }}][imei_ids][]" value="{{ $imei->id }}">
                                                    <button type="button" class="btn btn-xs text-danger remove-imei-field ms-1 p-0">
                                                        <i class="fa-solid fa-circle-xmark"></i>
                                                    </button>
                                                </div>
                                            @endforeach

                                            @if ($targetQuantity > $dbImeiCount)
                                                @for ($i = 0; $i < ($targetQuantity - $dbImeiCount); $i++)
                                                    <div class="d-flex align-items-center dynamic-imei-group bg-light rounded p-1 border">
                                                        <input type="text" name="stocks[{{ $rowId }}][imeis][]" 
                                                            class="form-control form-control-sm imei-input border-0 bg-transparent p-0" 
                                                            placeholder="IMEI#" style="width: 140px; font-size: 11px;">
                                                        <button type="button" class="btn btn-xs text-danger remove-imei-field ms-1 p-0">
                                                            <i class="fa-solid fa-circle-xmark"></i>
                                                        </button>
                                                    </div>
                                                @endfor
                                            @endif
                                        </div>
                                        <div class="imei-error text-danger fs-11 fw-bold mt-2 ps-1" style="display: none;">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> Duplicate IMEI detected in this stock row!
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
    <div class="p-2 bg-light border-top">
        <button type="button" class="btn btn-sm btn-primary-transparent add-stock-row"
            data-variant-id="{{ $variantId ?? '' }}" 
            data-cost="{{ $rowCost }}" 
            data-price="{{ $rowPrice }}"
            data-wholesale="{{ $rowWholesale }}">
            <i class="fa-solid fa-plus-circle me-1"></i> Add Row
        </button>
    </div>
</div>