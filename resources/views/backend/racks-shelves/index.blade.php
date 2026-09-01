@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.rack_shelf_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.rack_shelf_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.rack_shelf_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRackModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.rack') }}
            </a>
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
    <div class="modal fade" id="createRackModal" tabindex="-1" aria-labelledby="createRackModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRackModalLabel">{{ __('file.button.create') }}
                        {{ __('file.rack') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('racks-shelves.store') }}" method="POST" id="createRackForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.racks-shelves._form', [
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

    <div class="modal fade" id="editRackModal" tabindex="-1" aria-labelledby="editRackModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRackModalLabel">{{ __('file.button.edit') }}
                        {{ __('file.rack') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="editRackForm" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        @include('backend.racks-shelves._form', [
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

    <div class="modal fade" id="viewRackModal" tabindex="-1" aria-labelledby="viewRackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md"> <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title fs-6 fw-bold text-dark" id="viewRackModalLabel">
                        <i class="fa-solid fa-eye me-1 text-primary"></i> {{ __('file.title.rack_details') ?? 'Rack Details' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body p-2">
                            <table class="table table-sm table-borderless mb-0 fs-7">
                                <tr>
                                    <td class="fw-bold text-muted" style="width: 35%;">{{ __('file.branch') ?? 'Branch' }}:</td>
                                    <td id="view_rack_branch" class="text-dark fw-semibold">-</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">{{ __('file.rack') }} {{ __('file.name') }}:</td>
                                    <td id="view_rack_name" class="text-dark fw-semibold">-</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">{{ __('file.rack') }} {{ __('file.code') }}:</td>
                                    <td id="view_rack_code" class="text-dark">-</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">{{ __('file.description') }}:</td>
                                    <td id="view_rack_description" class="text-muted small">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h6 class="fw-bold text-secondary mb-2" style="font-size: 0.85rem;">
                        <i class="fa-solid fa-boxes-stacked me-1 text-info"></i> {{ __('file.shelf') }} {{ __('file.list') }}
                    </h6>
                    <div class="table-responsive border rounded bg-white" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-striped table-hover mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="py-1" style="width: 60%;">{{ __('file.shelf') }} {{ __('file.name') }}</th>
                                    <th class="py-1" style="width: 40%;">{{ __('file.shelf') }} {{ __('file.code') }}</th>
                                </tr>
                            </thead>
                            <tbody id="view-shelf-rows-container">
                                </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-1 bg-light">
                    <button type="button" class="btn btn-sm btn-secondary py-1 px-3" data-bs-dismiss="modal">
                        {{ __('file.button.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('js')
    @include('backend.layouts.partials._datatable_bottom')
    <script>
        let shelfIndex = 0;
        let $container = $('#shelf-rows-container');

        $(document).ready(function() {
            handleFormSubmit('#createRackForm', '#createRackModal', '#rack-table', false);
            handleFormSubmit('#editRackForm', '#editRackModal', '#rack-table', true);

            //  Trigger 1 default row when Create Modal opens
            $('#createRackModal').on('show.bs.modal', function () {
                $container = $(this).find('#shelf-rows-container');
                $container.empty();
                shelfIndex = 0;
                $container.append(generateShelfRow()); 
                validateShelfNames($(this)); //  Reset validation state
            });

            //  Handle click event for dynamic Add Row button globally
            $(document).on('click', '.add-shelf-row-btn', function() {
                let $modal = $(this).closest('.modal');
                $container = $modal.find('#shelf-rows-container');
                $container.append(generateShelfRow());
                validateShelfNames($modal);
            });

            //  Handle dynamic row deletion globally using event delegation
            $(document).on('click', '.remove-shelf-row-btn', function() {
                let $modal = $(this).closest('.modal');
                $container = $modal.find('#shelf-rows-container');
                let currentRows = $container.find('.shelf-row').length;
                
                if (currentRows > 1 || $modal.attr('id') === 'editRackModal') {
                    $(this).closest('.shelf-row').remove();
                    validateShelfNames($modal); // 🔥  Re-validate remaining rows after removal
                } else {
                    alert('At least one shelf row must be filled.');
                }
            });

            // 🔥  Real-time validation when user types anything inside the shelf name input field
            $(document).on('input', '.shelf-name-input, .shelf-code-input', function() {
                let $modal = $(this).closest('.modal');
                validateShelfNames($modal);
            });

        });

        //  Template generator function for compact row layout with classes for both validation fields
        function generateShelfRow(id = '', name = '', code = '') {
            let rowHtml = `
                <tr class="shelf-row">
                    <td>
                        <input type="hidden" name="shelves[${shelfIndex}][id]" value="${id}">
                        <input type="text" name="shelves[${shelfIndex}][name]" class="form-control form-control-sm shelf-input-field shelf-name-input" value="${name}" placeholder="e.g., Shelf 1, Top Row" required>
                        <div class="invalid-feedback shelf-dup-error small" style="display: none;">Duplicate shelf name not allowed in the same rack.</div>
                    </td>
                    <td>
                        <input type="text" name="shelves[${shelfIndex}][code]" class="form-control form-control-sm shelf-input-field shelf-code-input" value="${code}" placeholder="e.g., SH-1">
                        <div class="invalid-feedback shelf-code-dup-error small" style="display: none;">Duplicate shelf code not allowed in the same rack.</div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm p-0 btn-link text-danger remove-shelf-row-btn" title="Delete Row" style="line-height: 1;">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
            `;
            shelfIndex++;
            return rowHtml;
        }

        // 🔥  Core validation logic to detect duplicate names AND duplicate codes inside the active modal scope
        function validateShelfNames($modal) {
            let seenNames = {};
            let seenCodes = {}; // 🔥 Track unique codes
            let hasDuplicate = false;
            let $submitBtn = $modal.find('button[type="submit"]');

            // 1. --- Validate Shelf Names ---
            $modal.find('.shelf-name-input').each(function() {
                let $input = $(this);
                let nameValue = $input.val().trim().toLowerCase();
                let $errorDiv = $input.siblings('.shelf-dup-error');

                if (nameValue === '') {
                    $input.removeClass('is-invalid');
                    $errorDiv.hide();
                    return;
                }

                if (seenNames[nameValue]) {
                    hasDuplicate = true;
                    $input.addClass('is-invalid');
                    $errorDiv.show();
                    seenNames[nameValue].addClass('is-invalid');
                    seenNames[nameValue].siblings('.shelf-dup-error').show();
                } else {
                    seenNames[nameValue] = $input;
                    $input.removeClass('is-invalid');
                    $errorDiv.hide();
                }
            });

            // 2. --- Validate Shelf Codes (Only if not empty) ---
            $modal.find('.shelf-code-input').each(function() {
                let $input = $(this);
                let codeValue = $input.val().trim().toLowerCase();
                let $errorDiv = $input.siblings('.shelf-code-dup-error');

                //  Skip validation if the user leaves the code blank (since it's optional)
                if (codeValue === '') {
                    $input.removeClass('is-invalid');
                    $errorDiv.hide();
                    return;
                }

                if (seenCodes[codeValue]) {
                    hasDuplicate = true;
                    $input.addClass('is-invalid');
                    $errorDiv.show();
                    seenCodes[codeValue].addClass('is-invalid');
                    seenCodes[codeValue].siblings('.shelf-code-dup-error').show();
                } else {
                    seenCodes[codeValue] = $input;
                    //  Only remove invalid class if it wasn't already marked invalid by something else
                    $input.removeClass('is-invalid');
                    $errorDiv.hide();
                }
            });

            //  Final call to lock or unlock the action button
            if (hasDuplicate) {
                $submitBtn.prop('disabled', true);
            } else {
                $submitBtn.prop('disabled', false);
            }
        }

        //  Map values and populate rows dynamically for edit operation
       function editRack(id) {
            let url = "{{ route('racks-shelves.edit', ':rack') }}".replace(':rack', id);

            $.get(url, function(response) {
                let rack = response.rack;
                let $modal = $('#editRackModal');
                let $form = $('#editRackForm');
                
                $form.find('#branch_id').val(rack.branch_id).trigger('change');
                $form.find('#rack_name').val(rack.name);
                $form.find('#rack_code').val(rack.code);
                $form.find('#rack_description').val(rack.description);

                let $container = $form.find('#shelf-rows-container');
                $container.empty();
                shelfIndex = 0;

                if (rack.shelves && rack.shelves.length > 0) {
                    // 🔥  Sort the shelves array by ID in ascending order before rendering rows
                    rack.shelves.sort(function(a, b) {
                        return a.id - b.id; //  Numeric comparison for sorting IDs ascending
                    });

                    rack.shelves.forEach(function(shelf) {
                        $container.append(generateShelfRow(shelf.id, shelf.name, shelf.code));
                    });
                } else {
                    $container.append(generateShelfRow());
                }

                validateShelfNames($modal); //  Initial check upon loading data into edit modal

                let updateUrl = "{{ route('racks-shelves.update', ':rack') }}".replace(':rack', id);
                $form.attr('action', updateUrl);
                $modal.modal('show');
            });
        }

        function viewRack(id) {
            let url = "{{ route('racks-shelves.view', ':rack') }}".replace(':rack', id);

            $.get(url, function(response) {
                if(response.success) {
                    let rack = response.rack;
                    let $modal = $('#viewRackModal');

                    // English Comment: Inject master text values into the read-only placeholder elements
                    $modal.find('#view_rack_branch').text(rack.branch ? rack.branch.name : '-');
                    $modal.find('#view_rack_name').text(rack.name);
                    $modal.find('#view_rack_code').text(rack.code ? rack.code : '-');
                    $modal.find('#view_rack_description').text(rack.description ? rack.description : '-');

                    // English Comment: Clear and safely rebuild child shelf table lists
                    let $shelfContainer = $modal.find('#view-shelf-rows-container');
                    $shelfContainer.empty();

                    if(rack.shelves && rack.shelves.length > 0) {
                        rack.shelves.forEach(function(shelf) {
                            $shelfContainer.append(`
                                <tr>
                                    <td class="fw-semibold text-dark py-1">${shelf.name}</td>
                                    <td class="text-muted py-1">${shelf.code ? shelf.code : '-'}</td>
                                </tr>
                            `);
                        });
                    } else {
                        $shelfContainer.append(`
                            <tr>
                                <td colspan="2" class="text-center text-muted small py-2">No shelves assigned to this rack.</td>
                            </tr>
                        `);
                    }

                    // English Comment: Finally trigger Bootstrap modal exhibition
                    $modal.modal('show');
                }
            }).fail(function() {
                alert('Failed to fetch data from server.');
            });
        }
    </script>
@endpush
