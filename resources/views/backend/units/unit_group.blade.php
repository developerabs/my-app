@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.unit_group_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.unit_group_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.unit_group_management_desc') }}
        @endslot
        @slot('button')
            <a href="{{ route('units.index') }}" class="btn btn-primary"><i class="fa-solid fa-list me-1"></i>
                {{ __('file.button.list') }} {{ __('file.unit') }}</a>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUnitGroupModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.unit_group') }}</a>
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
    <!-- Create unit Modal -->
    <div class="modal fade" id="createUnitGroupModal" tabindex="-1" aria-labelledby="createUnitGroupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUnitGroupModalLabel">{{ __('file.title.create_unit_group') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('unit-groups.store') }}" id="createUnitGroupForm" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name"
                                class="form-label small fw-semibold text-secondary">{{ __('file.field.group_name') }} <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                class="form-control form-control-lg bg-light border-0 shadow-none fs-6"
                                placeholder="e.g. Tiles Unit Group, Pharma Group" required>
                        </div>

                        <div class="mb-4">
                            <label for="description"
                                class="form-label small fw-semibold text-secondary">{{ __('file.field.description') }}
                                (Optional)</label>
                            <textarea name="description" id="description" rows="3" class="form-control bg-light border-0 shadow-none fs-6"
                                placeholder="Briefly describe what this group is for..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.create') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUnitGroupModal" tabindex="-1" aria-labelledby="editUnitGroupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUnitGroupModalLabel">{{ __('file.title.edit_unit_group') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="editUnitGroupForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="unit_group_id" id="unit_group_id">
                        <div class="mb-3">
                            <label for="edit_name"
                                class="form-label small fw-semibold text-secondary">{{ __('file.field.group_name') }} <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name"
                                class="form-control form-control-lg bg-light border-0 shadow-none fs-6"
                                placeholder="e.g. Tiles Unit Group, Pharma Group" required>
                        </div>

                        <div class="mb-4">
                            <label for="edit_description"
                                class="form-label small fw-semibold text-secondary">{{ __('file.field.description') }}
                                (Optional)</label>
                            <textarea name="description" id="edit_description" rows="3" class="form-control bg-light border-0 shadow-none fs-6"
                                placeholder="Briefly describe what this group is for..."></textarea>
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
            handleFormSubmit('#createUnitGroupForm', '#createUnitGroupModal', '#unit-group-table', false);
            handleFormSubmit('#editUnitGroupForm', '#editUnitGroupModal', '#unit-group-table', true); 
        });

        function editUnitGroup(id) {
            let url = "{{ route('unit-groups.edit', ':unitGroup') }}".replace(':unitGroup', id);
            $.get(url, function(response) {
                let data = response.data;
                let $modal = $('#editUnitGroupModal');
                let $form = $('#editUnitGroupForm');
                let updateUrl = "{{ route('unit-groups.update', ':unitGroup') }}".replace(':unitGroup', id);

                $form.attr('action', updateUrl);
                $form.find('input[name="name"]').val(data.name);
                $form.find('textarea[name="description"]').val(data.description);
                $form.find('input[name="unit_group_id"]').val(data.id);
                $modal.modal('show');

            })
        }
    </script>
@endpush
