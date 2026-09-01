/**
 * Global Select-All + Auto-Sync Feature Controller
 * -----------------------------------------------
 * @param selectAllSelector   → Select All checkbox
 * @param itemSelector        → Individual feature checkboxes
 * @param inputSelector       → Limit input field inside the item
 */
function initSelectAll(selectAllSelector, itemSelector, inputSelector) {

    let $selectAll = $(selectAllSelector);
    let $items     = $(itemSelector);

    // Select All -> Check/Uncheck all
    $selectAll.on("change", function () {
        let checked = $(this).is(":checked");
        $items.prop("checked", checked).trigger("change");
    });

    // Individual update
    $items.on("change", function () {

        // Enable/Disable limit input
        if (inputSelector) {
            let $input = $(this).closest(".feature-item").find(inputSelector);
            if ($input.length) {
                $input.prop("disabled", !$(this).is(":checked"));
            }
        }

        // Auto update Select All
        let allChecked = $items.length === $items.filter(":checked").length;
        $selectAll.prop("checked", allChecked);
    });
}
