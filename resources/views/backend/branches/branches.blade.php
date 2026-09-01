@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.branch_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.branch_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.branch_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBranchModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.branch') }}</a>
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
    <div class="modal fade" id="createBranchModal" tabindex="-1" aria-labelledby="createBranchModalLabel"aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createBranchModalLabel">{{ __('file.title.create_branch') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="createBranchForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('backend.branches._form', ['isEdit' => false, 'accounts' => $accounts, 'currencies' => $currencies])
                        <button type="submit" class="btn btn-primary mt-3">{{ __('file.button.create') }}
                            {{ __('file.branch') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBranchModal" tabindex="-1" aria-labelledby="editBranchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBranchModalLabel">{{ __('file.title.edit_branch') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="editBranchForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="id" id="edit_id">
                        @include('backend.branches._form', ['isEdit' => true, 'accounts' => $accounts, 'currencies' => $currencies])
                        <button type="submit" class="btn btn-primary mt-3">{{ __('file.button.update') }}
                            {{ __('file.branch') }}</button>
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
            handleFormSubmit('#createBranchForm', '#createBranchModal', '#branch-table', false);
            handleFormSubmit('#editBranchForm', '#editBranchModal', '#branch-table', true);

            $('#createBranchModal .select-currency').select2({
                placeholder: "Select a currency",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#createBranchModal')
            });

            $('#editBranchModal .select-currency').select2({
                placeholder: "Select a currency",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#editBranchModal')
            });
        });

        function editBranch(id) {
            const url = "{{ route('branches.edit', ':id') }}".replace(':id', id);

            $.get(url, function(response) {
                console.log(response);
                const $modal = $('#editBranchModal');
                const defaultImg = "{{ url('images/preview_image.png') }}";
                const imageSrc = response.branch_logo_url ? response.branch_logo_url : defaultImg;
                const editUrl = "{{ route('branches.update', ':branch') }}".replace(':branch', id);
                const isActiveStatus = !!response.branch.is_active ? 1 : 0;
                $modal.find('form').attr('action', editUrl);
                $modal.find('#edit_id').val(response.branch.id);
                $modal.find('#edit_name').val(response.branch.name);
                $modal.find('#edit_address').val(response.branch.address);
                $modal.find('#edit_email').val(response.branch.email);
                $modal.find('#edit_phone').val(response.branch.phone);
                $modal.find('#edit_bin_number').val(response.branch.bin_number);
                $modal.find('#edit_currency').val(response.branch.currency_id).trigger('change');
                $modal.find('#edit_is_active').val(isActiveStatus).trigger('change');
                $modal.find('#edit_image_preview').attr('src', imageSrc);
                $modal.modal('show');
            }).fail(function() {
                showFloatingAlert('error', "{{ __('file.message.unable_to_fetch_branch_data') }}");
            });
        }
    </script>
@endpush
