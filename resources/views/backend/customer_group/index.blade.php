@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.customer_group_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.customer_group_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.customer_group_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCustomerGroupModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.customer_group') }}</a>
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
    <!-- Create Customer Group Modal -->
    <div class="modal fade" id="createCustomerGroupModal" tabindex="-1" aria-labelledby="createCustomerGroupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCustomerGroupModalLabel">{{ __('file.title.create_customer_group') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('customer_groups.store') }}" id="createCustomerGroupForm" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('file.field.name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. VIP, Wholesale"
                                    required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('file.field.discount_type') }}</label>
                                <select name="discount_type" class="form-select">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('file.field.discount_value') }}</label>
                                <input type="number" name="discount_value" class="form-control" step="0.01"
                                    value="0.00">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('file.field.min_order_amount') }}</label>
                                <input type="number" name="min_order_amount" class="form-control" step="0.01"
                                    value="0.00">
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        id="activeCheck" checked>
                                    <label class="form-check-label" for="activeCheck">Active</label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1"
                                        id="defaultCheck">
                                    <label class="form-check-label" for="defaultCheck">Set as Default</label>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('file.button.create') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCustomerGroupModal" tabindex="-1" aria-labelledby="editCustomerGroupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerGroupModalLabel">{{ __('file.title.edit_customer_group') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="editCustomerGroupForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="customer_group_id" id="customer_group_id">

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('file.field.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('file.field.discount_type') }}</label>
                                <select name="discount_type" id="edit_discount_type" class="form-select">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('file.field.discount_value') }}</label>
                                <input type="number" name="discount_value" id="edit_discount_value"
                                    class="form-control" step="0.01">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('file.field.min_order_amount') }}</label>
                                <input type="number" name="min_order_amount" id="edit_min_order_amount"
                                    class="form-control" step="0.01">
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        id="edit_is_active">
                                    <label class="form-check-label" for="edit_is_active">Active</label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1"
                                        id="edit_is_default">
                                    <label class="form-check-label" for="edit_is_default">Set as Default</label>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('file.button.update') }}</button>
                        </div>
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
            handleFormSubmit('#createCustomerGroupForm', '#createCustomerGroupModal', '#customergroup-table',
                false);
            handleFormSubmit('#editCustomerGroupForm', '#editCustomerGroupModal', '#customergroup-table', true);
        })
        
        const editRoute = "{{ route('customer_groups.edit', ':customer_group') }}";
        function editCustomerGroup(id) {
            let url = editRoute.replace(':customer_group', id);
            $.get(url, function(response) {
                console.log(response);
                $editUrl = "{{ route('customer_groups.update', ':customer_group') }}".replace(':customer_group', id);
                $('#editCustomerGroupForm').attr('action', $editUrl);
                $('#customer_group_id').val(response.id);
                $('#edit_name').val(response.name);
                $('#edit_discount_type').val(response.discount_type).trigger('change');
                $('#edit_discount_value').val(response.discount_value);
                $('#edit_min_order_amount').val(response.min_order_amount);
                $('#edit_is_active').prop('checked', response.is_active);
                $('#edit_is_default').prop('checked', response.is_default);
                $('#editCustomerGroupModal').modal('show');
            });
        }
    </script>
@endpush
