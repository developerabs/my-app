@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.tax_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.tax_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.tax_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaxModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.tax') }}</a>
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
    <div class="modal fade" id="createTaxModal" tabindex="-1" aria-labelledby="createTaxModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTaxModalLabel">{{ __('file.title.create_tax') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('taxes.store') }}" id="createTaxForm" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('file.field.name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="rate" class="form-label"
                                title="{{ __('file.message.tax_rate_note') }}">{{ __('file.field.rate') }} <span class="text-danger">*</span><i
                                    class="fa-solid fa-info-circle ms-1 text-secondary"></i></label>
                            <input type="number" step="0.01" class="form-control" id="rate" name="rate" required>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.create') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTaxModal" tabindex="-1" aria-labelledby="editTaxModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaxModalLabel">{{ __('file.title.edit_tax') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="editTaxForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="tax_id" id="tax_id">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_rate" class="form-label"
                                title="{{ __('file.message.tax_rate_note') }}">{{ __('file.field.rate') }} <span class="text-danger">*</span> <i
                                    class="fa-solid fa-info-circle ms-1 text-secondary"></i></label>
                            <input type="number" step="0.01" class="form-control" id="edit_rate" name="rate" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
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
            handleFormSubmit('#createTaxForm', '#createTaxModal', '#tax-table', false);
            handleFormSubmit('#editTaxForm', '#editTaxModal', '#tax-table', true);
        })
        const editRoute = "{{ route('taxes.edit', ':tax') }}";

        function editTax(id) {
            let url = editRoute.replace(':tax', id);
            $.get(url, function(response) {
                let tax = response.data;
                $editUrl = "{{ route('taxes.update', ':tax') }}".replace(':tax', id);
                $('#editTaxForm').attr('action', $editUrl);
                $('#tax_id').val(tax.id);
                $('#edit_name').val(tax.name);
                $('#edit_rate').val(tax.rate);
                $('#editTaxModal').modal('show');
            });
        }
    </script>
@endpush
