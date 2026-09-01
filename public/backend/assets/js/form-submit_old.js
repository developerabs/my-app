function handleCreateForm(formSelector, modalSelector, tableSelector) {
    $(document).on("submit", formSelector, function (e) {
        e.preventDefault();
        let form = $(this);
        let rawForm = form[0];
        let formData = new FormData(rawForm);
        let submitBtn = form.find('button[type="submit"]');

        form.find('input[type="checkbox"]').each(function() {
            let name = $(this).attr('name');
            if (name) {
                formData.set(name, $(this).is(':checked') ? 1 : 0);
            }
        });

        $.ajax({
            url: form.attr("action"),
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                submitBtn
                    .prop("disabled", true)
                    .html(window.translations.creating + "...");
                // Remove previous error messages
                form.find(".invalid-feedback").remove();
                form.find(".is-invalid").removeClass("is-invalid");
            },
            success: function (response) {
                $(modalSelector).modal("hide");
                showFloatingAlert(
                    "success",
                    response.message || "Created successfully!",
                );
                $(tableSelector).DataTable().ajax.reload(null, false);
                form.trigger("reset");
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    // If validation errors
                    handleValidationErrors(form, xhr.responseJSON.errors);
                } else {
                    // Any other error (500 or 404)
                    showFloatingAlert(
                        "error",
                        xhr.responseJSON?.message || "Create failed!",
                    );
                }
                submitBtn
                    .prop("disabled", false)
                    .html(window.translations.create);
            },
            complete: function () {
                submitBtn
                    .prop("disabled", false)
                    .html(window.translations.create);
            },
        });
    });
}

function handleUpdateForm(formSelector, modalSelector, tableSelector) {
    $(document).on("submit", formSelector, function (e) {
        e.preventDefault();
        let form = $(this);
        let rawForm = form[0];
        let formData = new FormData(rawForm);
        let submitBtn = form.find('button[type="submit"]');
        formData.append("_method", "PATCH");

        form.find('input[type="checkbox"]').each(function() {
            let name = $(this).attr('name');
            if (name) formData.set(name, $(this).is(':checked') ? 1 : 0);
        });

        $.ajax({
            url: form.attr("action"),
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                submitBtn
                    .prop("disabled", true)
                    .html(window.translations.updating + "...");
            },
            success: function (response) {
                $(modalSelector).modal("hide");
                showFloatingAlert(
                    "success",
                    response.message || "Updated successfully!",
                );
                $(tableSelector).DataTable().ajax.reload(null, false);
                form.trigger("reset");
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    // If validation errors
                    handleValidationErrors(form, xhr.responseJSON.errors);
                } else {
                    // Any other error (500 or 404)
                    showFloatingAlert(
                        "error",
                        xhr.responseJSON?.message || "Update failed!",
                    );
                }
                submitBtn
                    .prop("disabled", false)
                    .html(window.translations.update);
            },
            complete: function () {
                submitBtn
                    .prop("disabled", false)
                    .html(window.translations.update);
            },
        });
    });
}

$(document).on("click", ".delete-btn", function (e) {
    e.preventDefault();

    let url = $(this).data("url"); // Delete URL
    let tableId = $(this).data("table-id") || ".datatable";
    let name = $(this).data("name") || "Item"; // Item name (e.g., Currency)
    let deleteBtn = $("#deleteConfirm"); // Modal confirm button

    // Show modal
    $("#deleteConfirmModal").modal("show");

    // Button click event
    deleteBtn.off("click").on("click", function () {
        $.ajax({
            url: url,
            type: "DELETE",
            beforeSend: function () {
                deleteBtn
                    .prop("disabled", true)
                    .text(window.translations.deleting + "...");
            },
            success: function (response) {
                $("#deleteConfirmModal").modal("hide");
                showFloatingAlert(
                    "success",
                    response.message || `${name} deleted successfully!`,
                );
                $(tableId).DataTable().ajax.reload(null, false); // Current page static, reload data
            },
            error: function (xhr) {
                showFloatingAlert(
                    "error",
                    xhr.responseJSON?.message || `Unable to delete ${name}.`,
                );
            },
            complete: function () {
                deleteBtn
                    .prop("disabled", false)
                    .text(window.translations.delete);
            },
        });
    });
});

// Bulk Delete Handler
$(document).on("click", "#bulk-delete-btn", function (e) {
    e.preventDefault();
    if ($(this).hasClass("disabled")) return;

    let ids = [];
    $(".row-checkbox:checked").each(function () {
        ids.push($(this).val());
    });

    const url = $(this).data("url");
    const deleteBtn = $("#deleteConfirm");
    const count = ids.length;

    // Show confirmation modal
    $("#deleteConfirmModal .modal-body").html(
        `Are you sure you want to delete <b>${count}</b> items?`,
    );
    $("#deleteConfirmModal").modal("show");

    deleteBtn.off("click").on("click", function () {
        $.ajax({
            url: url,
            type: "POST",
            data: {
                ids: ids,
            },
            beforeSend: function () {
                deleteBtn.prop("disabled", true).text(window.translations.deleting + "...");
            },
            success: function (response) {
                $("#deleteConfirmModal").modal("hide");
                if (typeof showFloatingAlert === "function") {
                    showFloatingAlert("success", response.message);
                } else {
                    alert(response.message);
                }
                // DataTable reload
                $(".datatable").DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                if (typeof showFloatingAlert === "function") {
                    showFloatingAlert("error", xhr.responseJSON.message);
                } else {
                    alert(xhr.responseJSON.message);
                }
            },
            complete: function () {
                deleteBtn.prop("disabled", false).text(window.translations.delete);
            },
        });
    });
});

// Form Error Handler
function handleValidationErrors(form, errors) {
    // Remove previous error messages
    form.find(".invalid-feedback").remove();
    form.find(".is-invalid").removeClass("is-invalid");

    // Finding and displaying errors
    $.each(errors, function (field, messages) {
        //Error message for fields with dot notation
        let fieldName = field.replace(/\./g, "_");
        let inputField = form.find(`[name="${field}"], [name="${field}[]"]`);

        inputField.addClass("is-invalid");
        // Showing error message after the input field
        inputField.after(`<div class="invalid-feedback">${messages[0]}</div>`);
    });
}
