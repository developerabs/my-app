<script>
    let saveTimeout;

    $(document).ready(async function() {

        // 1. Initialize Product Engine & Restore Saved Cart from LocalStorage
        await ProductManager.initialize();
        await CartManager.load('purchase_cart', appendProductToPurchaseGrid, function(meta) {
            if(meta.purchase_date) $('input[name="purchase_date"]').val(meta.purchase_date);
            if(meta.reference) $('input[name="reference"]').val(meta.reference);
            if(meta.memo_number) $('input[name="memo_number"]').val(meta.memo_number);
            if(meta.note) $('textarea[name="note"]').val(meta.note);
            if(meta.shipping_cost) $('#shipping-cost').val(meta.shipping_cost);
            if(meta.paid_amount) $('#paid-amount').val(meta.paid_amount);

            if(meta.supplier_id) $('#supplier_id').val(meta.supplier_id).trigger('change');
            if(meta.branch_id) $('select[name="branch_id"]').val(meta.branch_id).trigger('change');
            if(meta.purchase_status) $('select[name="purchase_status"]').val(meta.purchase_status).trigger('change');
            if(meta.order_discount_method) $('#order_discount_method').val(meta.order_discount_method).trigger('change');
            if(meta.order_tax_method) $('#order_tax_method').val(meta.order_tax_method).trigger('change');
            
            if(meta.order_discount_rate) $('#order_discount_rate').val(meta.order_discount_rate);
            if(meta.order_tax_rate) $('#order_tax_rate').val(meta.order_tax_rate);

            if(typeof ProductManager.updateGrandTotal === 'function') {
                const $table = $('#purchase-create-form #purchase-table');
                ProductManager.updateGrandTotal($table);
            }
        });

        // 2. Real-time Auto-Save to Cart on Input Change
        $('#purchase-create-form').on('change input', 'input:not(#product_search), select, textarea', function(e) {
            syncCart();
        });

        // 3. Load Master Taxes from Dexie
        async function loadTaxes() {
            if (window.db && window.db.taxes) {
                const taxes = await window.db.taxes.toArray();
                const select = $('#order_tax_method');

                taxes.forEach(tax => {
                    select.append(`<option value="${tax.rate}">${tax.name} (${tax.rate}%)</option>`);
                });
            }
        }
        await loadTaxes();

        // 4. Barcode Enter Key Scanner Listener
        $('#product_search').on('keydown', async function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const barcode = $(this).val();
                if (!barcode) return;

                await ProductManager.handleBarcodeScannerInput(barcode, function(product) {
                    if (product.type !== 'physical') {
                        if (typeof showFloatingAlert === "function") {
                            showFloatingAlert("error", "Only physical products can be added to Purchases!");
                        }
                        $('#product_search').val('').focus();
                        return;
                    }

                    appendProductToPurchaseGrid(product);
                    $('#product_search').val('').focus();
                });
            }
        });

        // 5. Autocomplete Search Listener
        ProductManager.initGlobalAutocomplete('#product_search', function(product) {
            appendProductToPurchaseGrid(product);
        }, ['physical']);

        // 6. Grid Row Builder
        async function appendProductToPurchaseGrid(product, savedItem = null) {
            const rowId = `purchase-row-${product.uid || product.product_id}`;
            const existingRow = $(`#${rowId}`);

            let isOpenModal = false;
            if (product.has_imei || product.product_barcode_type === 'master') {
                isOpenModal = true;
            }

            if (existingRow.length > 0) {
                const qtyInput = existingRow.find('.item-qty');
                qtyInput.val(parseFloat(qtyInput.val()) + 1);
                ProductManager.handleQtyChange(existingRow);
                return;
            }

            let taxMethod = product.tax_method || 'inclusive';
            let taxRate = await ProductManager.getTaxRate(product.tax_id);
            const decimals = ProductManager.decimals || 2;

            let unitOptionsHtml = '';
            const defaultUnitId = product.purchase_unit_id || product.base_unit_id;
            const baseUnitPrice = parseFloat(product.cost) || 0;

            if (product.unit_details) {
                Object.values(product.unit_details).forEach(unit => {
                    const isSelected = (unit.unit_id == defaultUnitId) ? 'selected' : '';
                    unitOptionsHtml += `<option value="${unit.unit_id}" ${isSelected}>${unit.short_name || unit.name}</option>`;
                });
            }

            let initialCost = baseUnitPrice;
            if (product.calculator && defaultUnitId) {
                const ratio = product.calculator.calculateRatio(defaultUnitId);
                initialCost = baseUnitPrice * ratio;
            }

            const isPartial = ($('select[name="purchase_status"]').val() === 'partial');

            const htmlRow = `
                <tr id="${rowId}" data-uid="${product.uid}">
                    <td>
                        <div class="d-flex align-items-center">
                            <input type="hidden" name="products[${product.uid}][product_id]" value="${product.product_id}">
                            <span class="fw-bold d-block editProduct">${product.product_name}</span>
                            ${isOpenModal ? '<span class="badge bg-primary imei-list ms-2" onclick="ImeiBarcodeManager.openModal($(this).closest(\'tr\'))" style="cursor:pointer;">IMEIs / Barcode</span>' : ''}
                        </div>
                        <small class="text-muted">
                            ${product.variant_name ? `${product.variant_name} | ` : ''}
                            ${product.variant_code || product.product_code || 'N/A'}
                        </small>
                    </td>

                    <td>
                        <div class="d-flex gap-1">
                            <input type="text" name="products[${product.uid}][batch_number]" class="form-control form-control-sm batch-input" placeholder="Select Batch" oninput="ProductManager.handleBatchChange($(this))" onblur="ProductManager.handleBatchChange($(this))">
                            <input type="hidden" name="products[${product.uid}][batch_id]" class="batch-id-hidden" value="">
                            <input type="text" name="products[${product.uid}][expire_date]" class="form-control form-control-sm expire-date-picker" placeholder="Exp Date" ${!product.has_expire_date ? 'disabled' : ''}>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex gap-1">
                            <input type="number" name="products[${product.uid}][quantity]" class="form-control form-control-sm item-qty text-center" value="1" min="0.1" step="any" onchange="ProductManager.handleQtyChange($(this).closest('tr'))">
                            <input type="number" name="products[${product.uid}][received_qty]" class="form-control form-control-sm received-qty text-center" 
                                value="1" min="0.1" step="any" placeholder="Received" 
                                style="${isPartial ? '' : 'display:none;'}" onchange="handleRcvQty($(this).closest('tr'))">
                            <select name="products[${product.uid}][unit_id]" class="form-control form-control-sm item-unit-selector" style="width: auto;" onchange="handleUnitChange($(this))">
                                ${unitOptionsHtml}
                            </select>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex gap-1">
                            <input type="hidden" name="products[${product.uid}][base_unit_price]" class="base-unit-price" value="${baseUnitPrice}">
                            <input type="number" name="products[${product.uid}][price]" class="form-control form-control-sm item-price text-end" value="${initialCost.toFixed(decimals)}" step="any" min="0" oninput="ProductManager.updateRowSubtotal($(this).closest('tr'))">
                        </div>
                    </td>

                    <td>
                        <div class="d-flex gap-1">
                            <select name="products[${product.uid}][discount_method]" class="form-control form-control-sm discount-method" style="width: auto;" onchange="calculateDiscount($(this))">
                                <option value="flat">Flat</option>
                                <option value="percentage">Percent</option>
                            </select>
                            <input type="number" name="products[${product.uid}][unit_discount]" class="form-control form-control-sm item-unit-discount text-end" min="0" step="any" value="0" placeholder="Unit Disc" oninput="calculateDiscount($(this))">
                            <input type="number" name="products[${product.uid}][total_discount]" class="form-control form-control-sm item-total-discount text-end" value="0" readonly placeholder="Total">
                        </div>
                    </td>

                    <td>
                        <div class="d-flex gap-1">
                            <select name="products[${product.uid}][tax_method]" class="form-control form-control-sm tax-method" style="width: 80px;" onchange="ProductManager.updateRowSubtotal($(this).closest('tr'))">
                                <option value="exclusive" ${taxMethod === 'exclusive' ? 'selected' : ''}>Excl</option>
                                <option value="inclusive" ${taxMethod === 'inclusive' ? 'selected' : ''}>Incl</option>
                            </select>
                            <input type="number" name="products[${product.uid}][tax_rate]" class="form-control form-control-sm tax-rate text-end" min="0" step="any" placeholder="Rate%" value="${taxRate}" oninput="ProductManager.updateRowSubtotal($(this).closest(\'tr\'))">
                            <input type="text" name="products[${product.uid}][tax_total]" class="form-control form-control-sm tax-total text-end" readonly value="0.00">
                        </div>
                    </td>

                    <td class="text-end align-middle fw-bold"><span class="item-subtotal">0.00</span></td>
                    <input type="hidden" class="item-subtotal-hidden" name="products[${product.uid}][subtotal]" value="0">

                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="$(this).closest('tr').remove(); ProductManager.updateGrandTotal($('#purchase-table')); syncCart();"><i class="fa fa-trash"></i></button>
                    </td>
                    <input type="hidden" class="item-imeis" name="products[${product.uid}][imei_list]" value="">
                    <input type="hidden" class="item-barcodes" name="products[${product.uid}][barcodes]" value="">
                </tr>
            `;

            $('#purchase_body').append(htmlRow);
            const $newRow = $(`#${rowId}`);
            $newRow.data('product-data', product); 

            if(savedItem){
                $newRow.find('.batch-input').val(savedItem.batch_number);
                $newRow.find('.batch-id-hidden').val(savedItem.batch_id);
                $newRow.find('.expire-date-picker').val(savedItem.expire_date);
                $newRow.find('.item-qty').val(savedItem.quantity);
                $newRow.find('.received-qty').val(savedItem.received_qty);
                $newRow.find('.item-unit-selector').val(savedItem.unit_id);
                $newRow.find('.item-price').val(savedItem.price);
                $newRow.find('.discount-method').val(savedItem.discount_method);
                $newRow.find('.item-unit-discount').val(savedItem.unit_discount);
                $newRow.find('.tax-method').val(savedItem.tax_method);
                $newRow.find('.tax-rate').val(savedItem.tax_rate);
                $newRow.find('.item-imeis').val(savedItem.imei_list);
                $newRow.find('.item-barcodes').val(savedItem.barcodes);
            }

            if (typeof flatpickr !== 'undefined') {
                $newRow.find('.expire-date-picker').flatpickr({
                    appendTo: document.body,
                    dateFormat: 'Y-m-d',
                    static: false,
                    allowInput: true,
                });
            }

            ProductManager.initBatchAutocomplete($newRow.find('.batch-input'));
            
            if (!savedItem && isOpenModal) {
                ImeiBarcodeManager.openModal($newRow);
            }

            ProductManager.updateRowSubtotal($newRow);
            syncCart();
        }

        // 7. Purchase Status Change Trigger
        $('select[name="purchase_status"]').on('change', function() {
            const purchaseStatus = $(this).val();
            if (purchaseStatus === 'partial') {
                $('.received-qty').show();
            } else {
                $('.received-qty').hide();
                $('.received-qty').val(1);
            }
        });

        // 8. Calculation Listeners
        $('input[name="order_discount_rate"], input[name="order_tax_rate"], input[name="shipping_cost"]').on('change input', function() {
            calculateOrderDiscount();
            const $table = $('#purchase-create-form #purchase-table');
            ProductManager.updateGrandTotal($table);
        });

        $('#order_discount_method').on('change', function() {
            calculateOrderDiscount();
            const $table = $('#purchase-create-form #purchase-table');
            ProductManager.updateGrandTotal($table);
        });

        function calculateOrderDiscount() {
            const discountMethod = $('#order_discount_method').find(':selected').val();
            let discountRate = parseFloat($('#order_discount_rate').val()) || 0;
            const subtotal = parseFloat($('#sub-total').text()) || 0;
            let maxLimit = (discountMethod === 'percentage') ? 100 : subtotal;

            if (discountRate > maxLimit) {
                if (typeof showFloatingAlert === "function") showFloatingAlert("error", "Discount limit exceeded.");
                discountRate = maxLimit;
                $('#order_discount_rate').val(discountRate.toFixed(ProductManager.decimals || 2));
            }
        }

        $('#order_tax_method').on('change', function() {
            const rate = parseFloat($(this).find(':selected').val()) || 0;
            $('#order_tax_rate').val(rate);
            const $table = $('#purchase-create-form #purchase-table');
            ProductManager.updateGrandTotal($table);
        });

        // =========================================================================
        // 🚀 9. FORM SUBMISSION & CART CLEARING ENGINE
        // =========================================================================
        $('#purchase-create-form').on('submit', function(e) {
            e.preventDefault();

            // Cancel any pending auto-save timeout
            clearTimeout(saveTimeout);

            const rowCount = $('#purchase_body tr[id^="purchase-row-"]').length;
            if (rowCount === 0) {
                if (typeof showFloatingAlert === "function") {
                    showFloatingAlert("error", "Please add at least one product to the purchase.");
                } else {
                    alert("Please add at least one product to the purchase.");
                }
                return false;
            }

            // =========================================================================
            // 🛑 IMEI QUANTITY VS ALLOCATION VALIDATION
            // =========================================================================
            let hasImeiError = false;
            let imeiErrorMessages = [];
            const purchaseStatus = $('select[name="purchase_status"]').val();

            $('#purchase_body tr').each(function() {
                const $row = $(this);
                const product = $row.data('product-data');

                // Check if product tracks IMEI
                const hasImei = product && (product.has_imei === true || product.has_imei == 1 || product.has_imei === '1' || product.has_imei === 'true');

                if (hasImei) {
                    // Determine target quantity based on purchase status
                    const purchaseQty = parseFloat($row.find('.item-qty').val()) || 0;
                    const receivedQty = parseFloat($row.find('.received-qty').val()) || 0;
                    const targetQty = (purchaseStatus === 'partial') ? receivedQty : purchaseQty;

                    // Calculate Unit Ratio (e.g. 1 Box = 10 Pcs means 10 IMEIs required)
                    let ratio = 1;
                    const unitId = $row.find('.item-unit-selector').val();
                    if (product.calculator && unitId) {
                        ratio = product.calculator.calculateRatio(unitId);
                    }

                    const expectedImeiCount = Math.floor(targetQty * ratio);

                    // Count allocated IMEIs
                    const imeiVal = $row.find('.item-imeis').val() ? $row.find('.item-imeis').val().trim() : '';
                    const actualImeis = imeiVal ? imeiVal.split(',').map(s => s.trim()).filter(s => s.length > 0) : [];
                    const actualImeiCount = actualImeis.length;

                    // Validate count
                    if (actualImeiCount !== expectedImeiCount) {
                        hasImeiError = true;
                        // 🔴 Red highlight the mismatched product row
                        $row.addClass('table-danger border border-danger');
                        
                        const prodTitle = product.product_name || 'Product';
                        imeiErrorMessages.push(`${prodTitle}: Required ${expectedImeiCount} IMEIs, but allocated ${actualImeiCount}.`);
                    } else {
                        // 🟢 Remove red highlight if valid
                        $row.removeClass('table-danger border border-danger');
                    }
                }
            });

            // If any IMEI mismatch is found, stop submission and alert user
            if (hasImeiError) {
                const fullMsg = imeiErrorMessages.join('<br>') || "IMEI count must match the required quantity!";
                if (typeof showFloatingAlert === "function") {
                    showFloatingAlert("error", fullMsg);
                } else {
                    alert(imeiErrorMessages.join('\n'));
                }
                return false; // Stop form submission
            }

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalBtnHtml = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving Purchase...');

            const formData = new FormData(this);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        // Clear Cart from LocalStorage & CartManager
                        if (typeof CartManager !== 'undefined' && typeof CartManager.clear === 'function') {
                            CartManager.clear('purchase_cart');
                        }
                        localStorage.removeItem('purchase_cart');

                        if (typeof showFloatingAlert === "function") {
                            showFloatingAlert("success", response.message || "Purchase created successfully!");
                        }

                        setTimeout(function() {
                            if (response.data && response.data.id) {
                                window.location.href = "{{ route('purchases.index') }}";
                            } else {
                                window.location.href = "{{ route('purchases.index') }}";
                            }
                        }, 600);
                    } else {
                        $submitBtn.prop('disabled', false).html(originalBtnHtml);
                        if (typeof showFloatingAlert === "function") {
                            showFloatingAlert("error", response.message || "Failed to save purchase.");
                        }
                    }
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);

                    let errorMessage = "An error occurred while saving the purchase.";

                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        if (xhr.responseJSON.errors) {
                            const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                            errorMessage = xhr.responseJSON.errors[firstKey][0];
                        }
                    }

                    if (typeof showFloatingAlert === "function") {
                        showFloatingAlert("error", errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        });

    });

    // Auto-Save Cart Helper
    function syncCart() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            if (typeof CartManager !== 'undefined' && typeof CartManager.save === 'function') {
                CartManager.save('purchase_cart', '#purchase-create-form #purchase-table', function() {
                    return {
                        purchase_date: $('input[name="purchase_date"]').val(),
                        supplier_id: $('#supplier_id').find(':selected').val(),
                        branch_id: $('select[name="branch_id"]').find(':selected').val(),
                        reference: $('input[name="reference"]').val(),
                        memo_number: $('input[name="memo_number"]').val(),
                        currency_id: $('select[name="currency_id"]').find(':selected').val(),
                        exchange_rate: $('input[name="exchange_rate"]').val(),
                        purchase_status: $('select[name="purchase_status"]').find(':selected').val(),
                        note: $('textarea[name="note"]').val(),
                        order_discount_method: $('#order_discount_method').find(':selected').val(),
                        order_discount_rate: $('#order_discount_rate').val(),
                        order_tax_method: $('#order_tax_method').find(':selected').val(),
                        order_tax_rate: $('#order_tax_rate').val(),
                        shipping_cost: $('#shipping-cost').val(),
                        paid_amount: $('#paid-amount').val()
                    };
                });
            }
        }, 600);
    }
</script>