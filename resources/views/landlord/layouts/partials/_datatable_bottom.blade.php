    <script src="{{ asset('backend/assets/plugins/DataTables/datatables.min.js') }}"></script>
    <script src="{{ asset('backend/assets/plugins/DataTables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('backend/assets/plugins/DataTables/dataTables.fixedHeader.min.js') }}"></script>
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}

    <script>
        $(document).ready(function() {
            // যখন ড্রপডাউনটি ওপেন হবে
            $(document).on('show.bs.dropdown', '.dropdown', function() {
                var $dropdown = $(this);
                var $menu = $dropdown.find('.dropdown-menu');

                // মেনুটিকে বডি ট্যাগে নিয়ে আসা
                $('body').append($menu.detach());

                // ড্রপডাউনের পজিশন ক্যালকুলেট করা
                var offset = $dropdown.offset();

                $menu.css({
                    'display': 'block',
                    'top': offset.top + $dropdown.outerHeight(),
                    'left': offset.left,
                    'position': 'absolute',
                    'z-index': '9999'
                });
            });

            // যখন ড্রপডাউনটি বন্ধ হবে
            $(document).on('hide.bs.dropdown', '.dropdown', function() {
                var $dropdown = $(this);
                var $menu = $('body > .dropdown-menu'); // বডি থেকে মেনুটি ধরা
                $dropdown.append($menu.detach());
                $menu.hide();
            });
        });
    </script>
