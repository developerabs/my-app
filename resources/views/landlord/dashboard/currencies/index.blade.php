@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.currency_management') }} - {{ $general_settings['site_title'] ?? $general_settings['company_name'] ?? 'SheraziPOS' }}
@endsection

@push('css')
    @include('landlord.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('landlord.layouts.partials.header')
        @slot('title'){{ __('file.title.currency_management') }}@endslot
        @slot('subtitle'){{ __('file.title.currency_management_desc') }}@endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCurrencyModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.currency') }}</a>    
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    {{ $dataTable->table(['class' => 'table nowrap responsive display']) }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <!-- Create Currency Modal -->
    <div class="modal fade" id="createCurrencyModal" tabindex="-1" aria-labelledby="createCurrencyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCurrencyModalLabel">{{ __('file.title.create_currency') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('landlord.currencies.store') }}" id="createCurrencyForm" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label" >Name * </label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="code" class="form-label" title="{{__('file.message.currency_code_note')}}">Code * <i class="fa-solid fa-info-circle ms-1 text-secondary"></i></label>
                            <input type="text" class="form-control" id="code" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label for="symbol" class="form-label">Symbol</label>
                            <input type="text" class="form-control" id="symbol" name="symbol">
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.create') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCurrencyModal" tabindex="-1" aria-labelledby="editCurrencyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCurrencyModalLabel">Edit Currency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="editCurrencyForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="currency_id" id="currency_id">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="code" class="form-label" title="{{__('file.message.currency_code_note')}}">Code * <i class="fa-solid fa-info-circle ms-1 text-secondary"></i></label>
                            <input type="text" class="form-control" id="edit_code" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label for="symbol" class="form-label">Symbol</label>
                            <input type="text" class="form-control" id="edit_symbol" name="symbol">
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('landlord.layouts.partials._datatable_bottom')
    <script>
        $('#createCurrencyForm').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let url = form.attr('action');
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text(
                        "{{ __('file.button.creating') }}...");
                },
                success: function(response) {
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.create') }}");
                    $('#createCurrencyModal').modal('hide');
                    $('#createCurrencyForm')[0].reset();
                    showFloatingAlert('success', response.message || 'Currency created successfully!');
                    $('#currency-table').DataTable().ajax.reload();
                },
                error: function(response) {
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.create') }}");
                    showFloatingAlert('error', response.responseJSON.message ||
                        'Unable to create currency. Please try again later.');
                }
            })
        })

        const editRoute = "{{ route('landlord.currencies.edit', ':currency') }}";

        function editCurrency(id) {
            let url = editRoute.replace(':currency', id);
            $.get(url, function(response) {
                $('#currency_id').val(response.id);
                $('#edit_name').val(response.name);
                $('#edit_code').val(response.code);
                $('#edit_symbol').val(response.symbol);
                $('#edit_exchange_rate').val(response.exchange_rate);
                $('#editCurrencyModal').modal('show');
            });
        }

        $('#editCurrencyForm').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);

            let url = "{{ route('landlord.currencies.update', ':currency') }}";
            url = url.replace(':currency', $('#currency_id').val());

            $.ajax({
                url: url,
                type: 'PATCH',
                data: form.serialize(),
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true)
                        .text("{{ __('file.button.updating') }}...");
                },
                success: function(response) {
                    form.find('button[type="submit"]').prop('disabled', false)
                        .text("{{ __('file.button.update') }}");
                    $('#editCurrencyModal').modal('hide');
                    $('#editCurrencyForm')[0].reset();
                    showFloatingAlert('success', response.message || 'Currency updated successfully!');
                    $('#currency-table').DataTable().ajax.reload();
                },
                error: function(response) {
                    form.find('button[type="submit"]').prop('disabled', false)
                        .text("{{ __('file.button.update') }}");
                    showFloatingAlert('error', response.responseJSON.message ||
                        'Unable to update currency. Please try again later.');
                }
            })
        });


        function deleteCurrency(id) {
            $('#deleteConfirmModal').modal('show');
            let deleteButton = $('#deleteConfirm');

            deleteButton.off('click').on('click', function() {
                let url = '{{ route('landlord.currencies.destroy', ['currency' => ':id']) }}';
                url = url.replace(':id', id);
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    beforeSend: function() {
                        deleteButton.prop('disabled', true).text(
                        "{{ __('file.button.deleting') }}...");
                    },
                    success: function(response) {
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('success', response.message ||
                            'Currency deleted successfully!');
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#currency-table').DataTable().ajax.reload();
                    },
                    error: function() {
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('error',
                        'Unable to delete currency. Please try again later.');
                    }
                });
            });
        }
    </script>
@endpush
