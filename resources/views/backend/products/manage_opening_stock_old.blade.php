@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.manage_opening_stock') }} -
    {{ $general_settings['site_title'] ?? 'SheraziPOS' }}
@endsection

@push('css')
    <style>
        .compound-qty::-webkit-inner-spin-button,
        .compound-qty::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .compound-qty {
            -moz-appearance: textfield;
            border-color: #e9ebec;
        }

        /* রো এর হাইট কন্ট্রোল করা */
        .stock-row td {
            vertical-align: middle;
            padding: 8px 4px;
        }

        .unit-label {
            color: #555;
            font-weight: 600;
            text-transform: uppercase;
        }

        .compound-unit-item:focus-within .unit-label {
            background-color: #5c67f7 !important;
            color: #fff;
            border-color: #5c67f7;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.manage_opening_stock') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.manage_opening_stock_desc') }}
        @endslot
        @slot('button')
            <a href="{{ route('products.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-list me-1"></i> {{ __('file.button.list') }} {{ __('file.product') }}
            </a>
        @endslot
    @endcomponent

    <form action="{{ route('products.openingStock.update', $product->id) }}" method="POST" id="product-opening-stock-form">
        @csrf

        <div class="card custom-card shadow-sm mb-4">
            {{-- Product Info Header --}}
            <div class="card-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center w-100">
                    <div class="avatar avatar-xxl border p-1 bg-light me-3 flex-shrink-0" style="width: 70px; height: 70px;">
                        <img src="{{ $product->thumb_url ?? url('backend/assets/images/no-image.png') }}" alt="Product"
                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="fw-bold mb-1 text-primary text-truncate">{{ $product->name }}</h6>
                        <span
                            class="badge bg-dark-transparent mb-1">#{{ str_pad($product->code ?? $product->id, 5, '0', STR_PAD_LEFT) }}</span>
                        <div class="text-muted fs-12">BASE SKU: <span class="fw-bold text-dark">{{ $product->sku }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                {{-- Stock Entry Section --}}
                @if ($product->variants->count() > 0)
                    @foreach ($product->variants as $index => $variant)
                        <div class="variant-row-wrapper {{ !$loop->first ? 'mt-5' : '' }}">
                            <div class="d-flex align-items-center mb-2 px-1">
                                <span class="badge bg-primary-transparent me-2">{{ $index + 1 }}</span>
                                <h6 class="mb-0 fw-bold text-dark">
                                    Variant: <span class="text-primary">{{ $variant->name }}</span>
                                    <small class="text-muted ms-2">(SKU: {{ $variant->sku }} / CODE:
                                        {{ $variant->code }})</small>
                                </h6>
                            </div>
                            @include('backend.products._stock_table', [
                                'variantId' => $variant->id,
                                'variant' => $variant,
                            ])
                        </div>
                    @endforeach
                @else
                    <div class="mt-2">
                        <h6 class="mb-3 fw-bold text-dark px-1">
                            <i
                                class="fa-solid fa-box-open text-primary me-2"></i>{{ __('file.title.product_opening_stock') }}
                        </h6>
                        @include('backend.products._stock_table', ['variantId' => null, 'variant' => null])
                    </div>
                @endif
            </div>

            <div class="card-footer bg-light text-end">
                <a href="{{ route('products.index') }}" class="btn btn-secondary btn-lg px-5 shadow me-2">{{ __('file.button.cancel') }}</a>
                <button type="submit" class="btn btn-success btn-lg px-5 shadow">
                    <i class="fa-solid fa-save me-2"></i> {{ __('file.button.update') }} {{ __('file.opening_stock') }}
                </button>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            const unitEngine = new CompoundUnitCalculator(@json($product->unit_details ?? []));
            const isExpiryDate = {{ $product->has_expire_date ? 'true' : 'false' }};

            // 1. Backward distribution: Split database base quantity into structured input boxes on load
            $('.stock-row').each(function() {
                const $row = $(this);
                const finalQty = parseFloat($row.find('.final-base-qty').val()) || 0;

                const baseUnit = Object.values(unitEngine.unitDetails).find(u => !u.base_unit_id);
                const baseUnitName = baseUnit ? baseUnit.short_name : 'Unit';

                // Distribute the data using the shared class engine method
                unitEngine.distributeQtyToInputs($row, finalQty);
                $row.find('.total-display').text(`Total: ${finalQty.toFixed(2)} ${baseUnitName}`);
            });

            // Initialize standard date pickers for pre-rendered elements
            $('.expiry-datepicker').flatpickr({
                appendTo: document.body,
                dateFormat: "d-m-Y",
                static: false,
                allowInput: true,
            });

            let isImeiTracked = {{ $product->has_imei ? 'true' : 'false' }};
            // 2. Factory blueprint for mapping dynamic subsequent lines
            function generateStockRow(variantId, cost, price, wholesale) {
                const rowId = 'new_' + Date.now() + Math.floor(Math.random() * 1000);
                const sortedUnits = unitEngine.getSortedUnits();

                const isMasterBarcode = @json(($product->barcode_type ?? 'standard') === 'master');
                let masterBarcodeField = '';
                if (isMasterBarcode) {
                    masterBarcodeField = `
                        <div class="mt-2">
                            <input type="text" name="stocks[${rowId}][master_barcode]" 
                                class="form-control form-control-sm master-barcode-scanner" 
                                placeholder="Scan Master Barcode" 
                                autocomplete="off">
                        </div>`;
                }

                let unitInputs = '';
                sortedUnits.forEach(unit => {
                    unitInputs += `
                        <div class="compound-unit-item" style="width: 50px;">
                            <input type="number" class="form-control form-control-sm text-center compound-qty px-1" data-unit-id="${unit.unit_id}" placeholder="0" style="font-size: 11px;">
                            <div class="unit-label text-center bg-light border border-top-0" style="font-size: 9px; padding: 1px 0;">${unit.short_name}</div>
                        </div>`;
                });

                const expiryInput = isExpiryDate ?
                    `<input type="text" name="stocks[${rowId}][expiry_date]" class="form-control expiry-datepicker" required>` :
                    `<input type="text" name="stocks[${rowId}][expiry_date]" class="form-control expiry-datepicker" disabled placeholder="N/A"><input type="hidden" name="stocks[${rowId}][expiry_date]" value="0000-00-00">`;

                // 1. The Main Row Structure
                const mainRowHtml = `
                    <tr class="stock-row" data-row-id="${rowId}">
                        <td>
                            <select name="stocks[${rowId}][branch_id]" class="form-select select2-simple" required>
                                <option value="">Select Branch</option>
                                @foreach ($branches as $branch) <option value="{{ $branch->id }}">{{ $branch->name }}</option> @endforeach
                            </select>
                            ${masterBarcodeField}
                        </td>
                        <td><input type="text" name="stocks[${rowId}][batch_no]" class="form-control" placeholder="Batch#"></td>
                        <td>${expiryInput}</td>
                        <td><input type="number" name="stocks[${rowId}][cost]" class="form-control text-end" value="${cost}" step="any"></td>
                        <td><input type="number" name="stocks[${rowId}][price]" class="form-control text-end" value="${price}" step="any"></td>
                        <td><input type="number" name="stocks[${rowId}][wholesale_price]" class="form-control text-end" value="${wholesale}" step="any"></td>
                        <td class="bg-light-transparent" style="min-width: 180px;">
                            <div class="d-flex flex-wrap gap-1 justify-content-center">${unitInputs}</div>
                            <input type="hidden" name="stocks[${rowId}][quantity]" class="final-base-qty" value="0">
                            <div class="text-center mt-1"><span class="badge bg-primary-transparent total-display" style="font-size: 10px;">Total: 0</span></div>
                        </td>
                        <input type="hidden" name="stocks[${rowId}][product_variant_id]" value="${variantId}">
                        <td class="text-center">
                            <button type="button" class="btn btn-icon btn-sm btn-danger-transparent remove-stock-group"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>`;

                // 2. The Expanded Full-Width Sub-Row Structure (Only if IMEI tracked)
                let subRowHtml = '';
                if (isImeiTracked) {
                    subRowHtml = `
                        <tr class="imei-row" data-owner-id="${rowId}">
                            <td colspan="8" class="p-3 bg-light-transparent">
                                <div class="card card-body shadow-none border-dashed mb-0 p-2 bg-white">
                                    <div class="d-flex align-items-center justify-content-between border-bottom pb-1 mb-2">
                                        <span class="fw-bold text-dark fs-11 text-uppercase"><i class="fa-solid fa-barcode text-primary me-1"></i> IMEI / Serial Inventory Allocation</span>
                                        <button type="button" class="btn btn-xs btn-outline-primary add-single-imei px-2 py-0" style="font-size: 10px;"><i class="fa-solid fa-plus me-1"></i>Add Manual Field</button>
                                    </div>
                                    <div class="imei-wrapper d-flex flex-wrap gap-2 pt-1"></div>
                                    <div class="imei-error text-danger fs-11 fw-bold mt-2 ps-1" style="display: none;"><i class="fa-solid fa-circle-exclamation me-1"></i>Duplicate IMEI detected in this stock row!</div>
                                </div>
                            </td>
                        </tr>`;
                }

                return mainRowHtml + subRowHtml;
            }

            // 3. Realtime aggregation math pipeline
            $(document).on('input', '.compound-qty', function() {
                const $row = $(this).closest('.stock-row');
                const rowId = $row.data('row-id');
                const $subRow = $(`.imei-row[data-owner-id="${rowId}"]`);
                
                let totalBaseQty = 0;
                $row.find('.compound-qty').each(function() {
                    const unitId = $(this).data('unit-id');
                    const qty = parseFloat($(this).val()) || 0;
                    totalBaseQty += qty * unitEngine.calculateRatio(unitId);
                });

                $row.find('.final-base-qty').val(totalBaseQty.toFixed(4));
                $row.find('.total-display').text(`Total: ${totalBaseQty.toFixed(2)}`);

                if (isImeiTracked && $subRow.length) {
                    syncImeiSubRowFields($row, $subRow, Math.floor(totalBaseQty));
                }
            });

            function syncImeiSubRowFields($row, $subRow, targetCount) {
                const $wrapper = $subRow.find('.imei-wrapper');
                const currentCount = $wrapper.find('.dynamic-imei-group').length;
                const rowId = $row.data('row-id');

                if (targetCount > currentCount) {
                    for (let i = currentCount; i < targetCount; i++) {
                        $wrapper.append(`
                            <div class="d-flex align-items-center dynamic-imei-group bg-light rounded p-1 border shadow-sm">
                                <input type="text" name="stocks[${rowId}][imeis][]" class="form-control form-control-sm imei-input border-0 bg-transparent p-0 mx-1" placeholder="IMEI#" required style="width: 140px; font-size: 11px;">
                                <button type="button" class="btn btn-xs text-danger remove-imei-field p-0 ms-1"><i class="fa-solid fa-circle-xmark"></i></button>
                            </div>
                        `);
                    }
                } else if (targetCount < currentCount) {
                    for (let i = currentCount; i > targetCount; i--) {
                        $wrapper.find('.dynamic-imei-group').last().remove();
                    }
                }
                validateUniqueImeis($subRow);
            }

            $(document).on('click', '.add-single-imei', function() {
                const $subRow = $(this).closest('.imei-row');
                const rowId = $subRow.data('data-owner-id') || $subRow.attr('data-owner-id');
                const $row = $(`.stock-row[data-row-id="${rowId}"]`);
                const $wrapper = $subRow.find('.imei-wrapper');

                $wrapper.append(`
                    <div class="d-flex align-items-center dynamic-imei-group bg-light rounded p-1 border shadow-sm">
                        <input type="text" name="stocks[${rowId}][imeis][]" class="form-control form-control-sm imei-input border-0 bg-transparent p-0 mx-1" placeholder="IMEI#" required style="width: 140px; font-size: 11px;">
                        <button type="button" class="btn btn-xs text-danger remove-imei-field p-0 ms-1"><i class="fa-solid fa-circle-xmark"></i></button>
                    </div>
                `);

                const newCount = $wrapper.find('.imei-input').length;
                unitEngine.distributeQtyToInputs($row, newCount);
                $row.find('.final-base-qty').val(newCount);
                $row.find('.total-display').text(`Total: ${newCount.toFixed(2)}`);
            });

            $(document).on('click', '.remove-imei-field', function() {
                const $subRow = $(this).closest('.imei-row');
                const rowId = $subRow.data('owner-id');
                const $row = $(`.stock-row[data-row-id="${rowId}"]`);
                
                $(this).closest('.dynamic-imei-group').remove();

                const newCount = $subRow.find('.imei-input').length;
                unitEngine.distributeQtyToInputs($row, newCount);
                $row.find('.final-base-qty').val(newCount);
                $row.find('.total-display').text(`Total: ${newCount.toFixed(2)}`);
                
                validateUniqueImeis($subRow);
            });

            // Row deletion handler for master-detail combo row
            $(document).on('click', '.remove-stock-group', function() {
                const $row = $(this).closest('.stock-row');
                const rowId = $row.data('row-id');
                $(`.imei-row[data-owner-id="${rowId}"]`).remove();
                $row.remove();
            });

            $(document).on('input', '.imei-input', function() {
                validateGlobalUniqueImeis();
            });

            // Validator Engine: Scans ALL IMEI fields globally across the entire form to ensure absolute uniqueness
            function validateGlobalUniqueImeis() {
                if (!isImeiTracked) return;

                const seenImeis = {};
                let hasAnyDuplicate = false;

                // Reset all styling flags before starting the global scan
                $('.imei-input').removeClass('is-invalid').closest('.dynamic-imei-group').removeClass('border-danger');
                $('.imei-error').slideUp(100);

                // Loop through every single IMEI input field on the page
                $('.imei-input').each(function() {
                    const val = $(this).val().trim();
                    
                    if (val !== '') {
                        if (seenImeis[val]) {
                            // If this IMEI has been seen before, flag both the original and the duplicate instance
                            $(this).addClass('is-invalid').closest('.dynamic-imei-group').addClass('border-danger');
                            $(seenImeis[val]).addClass('is-invalid').closest('.dynamic-imei-group').addClass('border-danger');
                            
                            // Display the error text container specifically inside their parent row cards
                            $(this).closest('.card-body').find('.imei-error').slideDown(150);
                            $(seenImeis[val]).closest('.card-body').find('.imei-error').slideDown(150);
                            
                            hasAnyDuplicate = true;
                        } else {
                            // Store reference to the first input element containing this unique value
                            seenImeis[val] = this;
                        }
                    }
                });

                // Toggle master save button compliance rules based on global health state
                if (hasAnyDuplicate) {
                    $('#product-opening-stock-form').find('button[type="submit"]').prop('disabled', true);
                } else {
                    // Double check to make sure absolutely no other structural validation conflicts remain active
                    if ($('.is-invalid').length === 0) {
                        $('#product-opening-stock-form').find('button[type="submit"]').prop('disabled', false);
                    }
                }
            }

            // 4. Dom structural appenders
            $(document).on('click', '.add-stock-row', function() {
                const vId = $(this).data('variant-id') || '';
                const vCost = $(this).data('cost') || 0;
                const vPrice = $(this).data('price') || 0;
                const vWholesale = $(this).data('wholesale') || 0;

                // Append raw generated layout structure dynamically to the active table block
                $(this).closest('.border').find('.stock-body').append(
                    generateStockRow(vId, vCost, vPrice, vWholesale)
                );

                // Refresh dynamically loaded layout configuration for datepickers
                $('.expiry-datepicker').flatpickr({
                    appendTo: document.body,
                    dateFormat: "d-m-Y",
                    static: false,
                    allowInput: true,
                });
            });

            // Row extraction mechanics
            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
            });

            // Enforce form compliance layout rules
            $('#product-opening-stock-form').on('submit', function(e) {
                if ($('.stock-row').length === 0) {
                    e.preventDefault();
                    alert('Please add at least one stock entry.');
                }
            });
        });
    </script>
@endpush
