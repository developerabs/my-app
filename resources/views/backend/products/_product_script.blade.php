<script>
    $(document).ready(function() {

        function calculatePrice() {
            let margin = parseFloat($('#profit_margin').val()) || 0;
            let cost = parseFloat($('#product_cost').val()) || 0;
            let currentPrice = parseFloat($('#product_price').val()) || 0;

            if (margin < 0) {
                margin = 0;
                $('#profit_margin').val(0);
            }

            let expectedPrice = cost + (cost * (margin / 100));

            if (currentPrice < expectedPrice || currentPrice === 0) {
                $('#product_price').val(expectedPrice.toFixed(2));
            }
        }

        $(document).on('input', '#profit_margin, #product_cost', function() {
            calculatePrice();
        });

        $(document).on('blur', '#product_price', function() {
            let margin = parseFloat($('#profit_margin').val()) || 0;
            let cost = parseFloat($('#product_cost').val()) || 0;
            let inputPrice = parseFloat($(this).val()) || 0;

            let minAllowedPrice = cost + (cost * (margin / 100));

            if (inputPrice < minAllowedPrice) {
                showFloatingAlert('Price cannot be less than the required profit margin (' + minAllowedPrice.toFixed(2) + ')');
                $(this).val(minAllowedPrice.toFixed(2));
            }
        });

        function initDynamicSelect2(selector, storeRoute, extraData = {}) {
            const selectElement = $(selector).select2({
                placeholder: "Select or type to add...",
                tags: true,
                allowClear: true,
                width: '100%',
                createTag: function(params) {
                    var term = $.trim(params.term);
                    if (term === '') return null;
                    return {
                        id: term,
                        text: term,
                        isNew: true
                    };
                }
            });

            selectElement.on('select2:select', function(e) {
                var data = e.params.data;
                if (data.isNew) {
                    let postData = {
                        name: data.text,
                        is_active: 1,
                        ...extraData
                    };

                    $.ajax({
                        url: storeRoute,
                        method: "POST",
                        data: postData,
                        success: function(response) {
                            selectElement.find('option[value="' + data.id + '"]').remove();
                            var newOption = new Option(data.text, response.id, true, true);
                            selectElement.append(newOption).trigger('change');
                            showFloatingAlert('success', 'Created successfully.');
                        },
                        error: function(xhr) {
                            selectElement.find('option[value="' + data.id + '"]').remove();
                            selectElement.trigger('change');
                            showFloatingAlert('error', 'Could not create entry.');
                            console.error(xhr);
                        }
                    });
                }
            });

            return selectElement;
        }

        const categorySelect = initDynamicSelect2('#category_id', "{{ route('categories.store') }}", {
            category_type_id: "{{ $typeId ?? '' }}"
        });
        const brandSelect = initDynamicSelect2('#brand_id', "{{ route('brands.store') }}");
        const genericSelect = initDynamicSelect2('#generic_id', "{{ route('generics.store') }}");

        $('#unit_group').select2();

        $('select[name="unit_group_id"]').on('change', function() {
            $('#unit_variables_container').empty();
            const groupId = $(this).val();
            if (groupId){
                UnitManager.fetchBaseUnits(groupId, 'select[name="base_unit_id"]');
            };
        });

        $('select[name="base_unit_id"]').on('change', function() {
            $('#unit_variables_container').empty();
            const baseUnitId = $(this).val();
            const baseUnitName = $(this).find('option:selected').text();
            if (baseUnitId){
                UnitManager.fetchSubUnits(baseUnitId, baseUnitName, '#unit_variables_container', 'select[name="purchase_unit_id"]', 'select[name="sale_unit_id"]');
            }
        });

        $('#toggle_details').on('click', function() {
            const section = $('#additional_details_section');
            const isHidden = section.hasClass('d-none');
            section.toggleClass('d-none');
            $(this).html(isHidden ? '<i class="fa fa-minus"></i> Hide Details' : '<i class="fa fa-plus"></i> Add More Details');
        });

        $('#has_specification').on('change', function() {
            $('#specification_section').toggleClass('d-none', !$(this).is(':checked'));
        });

        $('#has_opening_stock').on('change', function() {
            if(!$('#has_variants').is(':checked')) {
                $('#opening_stock_value').toggleClass('d-none', !$(this).is(':checked'));
            }
        });

        $('#has_warranty').on('change', function() {
            $('#warranty_section').toggleClass('d-none', !$(this).is(':checked'));
        });

        // ==================== COMBO BUILDER & CLEAN PRICING LOGIC ====================

        function roundToTwo(num) {
            return +(Math.round(num + "e+2")  + "e-2");
        }

        window.addComboItemRow = function(item, selectedUnitId = null, qty = 1) {
            let productId = item.product_id;
            let variantId = item.product_variant_id || '';

            // ১. ডুপ্লিকেট আইটেম চেক ও অটো-মার্জিং
            let $existingRow = null;
            $('.combo-item-row').each(function() {
                let rowProdId = $(this).find('input[name*="[product_id]"]').val();
                let rowVarId = $(this).find('input[name*="[product_variant_id]"]').val();

                if (rowProdId == productId && rowVarId == variantId) {
                    $existingRow = $(this);
                    return false;
                }
            });

            if ($existingRow) {
                let $qtyInput = $existingRow.find('.combo-qty');
                let currentQty = parseFloat($qtyInput.val()) || 0;
                let newQty = currentQty + (parseFloat(qty) || 1);

                $qtyInput.val(newQty);

                $existingRow.css('background-color', '#fffec8');
                setTimeout(function() {
                    $existingRow.css('background-color', '');
                }, 1000);

                updateComboRowCost($existingRow);

                if (typeof showFloatingAlert === 'function') {
                    showFloatingAlert('info', 'Item quantity increased to ' + newQty);
                }
                return;
            }

            $('#empty_combo_row').remove();
            let rowIndex = 'item_' + Date.now() + Math.floor(Math.random() * 100);

            let unitDetails = item.unit_details;
            if (typeof unitDetails === 'string') {
                try { unitDetails = JSON.parse(unitDetails); } catch (e) { unitDetails = {}; }
            }

            let baseCost = parseFloat(item.cost) || 0;
            let basePrice = parseFloat(item.price) || 0;

            // 💡 সার্ভিস বা নো-ইউনিট প্রডাক্টের জন্য ড্রপডাউন না দেখিয়ে N/A ব্যাজ শো করা
            let unitSelectHtml = '';
            if (unitDetails && typeof unitDetails === 'object' && Object.keys(unitDetails).length > 0) {
                let unitOptionsHtml = '';
                $.each(unitDetails, function(uId, u) {
                    let uName = u.name || u.short_name || 'Unit';
                    let uShort = u.short_name ? ` (${u.short_name})` : '';
                    let isSel = (selectedUnitId === uId || item.sale_unit_id === uId || item.base_unit_id === uId) ? 'selected' : '';

                    unitOptionsHtml += `<option value="${uId}" ${isSel}>${uName}${uShort}</option>`;
                });

                unitSelectHtml = `
                    <select name="combo_items[${rowIndex}][unit_id]" class="form-select form-select-sm combo-unit-select" required>
                        ${unitOptionsHtml}
                    </select>`;
            } else {
                // সার্ভিস বা ডিজিটাল প্রডাক্টের জন্য (কোনো required ড্রপডাউন থাকবে না)
                unitSelectHtml = `
                    <span class="badge bg-light text-muted border px-2 py-1 fs-11">N/A (No Unit)</span>
                    <input type="hidden" name="combo_items[${rowIndex}][unit_id]" value="">`;
            }

            let rowHtml = `
            <tr class="combo-item-row" data-row-id="${rowIndex}">
                <td>
                    <div class="fw-bold text-dark fs-12">${item.name}</div>
                    <small class="text-muted">SKU: ${item.sku}</small>
                    <input type="hidden" name="combo_items[${rowIndex}][product_id]" value="${item.product_id}">
                    <input type="hidden" name="combo_items[${rowIndex}][product_variant_id]" value="${item.product_variant_id || ''}">
                    <input type="hidden" name="combo_items[${rowIndex}][unit_cost]" class="item-unit-cost" value="${baseCost}">
                    <input type="hidden" name="combo_items[${rowIndex}][unit_price]" class="item-unit-price" value="${basePrice}">
                </td>
                <td>
                    ${unitSelectHtml}
                </td>
                <td>
                    <input type="number" step="any" min="0.0001" name="combo_items[${rowIndex}][quantity]" 
                        class="form-control form-control-sm text-center combo-qty fw-bold" value="${qty}" required>
                </td>
                <td class="text-end fw-semibold fs-12 item-unit-cost-display">${baseCost.toFixed(2)}</td>
                <td class="text-end fw-semibold fs-12 text-success item-unit-price-display">${basePrice.toFixed(2)}</td>
                <td class="text-end fw-bold text-dark fs-12 item-total-cost-display">${(baseCost * qty).toFixed(2)}</td>
                <td class="text-end fw-bold text-success fs-12 item-total-price-display">${(basePrice * qty).toFixed(2)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-combo-row"><i class="fa fa-trash"></i></button>
                </td>
            </tr>`;

            let $row = $(rowHtml);

            $row.data('unit-details', unitDetails);
            $row.data('base-cost', baseCost);
            $row.data('base-price', basePrice);

            $('#combo_items_tbody').append($row);

            updateComboRowCost($row);
        };

        function updateComboRowCost($row) {
            let unitDetails = $row.data('unit-details') || {};
            let baseCost = parseFloat($row.data('base-cost')) || 0;
            let basePrice = parseFloat($row.data('base-price')) || 0;
            let selectedUnitId = $row.find('.combo-unit-select').val();
            let qty = parseFloat($row.find('.combo-qty').val()) || 0;

            let ratio = 1;

            if (selectedUnitId && typeof CompoundUnitCalculator !== 'undefined' && unitDetails && Object.keys(unitDetails).length > 0) {
                let calculator = new CompoundUnitCalculator(unitDetails);
                ratio = calculator.calculateRatio(selectedUnitId) || 1;
            }

            let effectiveUnitCost = baseCost * ratio;
            let effectiveUnitPrice = basePrice * ratio;

            let lineTotalCost = effectiveUnitCost * qty;
            let lineTotalPrice = effectiveUnitPrice * qty;

            $row.find('.item-unit-cost').val(effectiveUnitCost.toFixed(4));
            $row.find('.item-unit-price').val(effectiveUnitPrice.toFixed(4));

            $row.find('.item-unit-cost-display').text(effectiveUnitCost.toFixed(2));
            $row.find('.item-unit-price-display').text(effectiveUnitPrice.toFixed(2));

            $row.find('.item-total-cost-display').text(lineTotalCost.toFixed(2));
            $row.find('.item-total-price-display').text(lineTotalPrice.toFixed(2));

            calculateComboTotals();
        }

        $(document).on('change', '.combo-unit-select', function() {
            let $row = $(this).closest('.combo-item-row');
            updateComboRowCost($row);
        });

        $(document).on('input', '.combo-qty', function() {
            let $row = $(this).closest('.combo-item-row');
            updateComboRowCost($row);
        });

        // 💡 ফিজিক্যাল প্রোডাক্টের জন্য মিডপয়েন্ট এবং সার্ভিস প্রডাক্টের জন্য ১০০% ফুল প্রাইস যোগ করার লজিক
        function calculateComboTotals() {
            let grandTotalCost = 0;
            let grandTotalPrice = 0;

            let physicalTotalCost = 0;
            let physicalTotalPrice = 0;
            let serviceTotalPrice = 0;

            $('.combo-item-row').each(function() {
                let lineCost = parseFloat($(this).find('.item-total-cost-display').text()) || 0;
                let linePrice = parseFloat($(this).find('.item-total-price-display').text()) || 0;

                grandTotalCost += lineCost;
                grandTotalPrice += linePrice;

                if (lineCost > 0) {
                    physicalTotalCost += lineCost;
                    physicalTotalPrice += linePrice;
                } else {
                    // সার্ভিস প্রডাক্টের কেনা খরচ ০ হওয়ায় এর পুরো বিক্রয় মূল্য যুক্ত হবে
                    serviceTotalPrice += linePrice;
                }
            });

            $('#combo_total_cost_display').text(grandTotalCost.toFixed(2));
            $('#combo_total_price_display').text(grandTotalPrice.toFixed(2));

            // ফিজিক্যাল প্রডাক্টের মিডপয়েন্ট + সার্ভিস প্রডাক্টের ১০০% ফুল প্রাইস
            let physicalSuggested = physicalTotalCost > 0 
                ? Math.round((physicalTotalCost + physicalTotalPrice) / 2) 
                : 0;

            let suggestedPrice = physicalSuggested + Math.round(serviceTotalPrice);

            // সেফটি চেক: কম্বো প্রাইস কেনা খরচের নিচে নামবে না
            if (suggestedPrice < grandTotalCost) {
                suggestedPrice = Math.round(grandTotalCost);
            }

            let suggestedMargin = grandTotalCost > 0 
                ? roundToTwo(((suggestedPrice - grandTotalCost) / grandTotalCost) * 100) 
                : 0;

            $('#combo_suggested_price_display').html(`
                <span class="fw-bold fs-6 text-primary">${suggestedPrice}.00</span>
            `);

            // অটো সেভ মেইন ফিল্ডে
            $('#product_cost').val(grandTotalCost.toFixed(2));

            if ($('select[name="type"]').val() === 'combo') {
                $('#product_price').val(suggestedPrice.toFixed(2));
            }
        }

        $(document).on('click', '.remove-combo-row', function() {
            $(this).closest('tr').remove();
            if ($('#combo_items_tbody tr').length === 0) {
                $('#combo_items_tbody').append('<tr id="empty_combo_row"><td colspan="8" class="text-center text-muted py-3">No products added to this combo yet.</td></tr>');
            }
            calculateComboTotals();
        });

        // ==================== PRODUCT TYPE CHANGE LOGIC ====================

        $('select[name="type"]').on('change', function() {
            const type = $(this).val();
            const dynamicCard = $('#dynamic_product_card');
            const digitalPart = $('#digital_part');
            const digitalPartContainer = $('#digital_part_container');
            const container = $('#dynamic_field_container');
            const unitSection = $('#unit_section');
            const pharmacySection = $('#pharmacy_item_section');
            const dropshipCard = $('#dropship_product_card');
            const dropshipContainer = $('#dropship_field_container');

            // Reset UI
            container.empty();
            digitalPartContainer.empty();
            dropshipContainer.empty();
            dynamicCard.addClass('d-none');
            digitalPart.addClass('d-none');
            dropshipCard.addClass('d-none');

            // Control stock management visibility based on type
            const isPhysicalOrDropship = (type === 'physical' || type === 'dropship');
            unitSection.toggleClass('d-none', !isPhysicalOrDropship);
            pharmacySection.toggleClass('d-none', !isPhysicalOrDropship);

            const unitInputs = unitSection.find('input, select, textarea');

            if (isPhysicalOrDropship) {
                unitInputs.prop('required', true);
            } else {
                unitInputs.prop('required', false);
            }

            const stockFields = [
                '#manage_stock', '#allow_oversale', '#has_variants',
                '#has_imie', '#expire_date', '#has_opening_stock'
            ];

            if (type === 'service' || type === 'digital' || type === 'combo' || type === 'dropship') {
                stockFields.forEach(selector => {
                    $(selector).prop('checked', false).closest('.col-md-3').hide();
                });
            } else {
                stockFields.forEach(selector => $(selector).closest('.col-md-3').show());
            }

            // Handle Specific Types
            if (type === 'digital') {
                digitalPart.removeClass('d-none');
                digitalPartContainer.append(`
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Download File *</label>
                            <input type="file" name="digital_upload" class="form-control" required>
                            <input type="hidden" name="digital_file" id="digital_file_id">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">External Links</label>
                            <input type="text" name="external_links" class="form-control">
                        </div>
                    </div>`);
                setTimeout(initFilepond, 50);
            } else if (type === 'combo') {
                dynamicCard.removeClass('d-none');
                $('#dynamic_card_title').text('Combo Bundle Items Builder');
                container.append(`
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="fw-bold form-label">Search & Add Items to Combo Bundle <span class="text-danger">*</span></label>
                            <select id="combo_search_select" class="form-control"></select>
                            <small class="text-muted">Type product name or SKU to search and add items to this bundle.</small>
                        </div>
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0" id="combo_items_table">
                                    <thead class="fs-12">
                                        <tr>
                                            <th width="30%">Product Item</th>
                                            <th width="15%">Unit</th>
                                            <th width="10%" class="text-center">Qty</th>
                                            <th width="11%" class="text-end">Unit Cost</th>
                                            <th width="11%" class="text-end">Unit Price</th>
                                            <th width="11%" class="text-end">Total Cost</th>
                                            <th width="12%" class="text-end">Total Price</th>
                                            <th width="5%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="combo_items_tbody">
                                        <tr id="empty_combo_row"><td colspan="8" class="text-center text-muted py-3">No products added to this combo yet.</td></tr>
                                    </tbody>
                                    <tfoot class="fw-bold fs-12">
                                        <tr>
                                            <td colspan="5" class="text-end">Total Calculated Combo Cost:</td>
                                            <td class="text-end text-primary" id="combo_total_cost_display">0.00</td>
                                            <td colspan="2"></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end text-muted">Total Individual Retail Price:</td>
                                            <td class="text-end text-muted" id="combo_total_price_display">0.00</td>
                                            <td colspan="2"></td>
                                        </tr>
                                        <tr class="table-primary-subtle">
                                            <td colspan="5" class="text-end text-primary align-middle">
                                                <i class="fa-solid fa-wand-magic-sparkles me-1 text-primary"></i> Smart Suggested Combo Price:
                                            </td>
                                            <td class="text-end fw-bold text-primary fs-6 align-middle" id="combo_suggested_price_display">0.00</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>`);

                // Initialize Select2 for Combo Live Search
                $('#combo_search_select').select2({
                    placeholder: "Type product name, SKU or code...",
                    allowClear: true,
                    width: '100%',
                    ajax: {
                        url: "{{ route('products.searchForCombo') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return {
                                results: data.map(function (item) {
                                    return {
                                        id: item.id,
                                        text: item.name + ' (SKU: ' + item.sku + ' | Cost: ' + item.cost + ')',
                                        raw: item
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                }).on('select2:select', function (e) {
                    let selected = e.params.data.raw;
                    addComboItemRow(selected);
                    $(this).val(null).trigger('change');
                });

                // Edit Mode: Pre-populate existing combo items
                @if(isset($product) && $product->type === 'combo' && $product->comboItems && $product->comboItems->count() > 0)
                    @foreach($product->comboItems as $cItem)
                        @php
                            $childProd = $cItem->product;
                            $childVar = $cItem->variant;
                            if($childProd) {
                                $itemName = $childProd->name . ($childVar ? ' (' . $childVar->name . ')' : '');
                                $itemSku = $childVar ? $childVar->sku : $childProd->sku;
                                $unitDetails = $childVar ? $childVar->unit_details : $childProd->unit_details;
                                $unitDetailsArray = is_array($unitDetails) ? $unitDetails : (json_decode($unitDetails, true) ?? []);
                                @endphp
                                addComboItemRow({
                                    product_id: "{{ $cItem->product_id }}",
                                    product_variant_id: "{{ $cItem->product_variant_id ?? '' }}",
                                    name: "{{ $itemName }}",
                                    sku: "{{ $itemSku }}",
                                    cost: {{ $cItem->unit_cost }},
                                    price: {{ $cItem->unit_price }},
                                    unit_details: @json($unitDetailsArray),
                                    sale_unit_id: "{{ $cItem->unit_id ?? $childProd->sale_unit_id }}"
                                }, "{{ $cItem->unit_id }}", {{ $cItem->quantity }});
                                @php
                            }
                        @endphp
                    @endforeach
                @endif

            } else if (type === 'dropship') {
                dropshipCard.removeClass('d-none');
                dropshipContainer.append(`
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="fw-bold">{{ __('file.field.platform_name') }}</label>
                            <input type="text" name="platform_name" class="form-control" placeholder="E.g. Amazon, eBay, etc.">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="fw-bold">{{ __('file.field.supplier_name') }}</label>
                            <input type="text" name="supplier_name" class="form-control" placeholder="E.g. ABC Supplier">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="fw-bold">{{ __('file.field.external_product_code') }}</label>
                            <input type="text" name="external_product_code" class="form-control" placeholder="E.g. 123456">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="fw-bold">{{ __('file.field.external_sku') }}</label>
                            <input type="text" name="external_sku" class="form-control" placeholder="E.g. SKU12345">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="fw-bold">{{ __('file.field.selling_price') }}</label>
                            <input type="number" step="any" name="selling_price" class="form-control" value="0" placeholder="E.g. 99.99">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="fw-bold">{{ __('file.field.buying_price') }}</label>
                            <input type="number" step="any" name="buying_price" class="form-control" value="0" placeholder="E.g. 99.99">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="fw-bold">{{ __('file.field.estimated_shipping_cost') }}</label>
                            <input type="number" step="any" name="estimated_shipping_cost" class="form-control" value="0" placeholder="E.g. 9.99">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="fw-bold">{{ __('file.field.delivery_lead_time') }}</label>
                            <input type="number" name="delivery_lead_time" class="form-control" value="0" placeholder="E.g. 5 (days)">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="fw-bold">{{ __('file.field.external_product_url') }}</label>
                            <input type="url" name="external_product_url" class="form-control" placeholder="https://example.com">
                        </div>
                    </div>`);
            }
        }).trigger('change');

        // Dynamic Specification Row Management
        $('#add_specification_button').on('click', function() {
            let newRow = `
                <div class="spec-row row align-items-end mb-2">
                    <div class="col-md-5">
                        <input type="text" name="specification_name[]" class="form-control form-control-sm" placeholder="Name">
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="specification_value[]" class="form-control form-control-sm" placeholder="Value">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-spec w-100">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            $('#product_specification_container').append(newRow);
        });

        $(document).on('click', '.remove-spec', function() {
            if ($('.spec-row').length > 1) {
                $(this).closest('.spec-row').remove();
            } else {
                showFloatingAlert('warning', 'At least one specification is required.');
            }
        });

        // Variant UI Logic for Save Buttons
        const variantCheckbox = $('#has_variants');
        const mainSaveBtn = $('#main_save_btn');
        const dropdownToggle = $('#dropdown_toggle');
        const textSave = "{{ __('file.button.save') }}";
        const textSaveNext = "{{ __('file.button.save_and_next') }}";

        function updateButtonUI() {
            if (variantCheckbox.is(':checked')) {
                mainSaveBtn.text(textSaveNext).val('save_and_next').addClass('rounded');
                dropdownToggle.hide();
            } else {
                mainSaveBtn.text(textSave).val('save').removeClass('rounded');
                dropdownToggle.show();
            }
        }

        variantCheckbox.on('change', updateButtonUI);
        updateButtonUI();

        /**
         * Master Controller for Product, Barcode, and Symbology Logic
         */
        const $productType = $('select[name="type"]');
        const $barcodeType = $('select[name="barcode_type"]');
        const $barcodeSymbology = $('select[name="barcode_symbology"]');

        function updateBarcodeLogic(source) {
            const pType = $productType.val();
            const bType = $barcodeType.val();
            const bSym = $barcodeSymbology.val();

            if (source === 'product_type') {
                if (pType === 'service' || pType === 'digital') {
                    $barcodeType.val('standard').trigger('change.select2');
                    $barcodeType.find('option').not('[value="standard"]').hide();
                } else {
                    $barcodeType.find('option').show();
                }
            }

            if (source === 'barcode_type' || source === 'product_type') {
                if (bType === 'dynamic') {
                    if (!['EAN13', 'EAN8', 'UPCA'].includes(bSym)) {
                        $barcodeSymbology.val('EAN13').trigger('change.select2');
                    }
                    $barcodeSymbology.find('option').hide();
                    $barcodeSymbology.find('option[value="EAN13"], option[value="EAN8"], option[value="UPCA"]').show();
                } else {
                    if (['EAN13', 'EAN8', 'UPCA'].includes(bSym)) {
                        $barcodeSymbology.val('C128').trigger('change.select2');
                    }
                    $barcodeSymbology.find('option').show();
                    $barcodeSymbology.find('option[value="EAN13"], option[value="EAN8"], option[value="UPCA"]').hide();
                }
            }

            if (source === 'symbology') {
                if (['EAN13', 'EAN8', 'UPCA'].includes(bSym)) {
                    if (pType !== 'service' && pType !== 'digital' && bType !== 'dynamic') {
                        $barcodeType.val('dynamic').trigger('change.select2');
                    }
                } else {
                    if (bType === 'dynamic') {
                        $barcodeType.val('standard').trigger('change.select2');
                    }
                }
            }
        }

        $productType.on('change', function() {
            updateBarcodeLogic('product_type');
        });
        $barcodeType.on('change', function() {
            updateBarcodeLogic('barcode_type');
        });
        $barcodeSymbology.on('change', function() {
            updateBarcodeLogic('symbology');
        });

        updateBarcodeLogic('product_type');

    });

    $('#generate_code').on('click', function(e) {
        e.preventDefault();
        $.get("{{ route('products.generateItemCode') }}", function(response) {
            $('input[name="code"]').val(response);
        });
    });

    $('input[name="code"]').on('input', function() {
        let $this = $(this);
        let value = $this.val();
        let cleanedValue = value.replace(/[^0-9]/g, '');

        if (cleanedValue.length > 5) {
            cleanedValue = cleanedValue.substring(0, 5);
        }

        $this.val(cleanedValue);

        if (value !== cleanedValue) {
            showFloatingAlert('warning', 'Only numbers are allowed.');
        }
    });
</script>