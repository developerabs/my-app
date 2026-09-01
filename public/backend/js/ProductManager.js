/**
 * Sherazi POS - Universal Product Manager
 * Handles: Search, Add Row, Modal Edit, Calculation
 */
const ProductManager = {
    config: {
        tableBody: '#product_body',
        totalField: '#grand-total',
        isPurchase: true // পেজ অনুযায়ী এটি চেঞ্জ হবে
    },

    // ১. ইনিশিয়ালাইজেশন
    init: function(config) {
        this.config = { ...this.config, ...config };
        this.attachEvents();
    },

    // ২. ইভেন্ট লিসেনার্স
    attachEvents: function() {
        const self = this;
        const $body = $(this.config.tableBody);

        // কোয়ান্টিটি চেঞ্জ
        $body.on('input', '.qty-input', function() {
            self.calculateRowTotal($(this).closest('tr'));
        });

        // রো রিমুভ
        $body.on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            self.updateGrandTotal();
        });

        // নামের ওপর ক্লিক করলে মডাল ওপেন
        $body.on('click', '.edit-product-link', function() {
            self.openEditModal($(this).closest('tr'));
        });
    },

    // ৩. রো অ্যাড করা (এটি গ্লোবাল ফাংশন)
    addRow: function(product, price) {
        let existingRow = $(`${this.config.tableBody} tr[data-uid="${product.uid}"]`);
        
        if (existingRow.length > 0) {
            let $qtyInput = existingRow.find('.qty-input');
            $qtyInput.val(parseFloat($qtyInput.val()) + 1);
            this.calculateRowTotal(existingRow);
            return;
        }

        // বারকোড মাস্টার লজিক
        let barcodeHtml = (product.is_batch) ? 
            `<input type="text" name="barcode[]" class="form-control form-control-sm mt-1" placeholder="Barcode">` : '';

        let rowHtml = `
            <tr data-uid="${product.uid}">
                <td>
                    <a href="javascript:void(0)" class="edit-product-link fw-bold" data-product-info='${JSON.stringify(product)}'>
                        ${product.name}
                    </a>
                    ${barcodeHtml}
                    <input type="hidden" name="product_id[]" value="${product.p_id}">
                    <input type="hidden" name="variant_id[]" value="${product.v_id || ''}">
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <input type="text" name="batch_no[]" class="form-control form-control-sm" placeholder="Batch">
                        <input type="date" name="expiry_date[]" class="form-control form-control-sm" 
                            ${product.has_expire_date ? '' : 'disabled'}>
                    </div>
                </td>
                <td>
                    <input type="number" name="qty[]" class="form-control form-control-sm qty-input" value="1" step="any">
                </td>
                <td class="text-end row-price-display">${price}</td>
                <td class="text-end row-discount-display">0.00</td>
                <td class="text-end row-tax-display">0.00</td>
                <td class="text-end fw-bold subtotal-display">${price}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm remove-row text-danger"><i class="fa fa-times"></i></button>
                </td>
                
                <input type="hidden" class="h-price" name="net_unit_cost[]" value="${price}">
                <input type="hidden" class="h-unit-id" name="purchase_unit_id[]" value="${product.purchase_unit_id || ''}">
                <input type="hidden" class="h-discount" name="row_discount[]" value="0">
                <input type="hidden" class="h-tax" name="row_tax[]" value="0">
                <input type="hidden" class="h-imei" name="imei_number[]" value="">
            </tr>`;

        $(this.config.tableBody).append(rowHtml);
        this.calculateRowTotal($(this.config.tableBody).find('tr:last'));
    },

    // ৪. ক্যালকুলেশন
    calculateRowTotal: function($row) {
        const qty = parseFloat($row.find('.qty-input').val()) || 0;
        const price = parseFloat($row.find('.h-price').val()) || 0;
        const discount = parseFloat($row.find('.h-discount').val()) || 0;
        const tax = parseFloat($row.find('.h-tax').val()) || 0;

        const subtotal = (price - discount + tax) * qty;
        $row.find('.subtotal-display').text(subtotal.toFixed(2));
        this.updateGrandTotal();
    },

    updateGrandTotal: function() {
        let total = 0;
        $('.subtotal-display').each(function() {
            total += parseFloat($(this).text()) || 0;
        });
        $(this.config.totalField).text(total.toFixed(2));
    }
};