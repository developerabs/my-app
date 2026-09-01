/**
 * Generic DataTable Filter Listener
 * It finds the target table and triggers draw() automatically.
 */
$(document).on('change keyup', '[data-dt-filter]', function () {
    const tableId = $(this).data('dt-filter'); // ড্রপডাউন থেকে টেবিল আইডি নিচ্ছে
    
    // উইন্ডো অবজেক্টে টেবিলটি আছে কি না চেক করে রিলোড দিচ্ছে
    if (window.LaravelDataTables && window.LaravelDataTables[tableId]) {
        window.LaravelDataTables[tableId].draw();
    } else {
        console.error(`DataTable [${tableId}] not found! Make sure setTableId('${tableId}') is used in PHP.`);
    }
});

function resetFilters(tableId) {
    $('[data-dt-filter="' + tableId + '"]').val('');
    if (window.LaravelDataTables && window.LaravelDataTables[tableId]) {
        window.LaravelDataTables[tableId].draw();
    }
}