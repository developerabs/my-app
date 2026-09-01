// Always write code comments in English.

// 1. Initialize Dexie Database
window.db = new Dexie("SheraziPOS_GlobalDB");

db.version(5).stores({
    variants: "uid, product_id, product_variant_id, type, product_code, variant_code, generic_name",
    stocks: "++id, uid, [uid+branch_id], [uid+branch_id+product_batch_id], branch_id, product_batch_id",
    barcodes: "barcode, [barcode+product_batch_id], uid, product_batch_id",
    taxes: "id, name, rate",
    imei_records: "imei, [imei+status], product_id, product_variant_id, product_batch_id, branch_id",
});

let isSyncRunning = false;
let lastUserActivityTime = Date.now();

// Track user interactions to identify active vs idle terminal
['mousemove', 'keydown', 'click', 'touchstart'].forEach(evt => {
    window.addEventListener(evt, () => {
        lastUserActivityTime = Date.now();
    }, { passive: true });
});

// Self-healing database connection on upgrade failure
async function openPosDatabase() {
    try {
        await window.db.open();
    } catch (e) {
        console.warn("Resetting local client cache due to schema change...", e);
        await Dexie.delete("SheraziPOS_GlobalDB");
        window.db = new Dexie("SheraziPOS_GlobalDB");
        window.db.version(5).stores({
            variants: "uid, product_id, product_variant_id, type, product_code, variant_code, generic_name",
            stocks: "++id, uid, [uid+branch_id], [uid+branch_id+product_batch_id], branch_id, product_batch_id",
            barcodes: "barcode, [barcode+product_batch_id], uid, product_batch_id",
            taxes: "id, name, rate",
            imei_records: "imei, [imei+status], product_id, product_variant_id, product_batch_id, branch_id",
        });
        await window.db.open();
        localStorage.removeItem("sherazi_pos_last_sync_time");
        console.log("Dexie Database successfully recreated with fresh schema.");
    }
}

openPosDatabase();

/**
 * Main Synchronization Orchestrator Engine.
 * @param {string|null} uiProgressBarId - Target element ID to update progress text
 * @param {boolean} forceSync - If true, clears local storage/IndexedDB and forces server cache flush
 */
async function syncProducts(uiProgressBarId = null, forceSync = false) {
    if (isSyncRunning) {
        console.log("Sync already running. Skipping duplicate execution.");
        return;
    }

    isSyncRunning = true;

    try {
        await openPosDatabase();

        if (forceSync) {
            await clearLocalPosDatabaseCache();
        }

        const recordCount = await window.db.variants.count();
        const isLocalEmpty = recordCount === 0;

        const lastSyncTime = (isLocalEmpty || forceSync) ? null : localStorage.getItem('sherazi_pos_last_sync_time');

        let currentChunk = 0;
        let totalChunks = 1;
        let keepSyncing = true;
        let serverTimestamp = null;

        const apiEndpoint = (typeof baseUrl !== 'undefined' ? baseUrl : '') + "/products/get-all-products";

        while (currentChunk < totalChunks && keepSyncing) {
            updateSyncUiProgress(
                uiProgressBarId,
                `Fetching chunk ${currentChunk + 1} of ${totalChunks}...`,
            );

            const response = await axios.get(apiEndpoint, {
                params: {
                    last_sync_time: lastSyncTime,
                    chunk_index: currentChunk,
                    force: forceSync ? 1 : 0
                },
            });

            const resData = response.data;

            if (!resData.success) {
                throw new Error(resData.message || "Backend sync failed.");
            }

            if (currentChunk === 0) {
                totalChunks = resData.total_chunks || 1;
                serverTimestamp = resData.server_time;
                if (resData.is_delta) totalChunks = 1;
            }

            // Ingest active chunk payload
            if (resData.data && resData.data.length > 0) {
                const taxes = resData.taxes || [];
                const imei_records = resData.imei_records || [];
                await commitPayloadChunkToIndexedDb(resData.data, taxes, imei_records);
            }

            // Handle deletions received in delta sync
            if (resData.is_delta && resData.deleted_uids && resData.deleted_uids.length > 0) {
                await purgeDeletedUidsFromIndexedDb(resData.deleted_uids);
            }

            currentChunk++;
        }

        if (serverTimestamp) {
            localStorage.setItem("sherazi_pos_last_sync_time", serverTimestamp);
        }

        updateSyncUiProgress(uiProgressBarId, "Sync Completed Successfully!", true);
    } catch (error) {
        console.error("Critical error in sync engine:", error);
        updateSyncUiProgress(uiProgressBarId, `Sync Failed: ${error.message}`, false);
        throw error;
    } finally {
        isSyncRunning = false;
    }
}

/**
 * Commits payload chunk to IndexedDB atomically.
 */
async function commitPayloadChunkToIndexedDb(payloadChunk, taxes = [], imei_records = []) {
    await db.transaction("rw", "variants", "stocks", "barcodes", "taxes", "imei_records", async () => {

        if (taxes && taxes.length > 0) {
            await db.table("taxes").clear();
            await db.table("taxes").bulkPut(taxes);
        }

        if (imei_records && imei_records.length > 0) {
            await db.table("imei_records").clear();
            await db.table("imei_records").bulkPut(imei_records);
        }

        let variantsList = [];
        let barcodesList = [];
        let stocksList = [];
        let uidsInChunk = [];

        for (const node of payloadChunk) {
            if (!node.variant || !node.variant.uid) continue;

            const uid = node.variant.uid;
            uidsInChunk.push(uid);
            variantsList.push(node.variant);

            if (node.stocks && Array.isArray(node.stocks)) {
                for (const stockItem of node.stocks) {
                    stocksList.push({
                        uid: uid,
                        branch_id: stockItem.branch_id,
                        product_batch_id: stockItem.product_batch_id,
                        batch_no: stockItem.batch_no,
                        expiry_date: stockItem.expiry_date,
                        quantity: Number(stockItem.quantity) || 0,
                        cost: Number(stockItem.cost) || 0,
                        price: Number(stockItem.price) || 0,
                    });
                }
            }

            if (node.barcodes && Array.isArray(node.barcodes)) {
                for (const bCode of node.barcodes) {
                    if (bCode.barcode) {
                        barcodesList.push({
                            barcode: String(bCode.barcode),
                            uid: uid,
                            product_batch_id: bCode.product_batch_id || null
                        });
                    }
                }
            }
        }

        // Clean out modified UIDs first to prevent orphaned batches or old barcodes
        if (uidsInChunk.length > 0) {
            await db.table("barcodes").where("uid").anyOf(uidsInChunk).delete();
            await db.table("stocks").where("uid").anyOf(uidsInChunk).delete();
        }

        if (variantsList.length > 0) {
            await db.table("variants").bulkPut(variantsList);
        }
        if (stocksList.length > 0) {
            await db.table("stocks").bulkAdd(stocksList);
        }
        if (barcodesList.length > 0) {
            await db.table("barcodes").bulkPut(barcodesList);
        }
    });
}

/**
 * Remove deleted or deactivated products from local Dexie IndexedDB
 */
async function purgeDeletedUidsFromIndexedDb(deletedUids) {
    if (!deletedUids || deletedUids.length === 0) return;

    await db.transaction("rw", "variants", "stocks", "barcodes", async () => {
        await db.table("variants").where("uid").anyOf(deletedUids).delete();
        await db.table("stocks").where("uid").anyOf(deletedUids).delete();
        await db.table("barcodes").where("uid").anyOf(deletedUids).delete();
    });

    console.log(`[Delta-Sync] Purged ${deletedUids.length} removed items from local Dexie cache.`);
}

/**
 * Reset Local IndexedDB Cache
 */
async function clearLocalPosDatabaseCache() {
    await db.variants.clear();
    await db.stocks.clear();
    await db.barcodes.clear();
    await db.taxes.clear();
    await db.imei_records.clear();
    localStorage.removeItem("sherazi_pos_last_sync_time");
}

/**
 * UI Progress tracker
 */
function updateSyncUiProgress(elementId, textStatus, isFinalSuccess = null) {
    if (!elementId) return;
    const targetElement = $(`#${elementId}`);
    if (targetElement.length > 0) {
        targetElement.text(textStatus);
        if (isFinalSuccess === true) {
            targetElement.removeClass("text-danger text-warning").addClass("text-success");
        } else if (isFinalSuccess === false) {
            targetElement.removeClass("text-success text-warning").addClass("text-danger");
        } else {
            targetElement.removeClass("text-success text-danger").addClass("text-warning");
        }
    }
}

/**
 * Auto Background Delta Sync with Gatekeeper
 */
async function autoBackgroundSync() {
    try {
        const lastSyncTime = localStorage.getItem('sherazi_pos_last_sync_time');
        if (!lastSyncTime || isSyncRunning) return;

        const apiEndpoint = (typeof baseUrl !== 'undefined' ? baseUrl : '') + "/products/get-all-products";

        const response = await axios.get(apiEndpoint, {
            params: {
                last_sync_time: lastSyncTime,
                chunk_index: 0,
                force: 0
            }
        });

        const resData = response.data;

        if (resData.success) {
            if (resData.data && resData.data.length > 0) {
                await commitPayloadChunkToIndexedDb(resData.data, resData.taxes || [], resData.imei_records || []);
            }
            if (resData.deleted_uids && resData.deleted_uids.length > 0) {
                await purgeDeletedUidsFromIndexedDb(resData.deleted_uids);
            }
            if (resData.server_time) {
                localStorage.setItem("sherazi_pos_last_sync_time", resData.server_time);
            }
        }
    } catch (e) {
        console.warn("[Auto-Sync] Delta check skipped:", e.message);
    }
}

// Smart Polling: Every 3 minutes only if tab is visible & user was active within last 15 minutes
setInterval(() => {
    const isTabActive = document.visibilityState === "visible";
    const isUserActive = (Date.now() - lastUserActivityTime) < (15 * 60 * 1000);

    if (isTabActive && isUserActive) {
        autoBackgroundSync();
    }
}, 180000);

// Sync immediately when cashier refocuses tab
document.addEventListener("visibilitychange", function() {
    if (document.visibilityState === "visible") {
        lastUserActivityTime = Date.now();
        autoBackgroundSync();
    }
});

// Expose global functions
window.globalSyncProducts = syncProducts;
window.clearLocalPosDatabaseCache = clearLocalPosDatabaseCache;
window.autoBackgroundSync = autoBackgroundSync;