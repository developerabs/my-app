    <script src="{{ url('backend/assets/plugins/DataTables/datatables.min.js') }}"></script>
    <script src="{{ url('backend/assets/plugins/DataTables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ url('backend/assets/plugins/DataTables/dataTables.fixedHeader.min.js') }}"></script>
    <script src="{{ url('backend/assets/js/moment.min.js') }}"></script>
    <script src="{{ url('backend/assets/js/daterangepicker.js') }}"></script>
    <script src="{{ url('backend/assets/plugins/DataTables/datatables-date-filter.js') }}"></script>
    <script src="{{ url('backend/assets/plugins/DataTables/datatable-helper.js') }}"></script>
    <script src="{{ url('backend/assets/plugins/tippyjs/tippy-bundle.umd.min.js') }}"></script>
    <script src="{{ url('backend/assets/plugins/DataTables/action-menu.js') }}"></script>

    {{ $dataTable->scripts() }}
    {{-- {{ $dataTable->scripts(attributes: ['type' => 'module']) }} --}}

    <script>
        // Global Event Listener for "select-all" checkboxes in datatables
        $(document).on('change', '.select-all', function() {
            const isChecked = $(this).prop('checked');

            // ফিক্সড হেডার বা স্ক্রলিং থাকলে টেবিল ভাগ হয়ে যায়, 
            // তাই আমরা লারাগন বা ডাটাটেবিলের মূল কন্টেইনারকে টার্গেট করবো।
            const $wrapper = $(this).closest('.dataTables_wrapper, .dt-container');

            // পুরো কন্টেইনারের ভেতর থেকে সব রো-চেকবক্স খুঁজে বের করা
            const $checkboxes = $wrapper.find('.row-checkbox');

            $checkboxes.prop('checked', isChecked);
            updateBulkButtonState($wrapper);
        });

        // Individual row checkbox change event
        $(document).on('change', '.row-checkbox', function() {
            const $wrapper = $(this).closest('.dataTables_wrapper, .dt-container');
            const $allRows = $wrapper.find('.row-checkbox');
            const $checkedRows = $wrapper.find('.row-checkbox:checked');

            // মাস্টার চেকবক্স আপডেট (হেডারে গিয়ে খুঁজে বের করবে)
            $wrapper.find('.select-all').prop('checked', ($allRows.length === $checkedRows.length && $allRows
                .length > 0));

            updateBulkButtonState($wrapper);
        });
        // Reset button state on table redraw (e.g., pagination, filtering)
        $(document).on('draw.dt', function(e, settings) {
            // settings.nTableWrapper দিয়ে মূল কন্টেইনারকে ধরা হয় যা গ্লোবালি কাজ করে
            const $wrapper = $(settings.nTableWrapper);
            $wrapper.find('.select-all').prop('checked', false);
            updateBulkButtonState($wrapper);
        });

        // Function to update the bulk delete button state
        function updateBulkButtonState($wrapper) {
            const selectedCount = $wrapper.find('.row-checkbox:checked').length;

            // বাটনটি সাধারণত টেবিল কন্টেইনারের ভেতরেই থাকে
            const $btn = $wrapper.find('#bulk-delete-btn, .btn-bulk-delete');

            if (selectedCount > 0) {
                $btn.removeClass('disabled')
                    .prop('disabled', false)
                    .css({
                        'pointer-events': 'auto',
                        'opacity': '1'
                    })
                    .find('.bulk-count').text('(' + selectedCount + ')').removeClass('d-none');
            } else {
                $btn.addClass('disabled')
                    .prop('disabled', true)
                    .css({
                        'pointer-events': 'none',
                        'opacity': '0.6'
                    })
                    .find('.bulk-count').text('0').addClass('d-none');
            }
        }

        // $(document).ready(function() {
        //     // যখন ড্রপডাউনটি ওপেন হবে
        //     $(document).on('show.bs.dropdown', '.dropdown', function() {
        //         var $dropdown = $(this);
        //         var $menu = $dropdown.find('.dropdown-menu');

        //         // মেনুটিকে বডি ট্যাগে নিয়ে আসা
        //         $('body').append($menu.detach());

        //         // ড্রপডাউনের পজিশন ক্যালকুলেট করা
        //         var offset = $dropdown.offset();

        //         $menu.css({
        //             'display': 'block',
        //             'top': offset.top + $dropdown.outerHeight(),
        //             'left': offset.left,
        //             'position': 'absolute',
        //             'z-index': '9999'
        //         });
        //     });

        //     // যখন ড্রপডাউনটি বন্ধ হবে
        //     $(document).on('hide.bs.dropdown', '.dropdown', function() {
        //         var $dropdown = $(this);
        //         var $menu = $('body > .dropdown-menu'); // বডি থেকে মেনুটি ধরা
        //         $dropdown.append($menu.detach());
        //         $menu.hide();
        //     });
        // });
    </script>
