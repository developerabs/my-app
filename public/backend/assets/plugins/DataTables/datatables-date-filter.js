(function($) {
    if ($.fn.dataTable) {
        $.fn.dataTable.ext.feature.push({
            fnInit: function(settings) {
                var dateInput = $('<input type="text" class="form-control form-control-sm ms-md-2" style="width: 100%; padding: 10px; cursor: pointer;" placeholder="Filter by Date" readonly>');
                
                // ডাটাটেবিলের ইন্টারনাল অবজেক্টে একটি প্রোপার্টি তৈরি করা
                settings._dateFilter = { start: '', end: '' };

                setTimeout(function() {
                    if ($.fn.daterangepicker) {
                        dateInput.daterangepicker({
                            autoUpdateInput: false,
                            locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
                        });

                        dateInput.on('apply.daterangepicker', function(ev, picker) {
                            var s = picker.startDate.format('YYYY-MM-DD');
                            var e = picker.endDate.format('YYYY-MM-DD');
                            $(this).val(s + ' - ' + e);
                            
                            // সেটিংস অবজেক্টে ডেটা রাখা
                            settings._dateFilter.start = s;
                            settings._dateFilter.end = e;
                            
                            // টেবিল রিলোড
                            new $.fn.dataTable.Api(settings).ajax.reload();
                        });

                        dateInput.on('cancel.daterangepicker', function(ev, picker) {
                            $(this).val('');
                            settings._dateFilter.start = '';
                            settings._dateFilter.end = '';
                            new $.fn.dataTable.Api(settings).ajax.reload();
                        });
                    }
                }, 200);

                return $('<div class="d-inline-block w-100"></div>').append(dateInput)[0];
            },
            cFeature: 'd'
        });

        // ক্লিয়ার বাটন রিসেট লজিক
        $(document).on('click', '.btn-secondary', function() {
            window.dt_start_date = "";
            window.dt_end_date = "";
            $('#date-range-filter').val('');
        });
    }
})(jQuery);