(function (window, $, Dexie) {
    "use strict";

    const CoreProductManager = {
        async initialize() {
            try {
                const exists = await Dexie.exists("SheraziPOS_GlobalDB");
                let hasData = false;

                if (exists) {
                    if (!window.db.isOpen()) await window.db.open();
                    const count = await window.db.variants.count();
                    hasData = count > 0;
                }

                if (!exists || !hasData) {
                    console.warn("Database missing or empty! Starting sync...");
                    if (typeof window.globalSyncProducts === "function") {
                        await window.globalSyncProducts();
                    }
                }
            } catch (err) {
                console.error("Initialization failed:", err);
            }
        },

        injectCalculator(product) {
            if (
                product &&
                product.unit_details &&
                typeof CompoundUnitCalculator !== "undefined"
            ) {
                product.calculator = new CompoundUnitCalculator(
                    product.unit_details,
                );
            }
            return product;
        },

        async findByBarcode(barcode) {
            if (!barcode) return null;
            const match = await window.db.barcodes
                .where("barcode")
                .equals(barcode.trim())
                .first();
            if (!match) return null;

            const product = await window.db.variants
                .where("uid")
                .equals(match.uid)
                .first();
            return this.injectCalculator(product);
        },

        async searchProducts(term, limit = 15, allowedTypes = null) {
            if (!term) return [];
            const token = term.toLowerCase();

            const results = await window.db.variants
                .filter((item) => {
                    const matchesSearch = (
                        (item.product_name?.toLowerCase().includes(token)) ||
                        (item.variant_name?.toLowerCase().includes(token)) ||
                        (item.product_code?.toLowerCase().includes(token)) ||
                        (item.variant_code?.toLowerCase().includes(token))
                    );

                    if (!matchesSearch) return false;

                    // 💡 পারচেজের জন্য কেবল ['physical'] প্রোডাক্ট ফিল্টার করা
                    if (allowedTypes && Array.isArray(allowedTypes) && allowedTypes.length > 0) {
                        return allowedTypes.includes(item.type);
                    }

                    return true;
                })
                .limit(limit)
                .toArray();

            return results.map(p => this.injectCalculator(p));
        },

        async handleBarcodeScannerInput(barcode, callback) {
            if (!barcode) return;

            const product = await this.findByBarcode(barcode);

            if (product) {
                if (typeof callback === "function") {
                    callback(product);
                }
            } else {
                if (typeof showFloatingAlert === "function")
                    showFloatingAlert(
                        "error",
                        "Product not found with this barcode!",
                    );
            }
        },

        initGlobalAutocomplete(selector, onSelectCallback, allowedTypes = null) {
            $(selector).autocomplete({
                delay: 150,
                minLength: 1,
                source: async (request, response) => {
                    const nodes = await this.searchProducts(request.term, 15, allowedTypes);

                    if (nodes.length === 0) {
                        response([{
                            label: `+ Add New Product: "${request.term}"`,
                            value: "add_new",
                            term: request.term
                        }]);
                        return;
                    }

                    if (nodes.length === 1) {
                        let product = nodes[0];
                        onSelectCallback(product);
                        $(selector).val("").autocomplete("close");
                        return;
                    }

                    response(nodes.map(node => ({
                        label: `${node.product_name} ${node.variant_name ? `(${node.variant_name})` : ""} [${node.variant_code || node.product_code || "N/A"}]`,
                        value: node.product_name,
                        raw: node
                    })));
                },
                select: (event, ui) => {
                    event.preventDefault();

                    if (ui.item.value === "add_new") {
                        $(selector).val("");
                        if (typeof ProductManager.openProductModal === "function") {
                            ProductManager.openProductModal(ui.item.term);
                        }
                        return;
                    }

                    let product = ui.item.raw;

                    if (event.shiftKey){
                        if (typeof ProductManager.openProductModal === "function") {
                            ProductManager.openProductModal(product.product_name);
                        }
                        return;
                    }

                    if (typeof onSelectCallback === "function") {
                        onSelectCallback(product);
                    }
                    $(selector).val("").focus();
                }
            });
        },

        get decimals() {
            return window.appSettings && window.appSettings.decimal_digits
                ? window.appSettings.decimal_digits
                : 2;
        },

        async getTaxRate(taxId) {
            if (!taxId) return 0;
            const tax = await window.db.taxes.get(taxId);
            return tax ? parseFloat(tax.rate) : 0;
        },

        // 💡 FIXED: Price calculation based on selected unit ratio (Transaction Currency)
        calculatePrice(row) {
            const product = row.data("product-data");
            const baseUnitPrice =
                parseFloat(row.find(".base-unit-price").val()) || 0;
            const unitId = row.find(".item-unit-selector").val();

            if (product && product.calculator && unitId) {
                const ratio = product.calculator.calculateRatio(unitId);
                return baseUnitPrice * ratio;
            }
            return baseUnitPrice;
        },

        handleQtyChange(row) {
            const qtyInput = row.find(".item-qty");
            const product = row.data("product-data");
            let qty = parseFloat(qtyInput.val()) || 0;
            if (isNaN(qty) || qty < 0.01) {
                qtyInput.val(1);
                qty = 1;
                if (typeof showFloatingAlert === "function")
                    showFloatingAlert(
                        "error",
                        "Quantity must be greater than 0.",
                    );
            }
            if (
                product &&
                (product.has_imei || product.product_barcode_type === "master")
            ) {
                ImeiBarcodeManager.openModal(row);
            }
            ProductManager.updateRowSubtotal(row);
        },

        updateRowSubtotal($row) {
            let qtyInput = $row.find(".item-qty");
            let qty = parseFloat(qtyInput.val());
            if (isNaN(qty) || qty < 0.01) {
                qty = 1;
                qtyInput.val(1);
                if (typeof showFloatingAlert === "function")
                    showFloatingAlert(
                        "error",
                        "Quantity must be greater than 0.",
                    );
            }
            const cost = parseFloat($row.find(".item-price").val()) || 0;
            const totalDisc =
                parseFloat($row.find(".item-total-discount").val()) || 0;
            const taxMethod = $row.find(".tax-method").val();
            const taxRate = parseFloat($row.find(".tax-rate").val()) || 0;

            let baseTotal = qty * cost - totalDisc;

            if (baseTotal < 0) {
                if (typeof showFloatingAlert === "function")
                    showFloatingAlert(
                        "error",
                        "Total discount must be less than total price.",
                    );
                baseTotal = 0;
                $row.find(".item-total-discount").val(
                    baseTotal.toFixed(this.decimals),
                );
            }

            let taxTotal =
                taxMethod === "inclusive"
                    ? (baseTotal * taxRate) / (100 + taxRate)
                    : (baseTotal * taxRate) / 100;

            $row.find(".tax-total").val(taxTotal.toFixed(this.decimals));
            const subtotal =
                taxMethod === "exclusive" ? baseTotal + taxTotal : baseTotal;

            $row.find(".item-subtotal").text(subtotal.toFixed(this.decimals));
            $row.find(".item-subtotal-hidden").val(
                subtotal.toFixed(this.decimals),
            );
            this.updateGrandTotal($row.closest("table"));
        },

        updateGrandTotal($table) {
            let subtotal = 0;
            let grandTotal = 0;
            $table.find(".item-subtotal").each(function () {
                subtotal += parseFloat($(this).text()) || 0;
            });
            $("#sub-total").text(subtotal.toFixed(this.decimals));
            const $container = $("#order_summary");
            let discountMethod = $container
                .find("#order_discount_method")
                .val();
            let discountRate =
                parseFloat($container.find("#order_discount_rate").val()) || 0;
            let discountTotal = discountRate;
            if (discountMethod === "percentage") {
                discountTotal = (discountRate * subtotal) / 100;
            }
            $container
                .find("#order_discount_amount")
                .val(discountTotal.toFixed(this.decimals));
            let taxRate =
                parseFloat($container.find("#order_tax_rate").val()) || 0;
            let taxTotal = (taxRate * subtotal) / 100;
            $container
                .find("#order_tax_amount")
                .val(taxTotal.toFixed(this.decimals));
            let shippingAmount =
                parseFloat($container.find("#shipping-cost").val()) || 0;
            grandTotal = subtotal - discountTotal + taxTotal + shippingAmount;
            $("#grand-total").text(grandTotal.toFixed(this.decimals));
            ProductManager.calculateDue();
        },

        initBatchAutocomplete: function ($input) {
            $input
                .autocomplete({
                    minLength: 0,
                    source: async function (request, response) {
                        const row = $input.closest("tr");
                        const uid = row.data("uid");
                        const branchId = $('select[name="branch_id"]').val();

                        const batches = await db.stocks
                            .where({ uid: uid, branch_id: branchId })
                            .toArray();

                        response(
                            batches.map((b) => ({
                                label: b.batch_no,
                                value: b.batch_no,
                                data: b,
                            })),
                        );
                    },
                    select: function (event, ui) {
                        const row = $input.closest("tr");
                        const data = ui.item.data;

                        row.find(".batch-id-hidden").val(data.product_batch_id);
                        row.find(".expire-date-picker")
                            .val(data.expire_date)
                            .prop("disabled", true);
                    },
                })
                .focus(function () {
                    $(this).autocomplete("search", "");
                });
        },

        handleBatchChange: async function ($input) {
            const row = $input.closest("tr");
            const typedValue = $input.val().trim();
            const product = row.data("product-data");
            const branchId = $('select[name="branch_id"]').val();
            const uid = row.data("uid");

            if (typedValue === "") {
                row.find(".batch-id-hidden").val("");
                if (product && product.has_expire_date) {
                    row.find(".expire-date-picker")
                        .val("")
                        .prop("disabled", false);
                }
                return;
            }

            const existingBatch = await db.stocks
                .where({ uid: uid, branch_id: branchId, batch_no: typedValue })
                .first();

            if (existingBatch) {
                row.find(".batch-id-hidden").val(
                    existingBatch.product_batch_id,
                );
                row.find(".expire-date-picker")
                    .val(existingBatch.expire_date)
                    .prop("disabled", true);
            } else {
                row.find(".batch-id-hidden").val("");
                if (product && product.has_expire_date) {
                    row.find(".expire-date-picker").prop("disabled", false);
                }
            }
        },

        calculateDue: function () {
            const grandTotal = parseFloat($("#grand-total").text()) || 0;
            let paidAmount = parseFloat($("#paid-amount").val()) || 0;
            if (paidAmount > grandTotal) {
                if (typeof showFloatingAlert === "function")
                    showFloatingAlert(
                        "error",
                        "Paid amount cannot be greater than grand total.",
                    );
                paidAmount = grandTotal;
                $("#paid-amount").val(
                    paidAmount.toFixed(ProductManager.decimals),
                );
            }
            const dueAmount = grandTotal - paidAmount;
            $("#due-amount").text(dueAmount.toFixed(ProductManager.decimals));
        },
    };

    window.ProductManager = CoreProductManager;

    // 💡 FIXED: Unit change handler only opens IMEI modal if product actually tracks IMEI or Master Barcode
    window.handleUnitChange = function ($select) {
        const $row = $select.closest("tr");
        const product = $row.data("product-data");
        const newPrice = CoreProductManager.calculatePrice($row);
        $row.find(".item-price").val(newPrice.toFixed(ProductManager.decimals));

        // 🛑 STRICT BOOLEAN CHECK
        const hasImei =
            product &&
            (product.has_imei === true ||
                product.has_imei === 1 ||
                product.has_imei === "1" ||
                product.has_imei === "true");
        const isMasterBarcode =
            product && product.product_barcode_type === "master";

        // শুধুমাত্র সত্যি থাকলে তবেই অ্যালার্ট ও মোডাল কল হবে
        if (hasImei || isMasterBarcode) {
            if (typeof showFloatingAlert === "function") {
                showFloatingAlert(
                    "warning",
                    "Unit updated. Please verify IMEI/Barcode allocations.",
                );
            }
            ImeiBarcodeManager.openModal($row);
        }

        ProductManager.updateRowSubtotal($row);
    };

    window.handleRcvQty = function ($row) {
        const qtyInput = parseFloat($row.find(".item-qty").val());
        const rvcQtyInput = parseFloat($row.find(".received-qty").val());
        const product = $row.data("product-data");
        if (rvcQtyInput > qtyInput) {
            $row.find(".received-qty").val(qtyInput);
            if (typeof showFloatingAlert === "function")
                showFloatingAlert(
                    "error",
                    "Received quantity cannot be greater than quantity.",
                );
        }
        if (
            product &&
            (product.has_imei || product.product_barcode_type === "master")
        ) {
            ImeiBarcodeManager.openModal($row);
        }
    };

    window.calculateDiscount = function ($el) {
        const row = $el.closest("tr");
        const qty = parseFloat(row.find(".item-qty").val()) || 0;
        const unitPrice = parseFloat(row.find(".item-price").val()) || 0;
        const discMethod = row.find(".discount-method").val();
        let unitDisc = parseFloat(row.find(".item-unit-discount").val()) || 0;

        let maxLimit = discMethod === "percentage" ? 100 : unitPrice;

        if (unitDisc > maxLimit) {
            if (typeof showFloatingAlert === "function")
                showFloatingAlert("error", "Discount limit exceeded.");
            unitDisc = maxLimit;
            row.find(".item-unit-discount").val(
                unitDisc.toFixed(ProductManager.decimals),
            );
        }

        const actualUnitDiscount =
            discMethod === "percentage"
                ? (unitPrice * unitDisc) / 100
                : unitDisc;

        row.find(".item-total-discount").val(
            (actualUnitDiscount * qty).toFixed(ProductManager.decimals),
        );
        ProductManager.updateRowSubtotal(row);
    };
})(window, jQuery, Dexie);
