<script>
    $(document).ready(function() {

        

        // Initialize Select2 on the category dropdown
        const categorySelect = $('#category_id').select2({
            placeholder: "Select or type to add...",
            tags: true, // নতুন ভ্যালু টাইপ করতে দিবে
            allowClear: true,
            width: '100%',
            createTag: function(params) {
                var term = $.trim(params.term);
                if (term === '') return null;

                return {
                    id: term,
                    text: term,
                    isNew: true // কাস্টম প্রপার্টি যাতে চিনতে পারি এটা নতুন
                };
            },
            closeOnSelect: false,
        });

        categorySelect.on('select2:select', function(e) {
            var data = e.params.data;

            // যদি এটি নতুন টাইপ করা ক্যাটেগরি হয়
            if (data.isNew) {
                $.ajax({
                    url: "{{ route('categories.store') }}", // আপনার ক্যাটেগরি স্টোর রাউট
                    method: "POST",
                    data: {
                        name: data.text,
                        category_type_id: "{{ $typeId ?? '' }}", // আপনার নির্দিষ্ট টাইপ
                        is_active: 1
                    },
                    success: function(response) {
                        // সার্ভার থেকে আসা আসল ID (UUID) দিয়ে অপশনটি আপডেট করুন
                        var option = categorySelect.find('option[value="' + data.id + '"]');
                        option.val(response.id);
                        categorySelect.trigger('change');
                        showFloatingAlert('success', 'Category created successfully.');
                    },
                    error: function(xhr) {
                        // এরর হলে অপশনটি রিমুভ করে দিন
                        categorySelect.find('option[value="' + data.id + '"]').remove();
                        categorySelect.trigger('change');
                        showFloatingAlert('error',
                            'Could not create category. Please try again.');
                    }
                });
            }
        });

        const brandSelect = $('#brand_id').select2({
            placeholder: "Select or type to add...",
            tags: true, // নতুন ভ্যালু টাইপ করতে দিবে
            allowClear: true,
            width: '100%',
            createTag: function(params) {
                var term = $.trim(params.term);
                if (term === '') return null;

                return {
                    id: term,
                    text: term,
                    isNew: true // কাস্টম প্রপার্টি যাতে চিনতে পারি এটা নতুন
                };
            },
            escapeMarkup: function(markup) {
                return markup;
            },
            closeOnSelect: false
        });

        brandSelect.on('select2:select', function(e) {
            var data = e.params.data;

            // যদি এটি নতুন টাইপ করা ক্যাটেগরি হয়
            if (data.isNew) {
                $.ajax({
                    url: "{{ route('brands.store') }}", // আপনার ক্যাটেগরি স্টোর রাউট
                    method: "POST",
                    data: {
                        name: data.text,
                        is_active: 1
                    },
                    success: function(response) {
                        // সার্ভার থেকে আসা আসল ID (UUID) দিয়ে অপশনটি আপডেট করুন
                        var option = brandSelect.find('option[value="' + data.id + '"]');
                        option.val(response.id);
                        brandSelect.trigger('change');
                        showFloatingAlert('success', 'Brand created successfully.');
                    },
                    error: function(xhr) {
                        brandSelect.find('option[value="' + data.id + '"]').remove();
                        brandSelect.trigger('change');
                        showFloatingAlert('error',
                            'Could not create brand. Please try again.');
                    }
                });
            }
        });

        $('#unit_group').select2();

        $('select[name="unit_group_id"]').on('change', function() {
            $('#unit_variables_container').empty();
            const groupId = $(this).val();
            getBaseUnitByGroupId(groupId);
        });

        $('select[name="base_unit_id"]').on('change', function() {
            $('#unit_variables_container').empty();
            const baseUnitId = $(this).val();
            const baseUnitName = $(this).find('option:selected').text();
            getSubUnitsByBaseUnitId(baseUnitId, baseUnitName);
        });

        // ১. "Add More Details" Toggle Logic
        $('#toggle_details').on('click', function() {
            const section = $('#additional_details_section');
            const icon = $(this).find('i');

            if (section.hasClass('d-none')) {
                section.removeClass('d-none');
                $(this).html('<i class="fa fa-minus"></i> Hide Details');
            } else {
                section.addClass('d-none');
                $(this).html('<i class="fa fa-plus"></i> Add More Details');
            }
        });

        // ২. "Add More Specification" Toggle Logic
        $('#has_specification').on('change', function() {
            $('#specification_section').toggleClass('d-none', !$(this).is(':checked'));
        });

        // ২. Product Type Change Logic
        $('select[name="type"]').on('change', function() {
            const type = $(this).val();
            const dynamicCard = $('#dynamic_product_card');
            const digitalPart = $('#digital_part');
            const digitalPartContainer = $('#digital_part_container');
            const container = $('#dynamic_field_container');
            const unitSection = $('#unit_section');
            const dropshipCard = $('#dropship_product_card');
            const dropshipContainer = $('#dropship_field_container');

            container.empty();
            digitalPartContainer.empty();
            dropshipContainer.empty();
            dynamicCard.addClass('d-none');
            digitalPart.addClass('d-none');
            dropshipCard.addClass('d-none');

            // ফিজিক্যাল এবং ড্রপশিপ ছাড়া ইউনিট সেকশন হাইড থাকবে
            if (type === 'physical' || type === 'dropship') {
                unitSection.removeClass('d-none');
                $('#manage_stock').closest('.col-md-3').show();
                $('#allow_oversale').closest('.col-md-3').show();
                $('#has_variant').closest('.col-md-3').show();
                $('#has_imie').closest('.col-md-3').show();
                $('#expire_date').closest('.col-md-3').show();
            } else {
                unitSection.addClass('d-none');
                if (type === 'service') {
                    $('#manage_stock').prop('checked', false).closest('.col-md-3').hide();
                    $('#allow_oversale').prop('checked', false).closest('.col-md-3').hide();
                    $('#has_variant').prop('checked', false).closest('.col-md-3').hide();
                    $('#has_imie').prop('checked', false).closest('.col-md-3').hide();
                    $('#expire_date').prop('checked', false).closest('.col-md-3').hide();
                }
            }

            // ডিজিটাল ও কম্বো প্রোডাক্টের জন্য কনফিগারেশন
            if (type === 'digital') {
                digitalPart.removeClass('d-none');
                digitalPartContainer.append(`
                    <div class="row"><div class="col-md-12 mb-3">
                        <label class="form-label">Download File *</label>
                        <input type="file" name="digital_file" class="form-control" required>
                        <input type="hidden" name="digital_file_id" id="digital_file_id">
                    </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">External Links</label>
                            <input type="text" name="external_links" class="form-control">
                        </div>
                    </div>`);

                $('#manage_stock').prop('checked', false).closest('.col-md-3').hide();
                $('#allow_oversale').prop('checked', false).closest('.col-md-3').hide();
                $('#has_variant').prop('checked', false).closest('.col-md-3').hide();
                $('#has_imie').prop('checked', false).closest('.col-md-3').hide();
                $('#expire_date').prop('checked', false).closest('.col-md-3').hide();

                setTimeout(() => {
                    initFilepond();
                }, 50);
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
                $('#manage_stock').prop('checked', false).closest('.col-md-3').hide();
                $('#allow_oversale').prop('checked', false).closest('.col-md-3').hide();
                $('#has_variant').prop('checked', false).closest('.col-md-3').hide();
                $('#has_imie').prop('checked', false).closest('.col-md-3').hide();
                $('#expire_date').prop('checked', false).closest('.col-md-3').hide();
            } else if (type === 'dropship') {
                dropshipCard.removeClass('d-none');
                dropshipContainer.append(`
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Source</label>
                            <input type="text" name="dropship_source" class="form-control" placeholder="E.g. Supplier Name or URL">
                        </div>
                    </div>`);
            }
        }).trigger('change');


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

        // Remove specification row
        $(document).on('click', '.remove-spec', function() {
            // কমপক্ষে একটি রো যেন থাকে তা নিশ্চিত করতে পারেন
            if ($('.spec-row').length > 1) {
                $(this).closest('.spec-row').remove();
            } else {
                showFloatingAlert('warning', 'At least one specification is required.');
            }
        });

        const variantCheckbox = $('#has_variant');
        const mainSaveBtn = $('#main_save_btn');
        const dropdownToggle = $('#dropdown_toggle');

        // ল্যাঙ্গুয়েজ টেক্সট (আপনার ফাইল থেকে)
        const textSave = "{{ __('file.button.save') }}";
        const textSaveNext = "{{ __('file.button.save_and_next') }}";

        function updateButtonUI() {
            if (variantCheckbox.is(':checked')) {
                // ১. ভ্যারিয়েন্ট থাকলে: শুধু Save and Next বাটন
                mainSaveBtn.text(textSaveNext).val('save_and_next');
                mainSaveBtn.addClass('rounded'); // বাটনটিকে গোল করার জন্য (বুটস্ট্র্যাপ ক্লাস)
                dropdownToggle.hide(); // ড্রপডাউন অ্যারো হাইড
            } else {
                // ২. ভ্যারিয়েন্ট না থাকলে: Save বাটন + ড্রপডাউন
                mainSaveBtn.text(textSave).val('save');
                mainSaveBtn.removeClass('rounded'); // বাটন গ্রুপ স্টাইল ফিরিয়ে আনা
                dropdownToggle.show(); // ড্রপডাউন অ্যারো শো
            }
        }

        // ইনিশিয়াল চেক
        updateButtonUI();

        // চেঞ্জ ইভেন্ট
        variantCheckbox.on('change', function() {
            updateButtonUI();
        });

    });

    function getBaseUnitByGroupId(groupId) {
        if (!groupId) return;
        const url = "{{ route('units.getBaseUnitsByGroup', ':id') }}".replace(':id', groupId);
        $.get(url, function(response) {
            console.log(response);
            const baseUnitSelect = $('select[name="base_unit_id"]');
            baseUnitSelect.empty();
            baseUnitSelect.append('<option value="">{{ __('file.option.select_base_unit') }}</option>');
            $.each(response.data, function(index, baseUnit) {
                baseUnitSelect.append('<option value="' + baseUnit.id + '">' + baseUnit.name +
                    '</option>');
            });
            baseUnitSelect.trigger('change');
        });
    }

    function getSubUnitsByBaseUnitId(baseUnitId, baseUnitName) {
        if (!baseUnitId) return;
        const url = "{{ route('units.getSubUnits', ':id') }}".replace(':id', baseUnitId);

        $.get(url, function(response) {

            $('#unit_variables_container').empty();

            const purchaseUnitSelect = $('select[name="purchase_unit_id"]');
            const saleUnitSelect = $('select[name="sale_unit_id"]');
            const container = $('#unit_variables_container');

            purchaseUnitSelect.empty();
            saleUnitSelect.empty();
            container.empty();

            // Add Base Unit Option
            purchaseUnitSelect.append(`<option value="${baseUnitId}">${baseUnitName}</option>`);
            saleUnitSelect.append(`<option value="${baseUnitId}">${baseUnitName}</option>`);

            // Recursive function to iterate through the tree
            function processNestedUnits(units) {
                $.each(units, function(index, unit) {
                    // ড্রপডাউনে অ্যাড করা
                    $('select[name="purchase_unit_id"]').append(
                        `<option value="${unit.id}">${unit.name}</option>`);
                    $('select[name="sale_unit_id"]').append(
                        `<option value="${unit.id}">${unit.name}</option>`);

                    // যদি ফর্মুলা থাকে তবে ইনপুট ফিল্ড তৈরি
                    if (unit.is_formulaic && unit.display_params && unit.display_params.variables) {
                        let variables = unit.display_params.variables;

                        // একটি পরিষ্কার কন্টেইনার (Border ও Padding সহ)
                        let html = `
                <div class="p-3 mb-3 border rounded bg-light" data-unit-id="${unit.id}">
                    <h6 class="text-primary mb-2">${unit.name} <small class="text-muted">(Formula: ${unit.formula})</small></h6>
                    <div class="row g-2">`;

                        $.each(variables, function(i, varName) {
                            html += `
                    <div class="col-md-4">
                        <label class="form-label mb-1 fw-bold small">${varName} <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="unit_vars[${unit.id}][${varName}]" 
                               class="form-control form-control-sm" placeholder="Enter ${varName}" required>
                    </div>`;
                        });

                        html += `</div></div>`;
                        $('#unit_variables_container').append(html);
                    }

                    // রিকার্সিভলি কল করা
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
</script>
