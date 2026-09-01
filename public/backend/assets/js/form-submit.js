// কমন ফর্ম হ্যান্ডলার (Create/Update)
function handleFormSubmit(
    formSelector,
    modalSelector,
    tableSelector,
    isUpdate = false,
    onSuccessCallback = null,
) {
    $(document).on("submit", formSelector, function (e) {
        e.preventDefault();
        let form = $(this);
        let formData = new FormData(form[0]);
        let submitBtn = form.find('button[type="submit"]');

        if (isUpdate) formData.append("_method", "PATCH");

        form.find('input[type="checkbox"]').each(function () {
            let name = this.name;

            if (name) {
                // যদি এটি বেনিফিট অ্যারে হয়, তবে একে ডিস্টার্ব করবো না
                if (name.includes("[]")) {
                    // benefits[] এর ডাটা FormData আগেই নিয়েছে, তাই এখানে কিছু করার দরকার নেই
                    return;
                } else {
                    // শুধুমাত্র is_active এর মতো সিঙ্গেল চেকবক্সের জন্য ১ বা ০ সেট করবো
                    formData.set(name, this.checked ? 1 : 0);
                }
            }
        });

        form.find(".phone-input").each(function () {
            const iti = this.iti; // Use instance stored on the element itself
            if (iti && iti.isValidNumber()) {
                formData.set(this.name, iti.getNumber());
            } else {
                formData.set(this.name, this.value); // Fallback to raw value if invalid or empty
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
                    .html(
                        `${isUpdate ? window.translations.updating : window.translations.creating}...`,
                    );
                form.find(".invalid-feedback").remove();
                form.find(".is-invalid").removeClass("is-invalid");
            },
            success: function (response) {
                if (response.status === false) {
                    showFloatingAlert("error", response.message);
                } else {
                    $(modalSelector).modal("hide");
                    showFloatingAlert("success", response.message);
                    $(tableSelector).DataTable().ajax.reload(null, false);
                    form[0].reset();
                    if (typeof onSuccessCallback === "function") {
                        onSuccessCallback(form, response);
                    }
                    let defaultImage = form
                        .find(".image-preview-class")
                        .data("default");
                    form.find(".image-preview-class").attr("src", defaultImage);
                }
            },
            error: function (xhr) {
                if (xhr.status === 402 || xhr.status === 403) {
                    let response = xhr.responseJSON;

                    // টোস্টার বা অ্যালার্ট দেখানো
                    if (typeof showFloatingAlert === "function") {
                        showFloatingAlert(
                            "error",
                            response.error || response.message,
                        );
                    } else {
                        alert(response.error || response.message);
                    }

                    // যদি রিডাইরেক্ট URL থাকে, তবে ২ সেকেন্ড পর রিডাইরেক্ট হবে
                    if (response.redirect) {
                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, 2000);
                    }
                    return; // ভ্যালিডেশন চেকে যাবে না
                }
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    try {
                        // The first error
                        let firstErrorKey = Object.keys(errors)[0];
                        let firstErrorMessage = errors[firstErrorKey][0];

                        // Check if the first error message is JSON
                        if (
                            firstErrorMessage.startsWith("{") &&
                            firstErrorMessage.endsWith("}")
                        ) {
                            let errorData = JSON.parse(firstErrorMessage);

                            if (errorData.is_trashed) {
                                let modal = $("#restoreConfirmModal");
                                let restoreBtn = $("#restoreConfirm");

                                submitBtn
                                    .prop("disabled", false)
                                    .html(
                                        isUpdate
                                            ? window.translations.update
                                            : window.translations.create,
                                    );

                                modal
                                    .find("#restoreMessage")
                                    .html(
                                        `<b>${errorData.name}</b> is in trash. <br> Do you want to restore it?`,
                                    );

                                modal.modal("show");

                                restoreBtn
                                    .off("click")
                                    .one("click", function () {
                                        $.ajax({
                                            url:
                                                "/trashes/restore/" +
                                                errorData.id,
                                            type: "POST",
                                            data: {},
                                            beforeSend: function () {
                                                restoreBtn
                                                    .prop("disabled", true)
                                                    .html(
                                                        '<i class="fas fa-spinner fa-spin"></i> Restoring...',
                                                    );
                                            },
                                            success: function (response) {
                                                modal.modal("hide");
                                                $(modalSelector).modal("hide"); // modal close
                                                showFloatingAlert(
                                                    "success",
                                                    response.message,
                                                );
                                                $(tableSelector)
                                                    .DataTable()
                                                    .ajax.reload(null, false);
                                            },
                                            error: function (res) {
                                                showFloatingAlert(
                                                    "error",
                                                    res.responseJSON?.message ||
                                                        "Restore failed!",
                                                );
                                            },
                                            complete: function () {
                                                restoreBtn
                                                    .prop("disabled", false)
                                                    .html("Restore");
                                            },
                                        });
                                    });

                                return; // Skip the rest of the code
                            }
                        }
                    } catch (e) {
                        console.error("JSON parse error:", e);
                    }

                    // If validation errors
                    handleValidationErrors(form, errors);
                } else {
                    showFloatingAlert(
                        "error",
                        xhr.responseJSON?.message || "Server Error!",
                    );
                }
            },
            complete: function () {
                submitBtn
                    .prop("disabled", false)
                    .html(
                        isUpdate
                            ? window.translations.update
                            : window.translations.create,
                    );
            },
        });
    });
}

// সিঙ্গেল ডিলিট
$(document).on("click", ".delete-btn", function (e) {
    e.preventDefault();

    let url = $(this).data("url"); // Delete URL
    let itemName = $(this).data("item") || "Item";
    let tableId = $(this).data("table-id") || ".datatable";
    let name = $(this).data("name") || "Item"; // Item name (e.g., Currency)
    let deleteBtn = $("#deleteConfirm"); // Modal confirm button

    // Show modal

    $("#deleteConfirmModal .modal-body").html(
        `Are you sure you want to delete <b>${itemName}</b> ?`,
    );

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
                //console.log("Delete Response:", response); // Debug log
                if (response.status === false) {
                    showFloatingAlert("error", response.message);
                } else {
                    $("#deleteConfirmModal").modal("hide");
                    showFloatingAlert(
                        "success",
                        response.message || `${name} deleted successfully!`,
                    );
                    $(tableId).DataTable().ajax.reload(null, false);
                } // Current page static, reload data
            },
            error: function (xhr) {
                // Log the full object in your browser console to see exactly what Laravel sent
                //console.error("Server Error Object:", xhr);

                let errorMessage = `Unable to delete ${name}.`;

                // Extract message from Laravel standard JSON format
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    // Fallback if Laravel crashes into an unexpected raw HTML template
                    errorMessage =
                        "Database constraint violation occurred on server.";
                }

                showFloatingAlert("error", errorMessage);
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
                deleteBtn
                    .prop("disabled", true)
                    .text(window.translations.deleting + "...");
            },
            success: function (response) {
                $("#deleteConfirmModal").modal("hide");
                if (typeof showFloatingAlert === "function") {
                    showFloatingAlert("success", response.message);
                } else {
                    alert(response.message);
                }
                // DataTable reload
                $.fn.dataTable
                    .tables({ visible: true, api: true })
                    .ajax.reload(null, false);
                // Uncheck all checkboxes
                $(".row-checkbox, .select-all").prop("checked", false);
            },
            error: function (xhr) {
                if (typeof showFloatingAlert === "function") {
                    showFloatingAlert("error", xhr.responseJSON.message);
                } else {
                    alert(xhr.responseJSON.message);
                }
            },
            complete: function () {
                deleteBtn
                    .prop("disabled", false)
                    .text(window.translations.delete);
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
