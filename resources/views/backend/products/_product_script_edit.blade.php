<script>
    $(document).ready(function() {

        let isEditMode = $('#product_price').val() !== "" && $('#product_price').val() !== "0";

        function calculatePrice() {
            let margin = parseFloat($('#profit_margin').val()) || 0;
            let cost = parseFloat($('#product_cost').val()) || 0;
            let currentPrice = parseFloat($('#product_price').val()) || 0;

            if (margin < 0) {
                margin = 0;
                $('#profit_margin').val(0);
            }

            let expectedPrice = cost + (cost * (margin / 100));

            // লজিক: 
            // ১. যদি নতুন এন্ট্রি হয় (currentPrice === 0)
            // ২. অথবা যদি কস্ট/মার্জিন বাড়ার কারণে expectedPrice বর্তমান প্রাইসকে ছাড়িয়ে যায়
            if (currentPrice === 0 || currentPrice < expectedPrice) {
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
                // ইউজারকে ওয়ার্নিং দেওয়া
                showFloatingAlert('Warning: Price is lower than the set profit margin (' + minAllowedPrice.toFixed(
                    2) + '). Reverting to minimum price.');
                $(this).val(minAllowedPrice.toFixed(2));
            }
        });
        /**
         * Reusable function to initialize Select2 with dynamic tagging
         * @param {string} selector - The jQuery selector
         * @param {string} storeRoute - Laravel route for storing new items
         * @param {object} extraData - Additional data for the AJAX request
         */
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
                            // ১. পুরনো টেম্পোরারি অপশনটি রিমুভ করুন (এটিই আসল ট্রিক)
                            selectElement.find('option[value="' + data.id + '"]').remove();

                            // ২. সার্ভার থেকে আসা রিয়েল আইডি দিয়ে নতুন অপশন তৈরি করুন
                            var newOption = new Option(data.text, response.id, true, true);

                            // ৩. এটি সিলেক্ট এলিমেন্টে যুক্ত করে ট্রিগার করুন
                            selectElement.append(newOption).trigger('change');

                            showFloatingAlert('success', 'Created successfully.');
                        },
                        error: function() {
                            selectElement.find('option[value="' + data.id + '"]').remove();
                            selectElement.trigger('change');
                            showFloatingAlert('error', 'Could not create entry.');
                        }
                    });
                }
            });

            return selectElement;
        }

        // Initialize Category and Brand Selects
        const categorySelect = initDynamicSelect2('#category_id', "{{ route('categories.store') }}", {
            category_type_id: "{{ $typeId ?? '' }}"
        });
        const brandSelect = initDynamicSelect2('#brand_id', "{{ route('brands.store') }}");

        // Basic Select2 Initialization
        $('#unit_group').select2();

        // Unit Group Change Logic
        $('select[name="unit_group_id"]').on('change', function() {
            $('#unit_variables_container').empty();
            const groupId = $(this).val();
            if (groupId) getBaseUnitByGroupId(groupId);
        });

        // Base Unit Change Logic
        $('select[name="base_unit_id"]').on('change', function() {
            $('#unit_variables_container').empty();
            const baseUnitId = $(this).val();
            const baseUnitName = $(this).find('option:selected').text();
            if (baseUnitId) getSubUnitsByBaseUnitId(baseUnitId, baseUnitName);
        });

        const editModeData = {
            unitGroupId: "{{ old('unit_group_id', $product->unit_group_id) }}",
            baseUnitId: "{{ old('base_unit_id', $product->base_unit_id) }}",
            purchaseUnitId: "{{ old('purchase_unit_id', $product->purchase_unit_id) }}",
            saleUnitId: "{{ old('sale_unit_id', $product->sale_unit_id) }}",
            // unit_details থেকে শুধু ভ্যারিয়েবলগুলো নিচ্ছি (যেমন: length, width)
            unitVars: {!! json_encode(old('unit_vars', collect($product->unit_details)->map->user_vars ?? [])) !!}
        };

        // ২. পেজ লোড হওয়ার সময় যদি Unit Group থাকে, তবে বেস ইউনিট লোড করা
        if (editModeData.unitGroupId) {
            loadBaseUnitsForEdit(editModeData.unitGroupId, editModeData);
        }

        // ৩. Unit Group Change (Manual Change)
        $('select[name="unit_group_id"]').on('change', function() {
            $('#unit_variables_container').empty();
            const groupId = $(this).val();
            if (groupId) loadBaseUnitsForEdit(groupId, null);
        });

        // ৪. Base Unit Change (Manual or Programmatic)
        $('select[name="base_unit_id"]').on('change', function() {
            $('#unit_variables_container').empty();
            const baseUnitId = $(this).val();
            const baseUnitName = $(this).find('option:selected').text();
            if (baseUnitId) loadSubUnitsForEdit(baseUnitId, baseUnitName, editModeData);
        });

        // ৫. বেস ইউনিট লোড করার ফাংশন
        function loadBaseUnitsForEdit(groupId, mode) {
            const url = "{{ route('units.getBaseUnitsByGroup', ':id') }}".replace(':id', groupId);
            $.get(url, function(response) {
                const baseUnitSelect = $('select[name="base_unit_id"]');
                baseUnitSelect.empty().append(
                    '<option value="">{{ __('file.option.select_base_unit') }}</option>');

                $.each(response.data, function(index, baseUnit) {
                    let selected = (mode && mode.baseUnitId == baseUnit.id) ? 'selected' : '';
                    baseUnitSelect.append(
                        `<option value="${baseUnit.id}" ${selected}>${baseUnit.name}</option>`
                    );
                });

                // বেস ইউনিট সেট হওয়ার পর সাব-ইউনিট লোড করতে ট্রিগার করা
                baseUnitSelect.trigger('change');
            });
        }

        // ৬. সাব-ইউনিট এবং ভ্যারিয়েবল লোড করার ফাংশন
        function loadSubUnitsForEdit(baseUnitId, baseUnitName, mode) {
            const url = "{{ route('units.getSubUnits', ':id') }}".replace(':id', baseUnitId);
            $.get(url, function(response) {
                const purchaseUnitSelect = $('select[name="purchase_unit_id"]');
                const saleUnitSelect = $('select[name="sale_unit_id"]');
                const container = $('#unit_variables_container');

                purchaseUnitSelect.empty().append(
                    `<option value="${baseUnitId}">${baseUnitName}</option>`);
                saleUnitSelect.empty().append(`<option value="${baseUnitId}">${baseUnitName}</option>`);
                container.empty();

                function processNestedUnits(units) {
                    $.each(units, function(index, unit) {
                        // Purchase & Sale Unit Selection
                        let pSelected = (mode && mode.purchaseUnitId == unit.id) ? 'selected' :
                            '';
                        let sSelected = (mode && mode.saleUnitId == unit.id) ? 'selected' : '';

                        purchaseUnitSelect.append(
                            `<option value="${unit.id}" ${pSelected}>${unit.name}</option>`);
                        saleUnitSelect.append(
                            `<option value="${unit.id}" ${sSelected}>${unit.name}</option>`);

                        // ভ্যারিয়েবল ইনপুট ফিল্ড তৈরি (length, width ইত্যাদি)
                        if (unit.is_formulaic && unit.display_params && unit.display_params
                            .variables) {
                            let html = `
                            <div class="p-3 mb-3 border rounded bg-light" data-unit-id="${unit.id}">
                                <h6 class="text-primary mb-2">${unit.name} <small class="text-muted">(Formula: ${unit.formula})</small></h6>
                                <div class="row g-2">`;

                            $.each(unit.display_params.variables, function(i, varName) {
                                // পুরাতন ভ্যালু খুঁজে বের করা
                                let val = (mode && mode.unitVars[unit.id] && mode
                                        .unitVars[unit.id][varName]) ?
                                    mode.unitVars[unit.id][varName] : '';

                                html += `
                                <div class="col-md-4">
                                    <label class="form-label mb-1 fw-bold small">${varName}</label>
                                    <input type="number" step="any" name="unit_vars[${unit.id}][${varName}]" 
                                           class="form-control form-control-sm" value="${val}" required>
                                </div>`;
                            });

                            html += `</div></div>`;
                            container.append(html);
                        }

                        if (unit.all_sub_units && unit.all_sub_units.length > 0) {
                            processNestedUnits(unit.all_sub_units);
                        }
                    });
                }

                processNestedUnits(response.data);

                // যদি বেস ইউনিটই পারচেজ বা সেল ইউনিট হয়
                if (mode && mode.purchaseUnitId == baseUnitId) purchaseUnitSelect.val(baseUnitId);
                if (mode && mode.saleUnitId == baseUnitId) saleUnitSelect.val(baseUnitId);

                // purchaseUnitSelect.selectpicker('refresh');
                // saleUnitSelect.selectpicker('refresh');
            });
        }

        // Toggle Additional Details Section
        $('#toggle_details').on('click', function() {
            const section = $('#additional_details_section');
            const isHidden = section.hasClass('d-none');
            section.toggleClass('d-none');
            $(this).html(isHidden ? '<i class="fa fa-minus"></i> Hide Details' :
                '<i class="fa fa-plus"></i> Add More Details');
        });

        // Toggle Specification Section
        $('#has_specification').on('change', function() {
            $('#specification_section').toggleClass('d-none', !$(this).is(':checked'));
        });

        // Toggle Warranty Section
        $('#has_warranty').on('change', function() {
            $('#warranty_section').toggleClass('d-none', !$(this).is(':checked'));
        });

        const dropshipData = @json($product->dropshippingDetail);

        //console.log('Dropship Data:', dropshipData);
        // Product Type Change Logic (Main Controller for Dynamic UI)
        $('select[name="type"]').on('change', function() {

            const type = $(this).val();
            const dynamicCard = $('#dynamic_product_card');
            const digitalPart = $('#digital_part');
            const digitalPartContainer = $('#digital_part_container');
            const container = $('#dynamic_field_container');
            const unitSection = $('#unit_section');
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

            const unitInputs = unitSection.find('input, select, textarea');

            if (isPhysicalOrDropship) {
                unitInputs.prop('required', true);
            } else {
                unitInputs.prop('required', false);
            }

            const stockFields = [
                '#manage_stock', '#allow_oversale', '#has_variant',
                '#has_imie', '#expire_date'
            ];

            if (type === 'service' || type === 'digital' || type === 'combo') {
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
                            <label class="fw-bold">Download File *</label>
                            <input type="file" name="digital_file" class="form-control" required>
                            <input type="hidden" name="digital_file_id" id="digital_file_id">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="fw-bold">External Links</label>
                            <input type="text" name="external_links" class="form-control">
                        </div>
                    </div>`);
                setTimeout(initFilepond, 50);
            } else if (type === 'combo') {
                dynamicCard.removeClass('d-none');
                container.append(`
                    <div class="row"><div class="col-md-12">
                        <label class="fw-bold">{{ __('file.field.combo_items') }} *</label>
                        <input type="text" id="combo_search" class="form-control" placeholder="Type product name...">
                        <table class="table table-sm mt-2" id="combo_table">
                            <thead><tr><th>Item</th><th>Qty</th><th>Action</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div></div>`);
            } else if (type === 'dropship') {
                dropshipCard.removeClass('d-none');
                renderDropshipFields(dropshipData, dropshipContainer);
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
        updateButtonUI(); // Run on load

        /**
         * Master Controller for Product, Barcode, and Symbology Logic
         * Optimized for Sherazi POS (Multi-branch & Weight Scale)
         */
        const $productType = $('select[name="type"]');
        const $barcodeType = $('select[name="barcode_type"]');
        const $barcodeSymbology = $('select[name="barcode_symbology"]');

        function updateBarcodeLogic(source) {
            const pType = $productType.val();
            const bType = $barcodeType.val();
            const bSym = $barcodeSymbology.val();

            // ১. প্রোডাক্ট টাইপ অনুযায়ী বারকোড টাইপ ফিল্টার
            if (source === 'product_type') {
                if (pType === 'service' || pType === 'digital') {
                    $barcodeType.val('standard').trigger('change.select2');
                    $barcodeType.find('option').not('[value="standard"]').hide();
                } else {
                    $barcodeType.find('option').show();
                }
            }

            // ২. বারকোড টাইপ অনুযায়ী সিম্বলজি কন্ট্রোল (এটিই আপনার সমস্যার সমাধান)
            if (source === 'barcode_type' || source === 'product_type') {
                if (bType === 'dynamic') {
                    // ডায়নামিক মোড: শুধু ওয়েট স্কেল সিম্বলজি দেখাবে
                    if (!['EAN13', 'EAN8', 'UPCA'].includes(bSym)) {
                        $barcodeSymbology.val('EAN13').trigger('change.select2');
                    }
                    $barcodeSymbology.find('option').hide();
                    $barcodeSymbology.find('option[value="EAN13"], option[value="EAN8"], option[value="UPCA"]')
                        .show();
                } else {
                    // স্ট্যান্ডার্ড মোড: ওয়েট স্কেল সিম্বলজিগুলো হাইড করবে, বাকি সব দেখাবে
                    if (['EAN13', 'EAN8', 'UPCA'].includes(bSym)) {
                        $barcodeSymbology.val('C128').trigger('change.select2'); // ডিফল্ট Code128 সেট হবে
                    }
                    $barcodeSymbology.find('option').show(); // আগে সব দেখাবে
                    $barcodeSymbology.find('option[value="EAN13"], option[value="EAN8"], option[value="UPCA"]')
                        .hide(); // ওয়েট স্কেলগুলো হাইড করবে
                }
            }

            // ৩. সিম্বলজি থেকে বারকোড টাইপ অটো-সিলেক্ট (Reverse Link)
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

        // Event Listeners with Source Tracking
        $productType.on('change', function() {
            updateBarcodeLogic('product_type');
        });
        $barcodeType.on('change', function() {
            updateBarcodeLogic('barcode_type');
        });
        $barcodeSymbology.on('change', function() {
            updateBarcodeLogic('symbology');
        });

        // Initial Load
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

        // ১. শুধুমাত্র নাম্বার রাখা (অন্য সব ক্যারেক্টার মুছে ফেলা)
        let cleanedValue = value.replace(/[^0-9]/g, '');

        // ২. ৫ ডিজিটের বেশি হলে কেটে দেওয়া
        if (cleanedValue.length > 5) {
            cleanedValue = cleanedValue.substring(0, 5);
        }

        // ইনপুট ফিল্ডে ক্লিন করা ভ্যালু বসানো
        $this.val(cleanedValue);

        // ৩. ওয়ার্নিং মেসেজ (ঐচ্ছিক: ইউজার যদি ভুল কিছু টাইপ করার চেষ্টা করে)
        if (value !== cleanedValue) {
            showFloatingAlert('warning', 'Only numbers are allowed.');
        }
    });
    /**
     * Fetch Base Units by Group ID
     */
    function getBaseUnitByGroupId(groupId) {
        const url = "{{ route('units.getBaseUnitsByGroup', ':id') }}".replace(':id', groupId);
        $.get(url, function(response) {
            const baseUnitSelect = $('select[name="base_unit_id"]');
            baseUnitSelect.empty().append(
                '<option value="">{{ __('file.option.select_base_unit') }}</option>');
            $.each(response.data, function(index, baseUnit) {
                baseUnitSelect.append('<option value="' + baseUnit.id + '">' + baseUnit.name +
                    '</option>');
            });
            baseUnitSelect.trigger('change');
        });
    }

    /**
     * Fetch Sub Units and Generate Formula Fields
     */
    function getSubUnitsByBaseUnitId(baseUnitId, baseUnitName) {
        const url = "{{ route('units.getSubUnits', ':id') }}".replace(':id', baseUnitId);
        $.get(url, function(response) {
            const purchaseUnitSelect = $('select[name="purchase_unit_id"]');
            const saleUnitSelect = $('select[name="sale_unit_id"]');
            const container = $('#unit_variables_container');

            purchaseUnitSelect.empty().append(`<option value="${baseUnitId}">${baseUnitName}</option>`);
            saleUnitSelect.empty().append(`<option value="${baseUnitId}">${baseUnitName}</option>`);
            container.empty();

            function processNestedUnits(units) {
                $.each(units, function(index, unit) {
                    purchaseUnitSelect.append(`<option value="${unit.id}">${unit.name}</option>`);
                    saleUnitSelect.append(`<option value="${unit.id}">${unit.name}</option>`);

                    if (unit.is_formulaic && unit.display_params && unit.display_params.variables) {
                        let html = `
                            <div class="p-3 mb-3 border rounded bg-light" data-unit-id="${unit.id}">
                                <h6 class="text-primary mb-2">${unit.name} <small class="text-muted">(Formula: ${unit.formula})</small></h6>
                                <div class="row g-2">`;

                        $.each(unit.display_params.variables, function(i, varName) {
                            html += `
                                <div class="col-md-4">
                                    <label class="form-label mb-1 fw-bold small">${varName} <span class="text-danger">*</span></label>
                                    <input type="number" step="any" name="unit_vars[${unit.id}][${varName}]" 
                                           class="form-control form-control-sm" placeholder="Enter ${varName}" required>
                                </div>`;
                        });

                        html += `</div></div>`;
                        container.append(html);
                    }

                    if (unit.all_sub_units && unit.all_sub_units.length > 0) {
                        processNestedUnits(unit.all_sub_units);
                    }
                });
            }

            processNestedUnits(response.data);
            purchaseUnitSelect.trigger('change');
            saleUnitSelect.trigger('change');
        });
    }

    function renderDropshipFields(data = null, dropshipContainer = $('#dropship_field_container')) {
        dropshipContainer.empty();

        // ডাটা যদি null হয় তবে অবজেক্ট হিসেবে খালি সেট করে নিচ্ছি যাতে কোড না ফাটে
        const d = data || {};

        // প্রতিটি ভ্যালুর পাশে || '' ব্যবহার করা হয়েছে যাতে null হলে খালি দেখায়
        const platform = d.platform_name ?? '';
        const supplier = d.supplier_name ?? '';
        const code = d.external_product_code ?? '';
        const sku = d.external_sku ?? '';
        const s_price = d.selling_price ?? '';
        const b_price = d.buying_price ?? '';
        const shipping = d.estimated_shipping_cost ?? '';
        const lead_time = d.delivery_lead_time ?? ''; // এখন 0 থাকলে 0-ই আসবে
        const url = d.external_product_url ?? '';

        dropshipContainer.append(`
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="fw-bold">{{ __('file.field.platform_name') }}</label>
                    <input type="text" name="platform_name" value="${platform}" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="fw-bold">{{ __('file.field.supplier_name') }}</label>
                    <input type="text" name="supplier_name" value="${supplier}" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="fw-bold">{{ __('file.field.external_product_code') }}</label>
                    <input type="text" name="external_product_code" value="${code}" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="fw-bold">{{ __('file.field.external_sku') }}</label>
                    <input type="text" name="external_sku" value="${sku}" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="fw-bold">{{ __('file.field.selling_price') }}</label>
                    <input type="number" step="any" name="selling_price" value="${s_price}" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="fw-bold">{{ __('file.field.buying_price') }}</label>
                    <input type="number" step="any" name="buying_price" value="${b_price}" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="fw-bold">{{ __('file.field.estimated_shipping_cost') }}</label>
                    <input type="number" step="any" name="estimated_shipping_cost" value="${shipping}" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="fw-bold">{{ __('file.field.delivery_lead_time') }}</label>
                    <input type="number" name="delivery_lead_time" value="${lead_time}" class="form-control">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="fw-bold">{{ __('file.field.external_product_url') }}</label>
                    <input type="url" name="external_product_url" value="${url}" class="form-control">
                </div>
            </div>
        `);
    }
</script>
