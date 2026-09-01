@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.lead_source_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.lead_source_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.lead_source_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLeadSourceModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.lead_source') }}
            </a>
        @endslot
    @endcomponent

    {{-- Filter Section --}}
    <div class="row mb-3">
        <div class="col-md-12">
            {{-- Mobile Toggle Button --}}
            <button class="btn btn-outline-primary d-md-none w-100 mb-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterCollapse">
                <i class="fa-solid fa-filter me-2"></i> {{ __('file.field.show_filters') }}
            </button>

            <div class="collapse d-md-block" id="filterCollapse">
                <div class="card border-0 mb-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            {{-- Filter Icon & Title (Desktop Only) --}}
                            <div class="col-auto d-none d-md-flex align-items-center gap-2">
                                <i class="fa-solid fa-filter text-primary"></i>
                                <span class="fw-bold text-secondary">{{ __('file.field.filters') }}:</span>
                            </div>

                            <div class="col-12 col-md-auto" style="min-width: 180px;">
                                <select id="filter-status" data-dt-filter="lead-source-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_status') }}</option>
                                    <option value="1">{{ __('file.option.active') }}</option>
                                    <option value="0">{{ __('file.option.inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto"
                                    onclick="resetFilters('lead-source-table')">
                                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('file.button.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    <div class="modal fade" id="createLeadSourceModal" tabindex="-1" aria-labelledby="createLeadSourceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createLeadSourceModalLabel">{{ __('file.button.create') }}
                        {{ __('file.lead_source') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('lead-sources.store') }}" method="POST" id="createLeadSourceForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.crm.lead_source._form', [
                            'isEdit' => false,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editLeadSourceModal" tabindex="-1" aria-labelledby="editLeadSourceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLeadSourceModalLabel">{{ __('file.button.edit') }}
                        {{ __('file.lead_source') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="editLeadSourceForm" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        @include('backend.crm.lead_source._form', [
                            'isEdit' => true,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')
    <script>
        $(document).ready(function() {
            handleFormSubmit('#createLeadSourceForm', '#createLeadSourceModal', '#lead-source-table', false);
            handleFormSubmit('#editLeadSourceForm', '#editLeadSourceModal', '#lead-source-table', true);
        });

        function editLeadSource(id) {
            let url = "{{ route('lead-sources.edit', ':leadSource') }}".replace(':leadSource', id);

            $.get(url, function(response) {
                console.log(response);
                let leadSource = response.lead_source;

                let $modal = $('#editLeadSourceModal');
                let $form = $('#editLeadSourceForm');
                let updateUrl = "{{ route('lead-sources.update', ':leadSource') }}".replace(':leadSource', id);

                $form.attr('action', updateUrl);
                $form.find('input[name="name"]').val(leadSource.name);
                $form.find('input[name="order"]').val(leadSource.sort_order);
                $form.find('select[name="is_active"]').val(leadSource.is_active ? 1 : 0);

                $modal.modal('show');
            });
        }
    </script>
@endpush
