@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.trash_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.trash_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.trash_management_desc') }}
        @endslot
    @endcomponent
    {{-- DataTable Section --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        {{ $dataTable->table(['class' => 'table table-hover table-striped nowrap w-100']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('modals')
    
@endsection
@push('js')
    @include('backend.layouts.partials._datatable_bottom')

    <script>
        $(document).ready(function() {
            const dataTableId = '#trash-table';

            // ১. চেক-বক্স সিলেক্ট করলে বাটন এনাবেল এবং কাউন্ট দেখানো
            $(document).on('change', '.row-checkbox, .select-all', function() {
                let selectedCount = $('.row-checkbox:checked').length;
                if (selectedCount > 0) {
                    $('.bulk-restore-btn, .bulk-delete-btn').removeClass('disabled');
                    $('.bulk-restore-count, .bulk-delete-count').removeClass('d-none').text('(' +
                        selectedCount + ')');
                } else {
                    $('.bulk-restore-btn, .bulk-delete-btn').addClass('disabled');
                    $('.bulk-restore-count, .bulk-delete-count').addClass('d-none');
                }
            });

            // ২. রিস্টোর হ্যান্ডলার (ইন্ডিভিজুয়াল ও বাল্ক একসাথে)
            $(document).on('click', '.restore-item, .bulk-restore-btn', function(e) {
                e.preventDefault();
                let id = $(this).data('id') || null;
                let modal = $('#restoreConfirmModal');
                let restoreBtn = $('#restoreConfirm');
                let selectedCount = $('.row-checkbox:checked').length;

                // মডালে মেসেজ সেট করা
                let message = id ? "Are you sure you want to restore this item?" :
                    `Are you sure you want to restore ${selectedCount} selected items?`;
                modal.find('#restoreMessage').text(message);
                modal.modal('show');

                restoreBtn.off('click').one('click', function() {
                    let ids = id ? [id] : [];
                    if (!id) {
                        $('.row-checkbox:checked').each(function() {
                            ids.push($(this).val());
                        });
                    }

                    $.ajax({
                        url: id ? "{{ route('trash.restore', ':id') }}".replace(':id', id) :
                            "{{ route('trash.bulk-action') }}",
                        type: 'POST',
                        data: id ? {} : {
                            ids: ids,
                            action: 'restore',
                            _token: "{{ csrf_token() }}"
                        },
                        beforeSend: function() {
                            restoreBtn.prop('disabled', true).html(
                                '<i class="fas fa-spinner fa-spin"></i> Restoring...'
                                );
                        },
                        success: function(response) {
                            modal.modal('hide');
                            if (response.status) {
                                showFloatingAlert('success', response.message);
                                $(dataTableId).DataTable().ajax.reload(null, false);
                                if (!id) $('.select-all').prop('checked', false)
                                    .trigger('change');
                            }
                        },
                        error: function(response) {
                            showFloatingAlert('error', response.responseJSON.message);
                        },
                        complete: function() {
                            restoreBtn.prop('disabled', false).text(
                                "{{ __('file.button.restore') }}");
                        }
                    });
                });
            });

            // ৩. পার্মানেন্ট ডিলিট হ্যান্ডলার (ইন্ডিভিজুয়াল ও বাল্ক একসাথে)
            $(document).on('click', '.delete-permanent, .bulk-delete-btn', function(e) {
                e.preventDefault();
                let id = $(this).data('id') || null;
                let modal = $('#deleteConfirmModal'); // নিশ্চিত করুন এই আইডিটা আপনার মডালের সাথে মিলে
                let deleteBtn = $('#deleteConfirm');

                let message = id ? "Permanently delete this item? This cannot be recovered." :
                    "Permanently delete selected items? This cannot be recovered.";
                modal.find(".modal-body").html(`<p>${message}</p>`);
                modal.modal('show');

                deleteBtn.off('click').one('click', function() {
                    let ids = id ? [id] : [];
                    if (!id) {
                        $('.row-checkbox:checked').each(function() {
                            ids.push($(this).val());
                        });
                    }

                    $.ajax({
                        url: id ? "{{ route('trash.permanent-delete', ':id') }}".replace(
                            ':id', id) : "{{ route('trash.bulk-action') }}",
                        type: id ? 'DELETE' : 'POST',
                        data: id ? {
                            _token: "{{ csrf_token() }}"
                        } : {
                            ids: ids,
                            action: 'permanent-delete',
                            _token: "{{ csrf_token() }}"
                        },
                        beforeSend: function() {
                            deleteBtn.prop('disabled', true).html(
                                '<i class="fas fa-spinner fa-spin"></i> Deleting...'
                                );
                        },
                        success: function(response) {
                            console.log(response);
                            modal.modal('hide');
                            if (response.status === false) {
                                // Complete failure
                                showFloatingAlert('error', response.message);
                            } else if (response.status === 'warning') {
                                // Partial success: show warning alert, but still reload table data
                                showFloatingAlert('warning', response.message);
                                
                                $(dataTableId).DataTable().ajax.reload(null, false);
                                if (!id) $('.select-all').prop('checked', false).trigger('change');
                            } else {
                                // Complete success
                                showFloatingAlert('success', response.message);
                                
                                $(dataTableId).DataTable().ajax.reload(null, false);
                                if (!id) $('.select-all').prop('checked', false).trigger('change');
                            }
                        },
                        error: function(response) {
                            showFloatingAlert('error', response.responseJSON.message);
                        },
                        complete: function() {
                            deleteBtn.prop('disabled', false).text(
                                "{{ __('file.button.delete') }}");
                        }
                    });
                });
            });
        });
    </script>
@endpush
