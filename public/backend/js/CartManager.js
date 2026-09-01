const CartManager = {
    isRestoring: false, // লুপ এড়ানোর জন্য ফ্ল্যাগ
    saveTimeout: null,  // ডেবাউন্সিং এর জন্য টাইমআউট

    // সেভ করার লজিক (স্মার্ট ডেবাউন্সিং সহ)
    save: function(cartKey, tableSelector, getExtraDataCallback) {
        if (this.isRestoring) return;

        // ডেবাউন্সিং: টাইপ করা থামানোর ৬০০ মিলিসেকেন্ড পর সেভ হবে
        let self = this;
        clearTimeout(self.saveTimeout);
        self.saveTimeout = setTimeout(() => {
            let cartData = [];
            $(tableSelector + ' tbody tr').each(function() {
                let $row = $(this);
                // শুধুমাত্র প্রয়োজনীয় ডাটা সেভ করছি (মেমোরি সেভ করার জন্য)
                cartData.push({
                    uid: $row.data('uid'),
                    productData: $row.data('product-data'),
                    batch_number: $row.find('.batch-input').val(),
                    batch_id: $row.find('.batch-id-hidden').val(),
                    expire_date: $row.find('.expire-date-picker').val(),
                    quantity: $row.find('.item-qty').val(),
                    received_qty: $row.find('.received-qty').val(),
                    unit_id: $row.find('.item-unit-selector').val(),
                    price: $row.find('.item-price').val(),
                    discount_method: $row.find('.discount-method').val(),
                    unit_discount: $row.find('.item-unit-discount').val(),
                    tax_method: $row.find('.tax-method').val(),
                    tax_rate: $row.find('.tax-rate').val(),
                    imei_list: $row.find('.item-imeis').val(),
                    barcodes: $row.find('.item-barcodes').val()
                });
            });

            let extraData = typeof getExtraDataCallback === 'function' ? getExtraDataCallback() : {};
            
            let fullPayload = {
                items: cartData,
                meta: extraData
            };

            localStorage.setItem(cartKey, JSON.stringify(fullPayload));
            //console.log("Cart Auto-Saved Successfully.");
        }, 600);
    },

    // লোড করার লজিক (Dexie থেকে ডাটা রিফ্রেশ করা হয়)
    load: async function(cartKey, appendCallback, applyMetaCallback) {
        this.isRestoring = true;
        const savedCart = localStorage.getItem(cartKey);
        
        if (savedCart) {
            const fullData = JSON.parse(savedCart);
            
            // নিশ্চিত করা যে ডাটাবেস কানেকশন তৈরি হয়েছে
            if (!window.db) {
                //console.error("Database not initialized yet.");
                this.isRestoring = false;
                return;
            }
            if (fullData.meta && typeof applyMetaCallback === 'function') {
                applyMetaCallback(fullData.meta);
            }

            if (fullData.items) {
                for (const item of fullData.items) {
                    try {
                        const freshProduct = await window.db.variants.get({ product_id: item.productData.product_id });
                        if (freshProduct) {
                            await appendCallback(freshProduct, item);
                        }
                    } catch (err) {
                        //console.error("Failed to restore product data.", item, err);
                    }
                }
            }
            
        }
        this.isRestoring = false;
    },

    loadFromData: async function(fullData, appendCallback, applyMetaCallback) {
        this.isRestoring = true;
        if (fullData) {
            await this.processCartPayload(fullData, appendCallback, applyMetaCallback);
        }
        this.isRestoring = false;
    },

    processCartPayload: async function(fullData, appendCallback, applyMetaCallback) {
        if (!window.db) {
            return;
        }

        if (fullData.items && Array.isArray(fullData.items)) {
            for (const item of fullData.items) {
                try {
                    let freshProduct = null;
                    if (item.uid) {
                        freshProduct = await window.db.variants.get(item.uid);
                    } else if (item.product_id) {
                        freshProduct = await window.db.variants.where('product_id').equals(item.product_id).first();
                    }

                    if (freshProduct && typeof appendCallback === 'function') {
                        await appendCallback(freshProduct, item);
                    }
                } catch (err) {
                    console.error("Failed to restore item in cart", item, err);
                }
            }
        }

        // সব আইটেম রো রেন্ডার হওয়ার পর মেটা-ডাটা বসবে
        if (fullData.meta && typeof applyMetaCallback === 'function') {
            applyMetaCallback(fullData.meta);
        }
    },

    // ক্লিয়ার করা
    clear: function(cartKey) {
        localStorage.removeItem(cartKey);
        //console.log("Cart Cleared.");
    }
};