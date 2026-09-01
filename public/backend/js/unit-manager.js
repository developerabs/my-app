(function(window, $) {
    "use strict";

    /**
     * UnitManager handles all unit-related UI operations
     * such as fetching base units and sub-units with formula inputs.
     */
    window.UnitManager = {

        /**
         * Fetch Base Units by Group ID
         * @param {number|string} groupId
         * @param {string} targetSelect
         * @param {string} placeholderText - Pass translated text from Blade
         * @param {function} [callback]
         */
        fetchBaseUnits: function(groupId, targetSelect, placeholderText = 'Select Base Unit', callback = null) {
            if (!groupId) return;

            const url = window.urls.getBaseUnits.replace(':id', groupId);
            
            $.get(url, function(response) {
                const baseUnitSelect = $(targetSelect);
                baseUnitSelect.empty().append(`<option value="">${placeholderText}</option>`);

                $.each(response.data, function(index, baseUnit) {
                    baseUnitSelect.append(`<option value="${baseUnit.id}">${baseUnit.name}</option>`);
                });

                baseUnitSelect.trigger('change');
                if (typeof callback === 'function') callback(response);
            });
        },

        /**
         * Fetch Sub Units and Generate Formula Fields
         * @param {number|string} baseUnitId - The ID of the selected base unit
         * @param {string} baseUnitName - The name of the selected base unit
         * @param {string} containerSelector - Selector for the formula inputs container
         * @param {string} purchaseSelectSelector - Selector for purchase unit dropdown
         * @param {string} saleSelectSelector - Selector for sale unit dropdown
         */
        fetchSubUnits: function(baseUnitId, baseUnitName, containerSelector, purchaseSelectSelector, saleSelectSelector) {
            if (!baseUnitId) return;

            const url = window.urls.getSubUnits.replace(':id', baseUnitId);
            
            $.get(url, function(response) {
                const purchaseUnitSelect = $(purchaseSelectSelector);
                const saleUnitSelect = $(saleSelectSelector);
                const container = $(containerSelector);

                // Reset dropdowns
                purchaseUnitSelect.empty().append(`<option value="${baseUnitId}">${baseUnitName}</option>`);
                saleUnitSelect.empty().append(`<option value="${baseUnitId}">${baseUnitName}</option>`);
                container.empty();

                // Recursive function to process units and nested sub-units
                function processNestedUnits(units) {
                    $.each(units, function(index, unit) {
                        purchaseUnitSelect.append(`<option value="${unit.id}">${unit.name}</option>`);
                        saleUnitSelect.append(`<option value="${unit.id}">${unit.name}</option>`);

                        // Generate formula inputs if unit is formulaic
                        // if (unit.is_formulaic && unit.display_params && unit.display_params.variables) {
                        //     let html = `
                        //         <div class="p-3 mb-3 border rounded bg-light" data-unit-id="${unit.id}">
                        //             <h6 class="text-primary mb-2">${unit.name} <small class="text-muted">(Formula: ${unit.formula})</small></h6>
                        //             <div class="row g-2">`;

                        //     $.each(unit.display_params.variables, function(i, varName) {
                        //         html += `
                        //             <div class="col-md-4">
                        //                 <label class="form-label mb-1 fw-bold small">${varName} <span class="text-danger">*</span></label>
                        //                 <input type="number" step="any" name="unit_vars[${unit.id}][${varName}]" 
                        //                        class="form-control form-control-sm" placeholder="Enter ${varName}" required>
                        //             </div>`;
                        //     });

                        //     html += `</div></div>`;
                        //     container.append(html);
                        // }

                        if (unit.is_formulaic && unit.display_params && unit.display_params.variables) {
                            let html = `
                                <div class="p-2 mb-2 border rounded bg-light" data-unit-id="${unit.id}">
                                    <h6 class="text-primary mb-0" style="font-size: 13px;">
                                        ${unit.name} <small class="text-muted">(Formula: ${unit.formula})</small>
                                    </h6>
                                    <div class="row g-1">`; // g-2 থেকে g-1 করা হয়েছে গ্যাপ কমানোর জন্য

                            $.each(unit.display_params.variables, function(i, varName) {
                                html += `
                                    <div class="col-md-3"> <label class="form-label mb-0 fw-bold" style="font-size: 12px;">${varName} <span class="text-danger">*</span></label>
                                        <input type="number" step="any" name="unit_vars[${unit.id}][${varName}]" 
                                            class="form-control form-control-sm" placeholder="${varName}" required>
                                    </div>`;
                            });

                            html += `</div></div>`;
                            container.append(html);
                        }

                        // Process deep nested units
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
    };

})(window, jQuery);