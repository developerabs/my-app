@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.unit_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
    <style>
        .bg-soft-primary {
            background-color: #f0f7ff;
        }

        .border-dashed {
            border-style: dashed !important;
            border-color: #ddd !important;
        }

        .form-control,
        .form-select {
            border-radius: 6px;
        }

        .badge {
            font-family: 'Courier New', monospace;
            letter-spacing: 0.5px;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.unit_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.unit_management_desc') }}
        @endslot
        @slot('button')
            <a href="{{ route('unit-groups.index') }}" class="btn btn-primary"><i class="fa-solid fa-list me-1"></i>
                {{ __('file.button.list') }} {{ __('file.unit') }}</a>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUnitModal"><i
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
    {{-- Create Unit Modal --}}
    <div class="modal fade" id="createUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header border-0 pb-0 pt-3 px-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-gear-wide-connected me-2"></i>Unit Configuration</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 py-3">
                    <form id="createUnitFormStop" action="{{ route('units.store') }}" method="POST">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small fw-bold mb-1">Group</label>
                                <select name="group_id" class="form-select border-0 bg-light shadow-none" required>
                                    <option value="">Select Group</option>
                                    @foreach ($unitGroups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="small fw-bold mb-1">Full Name</label>
                                <input type="text" name="name" class="form-control border-0 bg-light shadow-none"
                                    placeholder="e.g. Square Feet" required>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold mb-1">Short Name</label>
                                <input type="text" name="short_name" id="shortNameInput"
                                    class="form-control border-0 bg-light shadow-none fw-bold text-primary"
                                    placeholder="Sqft" required>
                            </div>

                            <div class="col-md-4 pt-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" value="1" id="isBaseUnit" name="is_base_unit"
                                        checked>
                                    <label class="form-check-label small fw-bold ms-1" for="isBaseUnit">Is Base
                                        Unit?</label>
                                </div>
                            </div>
                            <div class="col-md-5" id="baseUnitSelectContainer" style="display: none;">
                                <label class="small fw-bold mb-1 d-block">Parent Unit</label>
                                <select name="base_unit_id"
                                    class="form-select form-select-sm border-0 bg-soft-primary shadow-none">
                                    <option value="">Select Parent...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold mb-1 d-block">Precision</label>
                                <input type="number" name="precision"
                                    class="form-control form-control-sm border-0 bg-light shadow-none" value="2">
                            </div>

                            <div class="col-12 my-1">
                                <hr class="text-muted opacity-25">
                            </div>

                            <div id="logicSection" class="col-12 mt-1" style="display: none;">
                                <div class="p-3 rounded-3 bg-white border border-dashed mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="small fw-bold text-dark mb-0">Conversion Method</label>
                                        <div class="btn-group btn-group-sm">
                                            <input type="radio" class="btn-check" name="logic_type" id="logicOp"
                                                value="operator" checked>
                                            <label class="btn btn-outline-primary px-3" for="logicOp">Simple</label>
                                            <input type="radio" class="btn-check" name="logic_type" id="logicFormula"
                                                value="formula">
                                            <label class="btn btn-outline-primary px-3" for="logicFormula">Advanced Formula
                                                ✨</label>
                                        </div>
                                    </div>

                                    <div id="operatorView" class="row g-2">
                                        <div class="col-6">
                                            <select name="operator" class="form-select form-select-sm border-0 bg-light">
                                                <option value="*">Multiply (*)</option>
                                                <option value="/">Divide (/)</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" step="any" name="operator_value"
                                                class="form-control form-control-sm border-0 bg-light" value="1.00">
                                        </div>
                                    </div>

                                    <div id="formulaView" class="d-none">
                                        <input type="text" name="formula" id="formulaInput"
                                            class="form-control border-primary bg-soft-primary font-monospace shadow-none mb-2"
                                            placeholder="e.g. (x * length * width) / 144">

                                        <div id="instructionPanel"
                                            class="p-3 rounded bg-light border-start border-primary border-4">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-info-circle-fill text-primary"></i>
                                                <span class="small fw-bold text-dark text-uppercase">Formula Guide</span>
                                            </div>
                                            <ul class="text-muted mb-2 ps-3" style="font-size: 11px; list-style: square;">
                                                <li>Use <strong>'x'</strong> to represent the Input Quantity.</li>
                                                <li>Any word (e.g., <code>length</code>, <code>width</code>) will create a
                                                    field in Product Entry.</li>
                                                <li>Supports: <code>round()</code>, <code>ceil()</code>, <code>abs()</code>,
                                                    <code>+</code>, <code>-</code>, <code>*</code>, <code>/</code>.
                                                </li>
                                            </ul>
                                            <div class="pt-2 border-top">
                                                <span class="small fw-bold text-primary">Detected Variables:</span>
                                                <div id="varBadges" class="d-flex flex-wrap gap-1 mt-1">
                                                    <em class="text-muted small">None detected yet</em>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-1">
                                <div class="p-3 rounded-3 border bg-light shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                        <h6 class="small fw-bold text-uppercase mb-0 text-muted" style="font-size: 10px;">
                                            Invoice Display Hierarchy</h6>
                                        <button type="button" id="addRow" class="btn btn-sm btn-dark shadow-sm">+ Add
                                            Higher Level</button>
                                    </div>

                                    <div id="displayRowContainer"></div>

                                    <div class="row g-2 align-items-center mt-2 pt-2 border-top border-2 border-white">
                                        <div class="col-5 text-end pe-2">
                                            <span class="small text-muted italic">Smallest Unit (Fixed)</span>
                                        </div>
                                        <div class="col-6">
                                            <span id="currentUnitPreview"
                                                class="fw-bold text-primary px-3 py-1 bg-white rounded border shadow-sm">New
                                                Unit</span>
                                        </div>
                                        <div class="col-1 text-center"><i
                                                class="bi bi-lock-fill text-muted opacity-50"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-4 py-2 fw-bold shadow-sm">Save
                            Configuration</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Unit Modal --}}
<div class="modal fade" id="editUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0 pb-0 pt-3 px-4">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Unit Configuration</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <form id="editUnitForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="unit_id" id="edit_unit_id">
                    
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="small fw-bold mb-1">Group</label>
                            <select name="group_id" id="edit_group_id" class="form-select border-0 bg-light shadow-none" required>
                                <option value="">Select Group</option>
                                @foreach ($unitGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="small fw-bold mb-1">Full Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control border-0 bg-light shadow-none" required>
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1">Short Name</label>
                            <input type="text" name="short_name" id="edit_short_name" class="form-control border-0 bg-light shadow-none fw-bold text-primary" required>
                        </div>

                        <div class="col-md-4 pt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" value="1" id="edit_isBaseUnit" name="is_base_unit">
                                <label class="form-check-label small fw-bold ms-1" for="edit_isBaseUnit">Is Base Unit?</label>
                            </div>
                        </div>
                        <div class="col-md-5" id="edit_baseUnitSelectContainer">
                            <label class="small fw-bold mb-1 d-block">Parent Unit</label>
                            <select name="base_unit_id" id="edit_base_unit_id" class="form-select form-select-sm border-0 bg-soft-primary shadow-none">
                                <option value="">Select Parent...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1 d-block">Precision</label>
                            <input type="number" name="precision" id="edit_precision" class="form-control form-control-sm border-0 bg-light shadow-none">
                        </div>

                        <div id="edit_logicSection" class="col-12 mt-1">
                            <div class="p-3 rounded-3 bg-white border border-dashed mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="small fw-bold text-dark mb-0">Conversion Method</label>
                                    <div class="btn-group btn-group-sm">
                                        <input type="radio" class="btn-check" name="logic_type" id="edit_logicOp" value="operator">
                                        <label class="btn btn-outline-primary px-3" for="edit_logicOp">Simple</label>
                                        <input type="radio" class="btn-check" name="logic_type" id="edit_logicFormula" value="formula">
                                        <label class="btn btn-outline-primary px-3" for="edit_logicFormula">Advanced Formula</label>
                                    </div>
                                </div>

                                <div id="edit_operatorView" class="row g-2">
                                    <div class="col-6">
                                        <select name="operator" id="edit_operator" class="form-select form-select-sm border-0 bg-light">
                                            <option value="*">Multiply (*)</option>
                                            <option value="/">Divide (/)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <input type="number" step="any" name="operator_value" id="edit_operator_value" class="form-control form-control-sm border-0 bg-light">
                                    </div>
                                </div>

                                <div id="edit_formulaView" class="d-none">
                                    <input type="text" name="formula" id="edit_formulaInput" class="form-control border-primary bg-soft-primary font-monospace shadow-none mb-2" placeholder="e.g. (x * length * width) / 144">
                                    <div id="edit_varBadges" class="d-flex flex-wrap gap-1 mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mt-1">
                            <div class="p-3 rounded-3 border bg-light shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                    <h6 class="small fw-bold text-uppercase mb-0 text-muted" style="font-size: 10px;">Invoice Display Hierarchy</h6>
                                    <button type="button" id="edit_addRow" class="btn btn-sm btn-dark shadow-sm">+ Add Higher Level</button>
                                </div>
                                <div id="edit_displayRowContainer"></div>
                                <div class="row g-2 align-items-center mt-2 pt-2 border-top border-2 border-white">
                                    <div class="col-5 text-end pe-2">
                                        <span class="small text-muted italic">Smallest Unit (Fixed)</span>
                                    </div>
                                    <div class="col-6">
                                        <span id="edit_currentUnitPreview" class="fw-bold text-primary px-3 py-1 bg-white rounded border shadow-sm">Unit</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mt-4 py-2 fw-bold shadow-sm">Update Configuration</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom') b
    <script>
        $(document).ready(function() {

            handleFormSubmit('#createUnitForm', '#createUnitModal', '#unit-table', false, resetUnitFormToDefault);
            // English comment: Global variable to store units of the selected group to avoid redundant AJAX calls.
            let cachedGroupUnits = [];

            // English comment: Fetch units when the Group dropdown changes.
            $('select[name="group_id"]').on('change', function() {
                const groupId = $(this).val();
                if (!groupId) {
                    updateDropdowns([]);
                    return;
                }

                const getUrl = "{{ route('units.getUnitsByGroup', ':groupId') }}".replace(':groupId',
                    groupId);

                $.get(getUrl, function(response) {
                    if (response.success) {
                        cachedGroupUnits = response.data;
                        updateDropdowns(cachedGroupUnits);
                    }
                });
            });

            // English comment: Helper function to populate all existing unit-related dropdowns in the modal.
            function updateDropdowns(units) {
                let options = '<option value="">Select Unit...</option>';
                units.forEach(unit => {
                    options += `<option value="${unit.id}">${unit.name} (${unit.short_name})</option>`;
                });

                // Update Parent Unit and any existing hierarchy selects
                $('select[name="base_unit_id"], select[name="display_units[]"]').html(options);
            }

            // English comment: Sync Short Name with the Hierarchy Preview label.
            $('#shortNameInput').on('input', function() {
                $('#currentUnitPreview').text($(this).val() || 'New Unit');
            });

            // English comment: Show/Hide logic based on Base Unit checkbox.
            $('#isBaseUnit').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('#baseUnitSelectContainer').toggle(!isChecked);
                $('#logicSection').toggle(!isChecked);
            }).trigger('change');

            // English comment: Switch between Simple Operator and Formula view.
            $('input[name="logic_type"]').on('change', function() {
                const isFormula = (this.value === 'formula');
                $('#formulaView').toggleClass('d-none', !isFormula);
                $('#operatorView').toggleClass('d-none', isFormula);
            });

            // English comment: Live detection of formula variables and displaying them as badges.
            $('#formulaInput').on('input', function() {
                const val = $(this).val();
                const vars = [...new Set(val.match(/[a-zA-Z_][a-zA-Z0-9_]*/g))]
                    .filter(v => !['x', 'round', 'ceil', 'floor', 'abs', 'sqrt'].includes(v.toLowerCase()));

                const badgeContainer = $('#varBadges');
                if (vars.length > 0) {
                    badgeContainer.html(vars.map(v =>
                        `<span class="badge bg-primary px-2 py-1">${v}</span>`).join(' '));
                } else {
                    badgeContainer.html('<em class="text-muted small">None detected yet</em>');
                }
            });

            // English comment: Add a new row to the Display Hierarchy using cached units.
            $('#addRow').on('click', function() {
                let options = '<option value="">Select Higher Unit...</option>';
                cachedGroupUnits.forEach(unit => {
                    options +=
                        `<option value="${unit.id}">${unit.name} (${unit.short_name})</option>`;
                });

                const html = `
            <div class="row g-2 mb-2 align-items-center animate__animated animate__fadeIn">
                <div class="col-11">
                    <select name="display_units[]" class="form-select form-select-sm border-0 shadow-sm" required>
                        ${options}
                    </select>
                </div>
                <div class="col-1 text-center">
                    <button type="button" class="btn btn-sm text-danger remove-row bg-transparent border-0">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                </div>
            </div>`;
                $('#displayRowContainer').append(html);
            });

            // English comment: Remove a hierarchy level row.
            $(document).on('click', '.remove-row', function() {
                $(this).closest('.row').remove();
            });
        });


        // English comment: Function to fetch unit data and populate the edit modal.
        function editUnit(id) {
            const editUrl = "{{ route('units.edit', ':id') }}".replace(':id', id);
            const updateUrl = "{{ route('units.update', ':id') }}".replace(':id', id);

            $.get(editUrl, function(response) {
                if (response.success) {
                    const unit = response.data;
                    const form = $('#editUnitForm');
                    
                    // Set basic fields
                    form.attr('action', updateUrl);
                    $('#edit_unit_id').val(unit.id);
                    $('#edit_group_id').val(unit.group_id).trigger('change'); // Trigger change to load group units
                    $('#edit_name').val(unit.name);
                    $('#edit_short_name').val(unit.short_name);
                    $('#edit_precision').val(unit.precision);
                    $('#edit_currentUnitPreview').text(unit.short_name);

                    // Handle Base Unit Checkbox
                    const isBase = unit.is_base_unit == 1;
                    $('#edit_isBaseUnit').prop('checked', isBase);
                    $('#edit_baseUnitSelectContainer').toggle(!isBase);
                    $('#edit_logicSection').toggle(!isBase);

                    if (!isBase) {
                        // Wait a bit for cachedGroupUnits to be updated by the group_id change trigger
                        setTimeout(() => {
                            $('#edit_base_unit_id').val(unit.base_unit_id);
                            
                            if (unit.is_formulaic) {
                                $('#edit_logicFormula').prop('checked', true).trigger('change');
                                $('#edit_formulaInput').val(unit.formula).trigger('input');
                            } else {
                                $('#edit_logicOp').prop('checked', true).trigger('change');
                                $('#edit_operator').val(unit.operator);
                                $('#edit_operator_value').val(unit.operator_value);
                            }

                            // Populate Hierarchy
                            $('#edit_displayRowContainer').empty();
                            if (response.hierarchy && response.hierarchy.length > 0) {
                                response.hierarchy.forEach(hId => {
                                    addEditHierarchyRow(hId);
                                });
                            }
                        }, 500);
                    }

                    $('#editUnitModal').modal('show');
                }
            });
        }

        // English comment: Helper to add hierarchy rows in Edit Modal
        function addEditHierarchyRow(selectedId = null) {
            let options = '<option value="">Select Higher Unit...</option>';
            cachedGroupUnits.forEach(unit => {
                options += `<option value="${unit.id}" ${unit.id == selectedId ? 'selected' : ''}>${unit.name} (${unit.short_name})</option>`;
            });

            const html = `
                <div class="row g-2 mb-2 align-items-center animate__animated animate__fadeIn">
                    <div class="col-11">
                        <select name="display_units[]" class="form-select form-select-sm border-0 shadow-sm" required>
                            ${options}
                        </select>
                    </div>
                    <div class="col-1 text-center">
                        <button type="button" class="btn btn-sm text-danger remove-row bg-transparent border-0"><i class="bi bi-trash3-fill"></i></button>
                    </div>
                </div>`;
            $('#edit_displayRowContainer').append(html);
        }

        // Initialize Update Handler
        handleFormSubmit('#editUnitForm', '#editUnitModal', '#unit-table');

        // English comment: Handle toggles for Edit Modal (similar to create)
        $(document).on('change', '#edit_isBaseUnit', function() {
            const isChecked = $(this).is(':checked');
            $('#edit_baseUnitSelectContainer').toggle(!isChecked);
            $('#edit_logicSection').toggle(!isChecked);
        });

        $(document).on('change', 'input[name="logic_type"]', function() {
            if($(this).closest('#editUnitForm').length) {
                const isFormula = (this.value === 'formula');
                $('#edit_formulaView').toggleClass('d-none', !isFormula);
                $('#edit_operatorView').toggleClass('d-none', isFormula);
            }
        });

        $('#edit_addRow').on('click', function() {
            addEditHierarchyRow();
        });

        /**
         * Resets the Unit Configuration Modal to its default initial state.
         * English comment: Handles both form data reset and DOM element visibility.
         */
        function resetUnitFormToDefault() {
            const form = document.getElementById('createUnitFormStop');
            
            // 1. Reset all standard form inputs (text, select, radio, etc.)
            form.reset();

            // 2. Explicitly handle visibility of logic sections
            // Default: is_base_unit is checked, so logic and parent unit should be hidden
            document.getElementById('baseUnitSelectContainer').style.display = 'none';
            document.getElementById('logicSection').style.display = 'none';

            // 3. Reset logic type views to default ('operator' visible, 'formula' hidden)
            const operatorView = document.getElementById('operatorView');
            const formulaView = document.getElementById('formulaView');
            if (operatorView) operatorView.classList.remove('d-none');
            if (formulaView) formulaView.classList.add('d-none');

            // 4. Clear the dynamic 'Invoice Display Hierarchy' rows
            const displayRowContainer = document.getElementById('displayRowContainer');
            if (displayRowContainer) {
                displayRowContainer.innerHTML = '';
            }

            // 5. Reset preview text and formula badges
            const currentUnitPreview = document.getElementById('currentUnitPreview');
            if (currentUnitPreview) {
                currentUnitPreview.innerText = 'New Unit';
            }

            const varBadges = document.getElementById('varBadges');
            if (varBadges) {
                varBadges.innerHTML = '<em class="text-muted small">None detected yet</em>';
            }

            // 6. If you are using Select2 or any other library, trigger change
            // $(form).find('select').trigger('change');
        }
    </script>
@endpush
