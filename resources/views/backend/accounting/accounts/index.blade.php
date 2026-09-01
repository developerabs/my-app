@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.account_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.account_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.account_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.account') }}</a>
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    {{ $dataTable->table(['class' => 'table nowrap responsive display']) }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <!-- Create Account Modal -->
    <div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAccountModalLabel">{{ __('file.title.create_account') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('accounts.store') }}" id="createAccountForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('backend.accounting.accounts._form', ['isEdit' => false])
                        <button type="submit" class="btn btn-primary mt-3">{{ __('file.button.create') }}
                            {{ __('file.account') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAccountModalLabel">{{ __('file.title.edit_account') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="editAccountForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="id" id="edit_id">
                        @include('backend.accounting.accounts._form', ['isEdit' => true])
                        <button type="submit" class="btn btn-primary mt-3">{{ __('file.button.update') }}
                            {{ __('file.account') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')
    <script>
        $(document).ready(function() {
            handleFormSubmit('#createAccountForm', '#createAccountModal', '#account-table', false);
            handleFormSubmit('#editAccountForm', '#editAccountModal', '#account-table', true);

            // Initialize Flatpickr with altInput
            flatpickr(".date-picker", {
                altInput: true,
                altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                dateFormat: "Y-m-d",
                defaultDate: "today",
                minDate: typeof getMinDateSafe === "function" ? getMinDateSafe() : null,
                static: true,
                allowInput: true,
            });

            $('#createAccountModal .branch_id').select2({
                placeholder: "Select a Branch",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#createAccountModal')
            });
            $('#createAccountModal .currency_id').select2({
                placeholder: "Select Currency",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#createAccountModal')
            });

            $('#editAccountModal .branch_id').select2({
                placeholder: "Select a Branch",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#editAccountModal')
            });

            $('#editAccountModal .currency_id').select2({
                placeholder: "Select Currency",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#editAccountModal')
            });

            // 🟢 Auto-Select Branch Currency when Branch is chosen in Create Modal
            $('#branch_id').on('change', function() {
                const currencyId = $(this).find('option:selected').data('currency-id');
                if (currencyId) {
                    $('#currency_id').val(currencyId).trigger('change');
                }
            });
        });

        function editAccount(id) {
            const url = "{{ route('accounts.edit', ':id') }}".replace(':id', id);

            $.get(url, function(response) {
                const data = response.data;

                let updateUrl = "{{ route('accounts.update', ':account') }}".replace(':account', data.id);
                const $modal = $('#editAccountModal');
                const $form = $modal.find('#editAccountForm');
                $form.attr('action', updateUrl);

                // Populate Standard Inputs
                $form.find('#edit_id').val(data.id);
                $form.find('#edit_account_type').val(data.account_type).trigger('change');
                $form.find('#edit_opening_balance').val(data.opening_balance);
                $form.find('#edit_account_name').val(data.account_name);
                $form.find('#edit_account_number').val(data.account_number);
                $form.find('#edit_bank_name').val(data.bank_name);
                $form.find('#edit_branch_name').val(data.branch_name);
                $form.find('#edit_routing_number').val(data.routing_number);
                $form.find('#edit_branch_id').val(data.branch_id).trigger('change');
                $form.find('#edit_currency_id').val(data.currency_id).trigger('change');
                
                // 🟢 Populate Currency in Edit Modal
                $form.find('#edit_currency_id').val(data.currency_id);

                // Pass raw DB date directly to setFlatpickrSafe
                const $dateField = $form.find('#edit_opening_balance_date');
                if (typeof setFlatpickrSafe === "function") {
                    setFlatpickrSafe($dateField, data.opening_balance_date);
                } else {
                    $dateField.val(data.opening_balance_date);
                }

                $modal.modal('show');

            }).fail(function() {
                showFloatingAlert('error', "{{ __('file.message.unable_to_fetch_account_data') }}");
            });
        }
    </script>
@endpush