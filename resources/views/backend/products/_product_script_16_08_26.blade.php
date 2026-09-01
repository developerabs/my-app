<script>
    $(document).ready(function() {

        function calculatePrice() {
            let margin = parseFloat($('#profit_margin').val()) || 0;
            let cost = parseFloat($('#product_cost').val()) || 0;
            let currentPrice = parseFloat($('#product_price').val()) || 0;

            // নেগেটিভ মার্জিন ঠেকানোর জন্য
            if (margin < 0) {
                margin = 0;
                $('#profit_margin').val(0);
            }

            // মার্জিন অনুযায়ী সেলিং প্রাইস ক্যালকুলেশন: Cost + (Cost * Margin / 100)
            let expectedPrice = cost + (cost * (margin / 100));

            // যদি বর্তমান প্রাইস এক্সপেক্টেড প্রাইসের চেয়ে কম হয় অথবা নতুন এন্ট্রি হয়, তবেই আপডেট হবে
            if (currentPrice < expectedPrice || currentPrice === 0) {
                $('#product_price').val(expectedPrice.toFixed(2));
            }
        }

        // ইনপুট ইভেন্ট (টাইপ করার সাথে সাথে কাজ করবে)
        $(document).on('input', '#profit_margin, #product_cost', function() {
            calculatePrice();
        });

        // প্রাইস ম্যানুয়ালি চেঞ্জ করলে চেক করবে
        $(document).on('blur', '#product_price', function() {
            let margin = parseFloat($('#profit_margin').val()) || 0;
            let cost = parseFloat($('#product_cost').val()) || 0;
            let inputPrice = parseFloat($(this).val()) || 0;

            let minAllowedPrice = cost + (cost * (margin / 100));

            // যদি ইউজার ক্যালকুলেটেড প্রাইসের চেয়ে কম দিতে চায়, তাকে বাধা দিবে
            if (inputPrice < minAllowedPrice) {
                showFloatingAlert('Price cannot be less than the required profit margin (' + minAllowedPrice
                    .toFixed(2) + ')');
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

        // Initialize Category and Brand Selects
        const categorySelect = initDynamicSelect2('#category_id', "{{ route('categories.store') }}", {
            category_type_id: "{{ $typeId ?? '' }}"
        });
        const brandSelect = initDynamicSelect2('#brand_id', "{{ route('brands.store') }}");

        const genericSelect = initDynamicSelect2('#generic_id', "{{ route('generics.store') }}");

        // Basic Select2 Initialization
        $('#unit_group').select2();

        // Unit Group Change Logic
        $('select[name="unit_group_id"]').on('change', function() {
            $('#unit_variables_container').empty();
            const groupId = $(this).val();
            if (groupId){
                UnitManager.fetchBaseUnits(groupId, 'select[name="base_unit_id"]');
            };
        });

        // Base Unit Change Logic
        $('select[name="base_unit_id"]').on('change', function() {
            $('#unit_variables_container').empty();
            const baseUnitId = $(this).val();
            const baseUnitName = $(this).find('option:selected').text();
            if (baseUnitId){
                UnitManager.fetchSubUnits(baseUnitId, baseUnitName, '#unit_variables_container', 'select[name="purchase_unit_id"]', 'select[name="sale_unit_id"]');
            }
        });

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

        $('#has_opening_stock').on('change', function() {
            if(!$('#has_variants').is(':checked')) {
                $('#opening_stock_value').toggleClass('d-none', !$(this).is(':checked'));
            }
        });

        // Toggle Warranty Section
        $('#has_warranty').on('change', function() {
            $('#warranty_section').toggleClass('d-none', !$(this).is(':checked'));
        });

        // Product Type Change Logic (Main Controller for Dynamic UI)
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
                container.append(`
                    <div class="row"><div class="col-md-12">
                        <label class="form-label">Search & Add Products *</label>
                        <input type="text" id="combo_search" class="form-control" placeholder="Type product name...">
                        <table class="table table-sm mt-2" id="combo_table">
                            <thead><tr><th>Item</th><th>Qty</th><th>Action</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div></div>`);
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
</script>
