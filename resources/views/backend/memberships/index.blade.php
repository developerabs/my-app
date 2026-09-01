@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.membership_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.membership_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.membership_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMembershipModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.membership') }}</a>
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
    <!-- Create Membership Modal -->
    <div class="modal fade" id="createMembershipModal" tabindex="-1" aria-labelledby="createMembershipModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="createMembershipModalLabel">{{ __('file.title.create_membership') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('memberships.store') }}" id="createMembershipFormStop" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.name') }} <span
                                            class="text-danger">*</span></strong></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Gold Plan"
                                    required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.code') }} <span
                                            class="text-danger">*</span></strong></label>
                                <input type="text" name="code" class="form-control" placeholder="e.g. GOLD01"
                                    required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.minimum_points') }} *</strong></label>
                                <input type="number" name="minimum_points" id="minimum_points" class="form-control">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.membership_fee') }}</strong></label>
                                <input type="number" name="membership_fee" class="form-control" step="0.01"
                                    value="0.00">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.minimum_spend') }}</strong></label>
                                <input type="number" name="minimum_spend" class="form-control" step="0.01"
                                    value="0.00">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.validation_days') }}</strong></label>
                                <input type="number" name="validation_days" class="form-control" value="365">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.discount_type') }}</strong></label>
                                <select name="discount_type" class="form-select">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.discount_value') }}</strong></label>
                                <input type="number" name="discount_value" class="form-control" step="0.01"
                                    value="0.00">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.select_benefits') }}</strong></label>
                                <div class="p-3 border rounded bg-light">
                                    <div class="row">
                                        @php
                                            $available_benefits = [
                                                'free_shipping' => 'free_shipping',
                                                'double_points' => 'double_points',
                                                'priority_service' => 'priority_service',
                                                'birthday_gift' => 'birthday_gift',
                                                'cashback_eligible' => 'cashback_eligible',
                                            ];
                                        @endphp

                                        @foreach ($available_benefits as $key => $value)
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input benefit-checkbox" type="checkbox"
                                                        name="benefits[]" value="{{ $value }}"
                                                        id="b_{{ $key }}">
                                                    <label class="form-check-label" for="b_{{ $key }}">
                                                        {{ __('file.option.' . $key) }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        id="m_active" checked>
                                    <label class="form-check-label"
                                        for="m_active"><strong>{{ __('file.option.active') }}</strong></label>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('file.button.create') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editMembershipModal" tabindex="-1" aria-labelledby="editMembershipModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMembershipModalLabel">{{ __('file.title.edit_membership') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="editMembershipForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="membership_id" id="membership_id">

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.name') }} *</strong></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.code') }} *</strong></label>
                                <input type="text" name="code" id="edit_code" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.minimum_points') }}</strong></label>
                                <input type="number" name="minimum_points" id="edit_minimum_points" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.membership_fee') }}</strong></label>
                                <input type="number" name="membership_fee" id="edit_membership_fee"
                                    class="form-control" step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.minimum_spend') }}</strong></label>
                                <input type="number" name="minimum_spend" id="edit_minimum_spend" class="form-control"
                                    step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.validation_days') }}</strong></label>
                                <input type="number" name="validation_days" id="edit_validation_days"
                                    class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.discount_type') }}</strong></label>
                                <select name="discount_type" id="edit_discount_type" class="form-select">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.discount_value') }}</strong></label>
                                <input type="number" name="discount_value" id="edit_discount_value"
                                    class="form-control" step="0.01">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label"><strong>{{ __('file.field.select_benefits') }}</strong></label>
                                <div class="p-3 border rounded bg-light">
                                    <div class="row">
                                        @php
                                            $available_benefits = [
                                                'free_shipping' => 'free_shipping',
                                                'double_points' => 'double_points',
                                                'priority_service' => 'priority_service',
                                                'birthday_gift' => 'birthday_gift',
                                                'cashback_eligible' => 'cashback_eligible',
                                            ];
                                        @endphp

                                        @foreach ($available_benefits as $key => $value)
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input benefit-checkbox" type="checkbox"
                                                        name="benefits[]" value="{{ $value }}"
                                                        id="edit_b_{{ $key }}">
                                                    <label class="form-check-label" for="edit_b_{{ $key }}">
                                                        {{ __('file.option.' . $key) }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        id="edit_is_active">
                                    <label class="form-check-label"
                                        for="edit_is_active"><strong>{{ __('file.option.active') }}</strong></label>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
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
            handleFormSubmit('#createMembershipForm', '#createMembershipModal', '#membership-table',
                false);
            handleFormSubmit('#editMembershipForm', '#editMembershipModal', '#membership-table', true);
        })

        const editRoute = "{{ route('memberships.edit', ':membership') }}";

        function editMembership(id) {
            let url = editRoute.replace(':membership', id);

            $.get(url, function(data) {
                let updateUrl = "{{ route('memberships.update', ':membership') }}".replace(':membership', id);
                let $form = $('#editMembershipForm'); // ক্যাশ করে রাখা ভালো

                $form.attr('action', updateUrl);

                // সাধারণ ফিল্ডগুলো ফিল করা
                $form.find('#membership_id').val(data.id);
                $form.find('#edit_name').val(data.name);
                $form.find('#edit_code').val(data.code);
                $form.find('#edit_minimum_points').val(data.minimum_points);
                $form.find('#edit_membership_fee').val(data.membership_fee);
                $form.find('#edit_minimum_spend').val(data.minimum_spend);
                $form.find('#edit_validation_days').val(data.validation_days);
                $form.find('#edit_discount_type').val(data.discount_type);
                $form.find('#edit_discount_value').val(data.discount_value);

                // ১. বেনিফিট চেকসবক্স হ্যান্ডেল করা (শুধুমাত্র এডিট ফর্মের ভেতরে)
                $form.find('.benefit-checkbox').prop('checked', false);

                if (data.benefits && Array.isArray(data.benefits)) {
                    data.benefits.forEach(function(benefit) {
                        // শুধুমাত্র এই ফর্মের ভেতরে নির্দিষ্ট ভ্যালুর চেকসবক্স খুঁজে চেক করা
                        $form.find(`.benefit-checkbox[value="${benefit}"]`).prop('checked', true);
                    });
                }

                // ২. ইজ একটিভ সুইচ হ্যান্ডেল করা
                if (data.is_active == true || data.is_active == 1) {
                    $form.find('#edit_is_active').prop('checked', true);
                } else {
                    $form.find('#edit_is_active').prop('checked', false);
                }

                $('#editMembershipModal').modal('show');
            }).fail(function() {
                alert("Could not fetch data. Please try again.");
            });
        }
    </script>
@endpush
