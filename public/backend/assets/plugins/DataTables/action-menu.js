(function (window, document) {
    "use strict";

    const ActionMenu = {
        init(context = document) {
            context.querySelectorAll(".action-menu").forEach((button) => {
                // Already initialized
                if (button.dataset.tippyInitialized === "1") {
                    return;
                }

                const menu = document.getElementById(button.dataset.menu);

                if (!menu) {
                    return;
                }

                tippy(button, {
                    trigger: "click",
                    interactive: true,
                    allowHTML: true,
                    arrow: false,
                    appendTo: () => document.body,
                    placement: "bottom-end",
                    animation: "shift-away",
                    theme: "light-border",
                    maxWidth: "none",
                    zIndex: 9999,
                    hideOnClick: true,
                    content() {
                        return menu.innerHTML;
                    },
                    onShow(instance) {
                        // Close all other menus
                        tippy.hideAll({
                            exclude: instance,
                        });
                    },
                });
                button.dataset.tippyInitialized = "1";
            });
        },

        refresh(context = document) {
            context.querySelectorAll(".action-menu").forEach((button) => {
                if (button._tippy) {
                    button._tippy.destroy();
                }

                button.removeAttribute("data-tippy-initialized");
                button.removeAttribute("data-tippy-initialized");
                button.dataset.tippyInitialized = "";
            });

            this.init(context);
        },
    };

    window.ActionMenu = ActionMenu;

    // Initial Page Load
    document.addEventListener("DOMContentLoaded", function () {
        ActionMenu.init();
    });

    // Any DataTable Draw
    $(document).on("draw.dt", function () {
        ActionMenu.refresh();
    });

    // Bootstrap Modal
    $(document).on("shown.bs.modal", function (e) {
        ActionMenu.init(e.target);
    });

    // Ajax Complete
    $(document).ajaxComplete(function () {
        ActionMenu.refresh();
    });
})(window, document);
