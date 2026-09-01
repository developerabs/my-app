(function (window, $) {
    "use strict";

    const ImeiBarcodeManager = {
        
        openModal($row) {
            const product = $row.data('product-data');
            if (!product) return;

            // 🛑 STRICT BOOLEAN CHECK: "0", 0, false, null নিরাপদভাবে হ্যান্ডেল করা
            const hasImei = (product.has_imei === true || product.has_imei === 1 || product.has_imei === '1' || product.has_imei === 'true');
            const isMasterBarcode = (product.product_barcode_type === 'master');

            // যদি IMEI এবং Master Barcode কোনটিই না থাকে, তবে এখানেই প্রসেস বন্ধ করবে
            if (!hasImei && !isMasterBarcode) {
                return; 
            }

            const product_code = product.variant_code || product.product_code;
            const purchaseStatus = $('select[name="purchase_status"]').val();
            const PurchaseQty = $row.find('.item-qty').val();
            const ReceivedQty = $row.find('.received-qty').val();
            let qty = PurchaseQty;
            if (purchaseStatus === 'partial') {
                qty = ReceivedQty;
            }

            let ratio = 1;
            const unitId = $row.find('.item-unit-selector').val();
            if (product.calculator && unitId) {
                ratio = product.calculator.calculateRatio(unitId);
            }

            const baseQty = Math.floor(qty * ratio);
            const modal = $('#imeiBarcodeModal');
            modal.find('#product_row_id').val($row[0].id);
            modal.find('#modalTitle').text('IMEI / Barcode Allocation for ' + product.product_name + ' (' + product_code + ')');
            const modalBody = modal.find('.modal-body');
            modalBody.empty();

            if (hasImei) {
                modalBody.append('<h6 class="fw-bold text-primary border-bottom pb-1">IMEI Numbers</h6>');
                const imeiContainer = document.createElement('div');
                imeiContainer.className = 'imei-container';
                modalBody.append(imeiContainer);
                $(imeiContainer).append(this.generateImeiField(baseQty, $row));
            }

            if (isMasterBarcode) {
                modalBody.append('<h6 class="fw-bold text-success border-bottom pb-1 mt-3">Master Barcodes</h6>');
                const barcodeContainer = document.createElement('div');
                barcodeContainer.className = 'barcode-container';
                modalBody.append(barcodeContainer);
                $(barcodeContainer).append(this.generateBarcodeField($row));
            }

            modal.modal('show');
            this.initValidator();
        },

        generateImeiField(qty, $row){
            let existingImeiString = $row.find('.item-imeis').val() || "";
            let existingImeis = existingImeiString ? existingImeiString.split(',') : [];

            const fragment = document.createElement('div');
            fragment.className = 'imei-setup-wrapper';

            for (let i = 0; i < qty; i++) {
                const imeiValue = existingImeis[i] || '';
                const wrapper = document.createElement('div');
                wrapper.className = 'input-group mb-2';
                wrapper.innerHTML = `
                    <input type="text" class="form-control identifier-input" data-type="imei" value="${imeiValue}" placeholder="Enter IMEI ${i + 1}">
                    <div class="invalid-feedback error-msg" style="display: none;"></div>
                `;
                fragment.appendChild(wrapper);
            }

            if (existingImeis.length > qty) {
                const excessContainer = document.createElement('div');
                excessContainer.className = 'mt-3 p-2 bg-light border';
                excessContainer.innerHTML = `<label class="fw-bold small">Excess IMEIs (Click to copy):</label><div class="d-flex flex-wrap gap-1 mt-1"></div>`;
                
                const badgeList = excessContainer.querySelector('div');
                
                for (let i = qty; i < existingImeis.length; i++) {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-secondary cursor-pointer';
                    badge.style.cursor = 'pointer';
                    badge.innerText = existingImeis[i];
                    
                    badge.onclick = () => {
                        if (navigator.clipboard) {
                            navigator.clipboard.writeText(existingImeis[i]);
                        }
                        badge.className = 'badge bg-success';
                        setTimeout(() => badge.className = 'badge bg-secondary', 500);
                    };
                    
                    badgeList.appendChild(badge);
                }
                fragment.appendChild(excessContainer);
            }

            return fragment;
        },

        generateBarcodeField($row) {
            let existingBarcode = $row.find('.item-barcodes').val() || "";

            const fragment = document.createElement('div');
            fragment.className = 'barcode-setup-wrapper';
            
            fragment.innerHTML = `
                <div class="mb-2">
                    <label class="fw-bold small">Product Barcodes (Separate with comma):</label>
                    <textarea class="form-control identifier-input" data-type="barcode" rows="3" placeholder="Example: 12345,67890,ABCDE">${existingBarcode}</textarea>
                    <div class="invalid-feedback error-msg"></div>
                </div>
            `;

            return fragment;
        },

        async saveImeisToProductRow(){
            const modal = $('#imeiBarcodeModal');
            const allInputs = modal.find('.identifier-input[data-type="imei"]');

            let hasError = false;

            for (let input of allInputs) {
                await this.validateInput(input);
                if ($(input).hasClass('is-invalid')) {
                    hasError = true;
                }
            }
    
            if (hasError) {
                if (typeof showFloatingAlert === "function") showFloatingAlert("error", "Please fix the errors in the IMEI fields before saving!");
                modal.find('.identifier-input[data-type="imei"]').first().focus();
                return; 
            }

            const hasEmpty = modal.find('.identifier-input[data-type="imei"]').filter(function() {
                return $(this).val().trim() === "";
            }).length > 0;

            if (hasEmpty) {
                if (typeof showFloatingAlert === "function") showFloatingAlert("error", "Some IMEI fields are empty. Please fill them all.");
                return;
            }

            const productRowId = modal.find('#product_row_id').val();
            const row = $(`#${productRowId}`);
            const imeis = [];
            modal.find('.identifier-input[data-type="imei"]').each(function() {
                if ($(this).val().trim() !== "") imeis.push($(this).val().trim());
            });
            row.find('.item-imeis').val(imeis.join(','));

            const barcodes = modal.find('.identifier-input[data-type="barcode"]').val();
            row.find('.item-barcodes').val(barcodes);

            modal.modal('hide');
        },

        focusNextField(currentInput) {
            const modal = $('#imeiBarcodeModal');
            const allInputs = [...modal.find('.identifier-input[data-type="imei"]')];
            const currentIndex = allInputs.indexOf(currentInput);
            if(currentIndex > -1 && currentIndex < allInputs.length - 1) {
                allInputs[currentIndex + 1].focus();
            }
        },

        initValidator() {
            const modal = $('#imeiBarcodeModal');
            
            modal.off('keydown', '.identifier-input').on('keydown', '.identifier-input', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.focusNextField(e.target);
                }
            });

            modal.off('input', '.identifier-input[data-type="imei"]').on('input', '.identifier-input[data-type="imei"]', (e) => {
                this.validateInput(e.target);
            });
        },

        async validateInput(inputElement) {
            const value = $(inputElement).val().trim();
            if (!value) return;

            const allInputs = $('.identifier-input').not(inputElement);
            const isDuplicateInModal = allInputs.toArray().some(el => $(el).val().trim() === value);

            if (isDuplicateInModal) {
                this.setError(inputElement, "This IMEI is duplicated in this modal!");
                return;
            }

            let existsInTable = false;
            $('.item-imeis').not($(`#${$('#product_row_id').val()}`).find('.item-imeis')).each(function() {
                const val = $(this).val();
                if (val && val.split(',').includes(value)) {
                    existsInTable = true;
                }
            });

            if (existsInTable) {
                this.setError(inputElement, "This IMEI is already added in another product row!");
                return;
            }

            const existsInDb = await window.db.imei_records.where('imei').equals(value).count();
            if (existsInDb > 0) {
                this.setError(inputElement, "This IMEI already exists in database!");
            } else {
                this.clearError(inputElement);
            }

            const totalErrors = $('.identifier-input.is-invalid').length;
            const saveBtn = $('#imeiBarcodeModal').find('.save-btn');
            
            if (totalErrors > 0) {
                saveBtn.prop('disabled', true);
            } else {
                saveBtn.prop('disabled', false);
            }
        },

        setError(el, msg) {
            $(el).addClass('is-invalid');
            $(el).siblings('.error-msg').text(msg).css('display', 'block');
        },

        clearError(el) {
            $(el).removeClass('is-invalid');
            $(el).siblings('.error-msg').text('').hide();
        }
    };

    window.ImeiBarcodeManager = ImeiBarcodeManager;

})(window, jQuery);