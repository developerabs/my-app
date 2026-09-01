@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.client_management') }} - SheraziPOS Landlord
@endsection

@push('css')
    @include('landlord.layouts.partials._datatable_top')
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{ __('file.title.client_management') }}</h4>
            <p class="mb-0 text-muted">{{ __('file.title.client_management_desc') }}</p>
        </div>
        <div>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClientModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.client') }}</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {{ $dataTable->table(['class' => 'table nowrap responsive display']) }}
        </div>
    </div>
@endsection

@section('modals')
    <!-- Create User Modal -->
    <div class="modal fade" id="createClientModal" tabindex="-1" aria-labelledby="createClientModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="createClientModalLabel">{{ __('file.title.create_client') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" method="POST" id="clientCreateForm">
                        @csrf
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label for="package_id" class="form-label">{{ __('file.package') }}</label>
                                <select name="package_id" id="package_id" class="form-select" required>
                                    <option value="" selected>{{ __('file.option.select_package') }}</option>
                                    @foreach ($packages as $package)
                                        <option value="{{ $package->id }}">{{ $package->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="subscription_type"
                                    class="form-label">{{ __('file.field.subscription_type') }}</label>
                                <select name="subscription_type" id="subscription_type" class="form-select">

                                </select>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label for="business_name" class="form-label">{{ __('file.field.business_name') }}</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">{{ __('file.field.email') }}</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label for="username" class="form-label">{{ __('file.field.username') }}</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">{{ __('file.field.phone_number') }}</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label for="password" class="form-label">{{ __('file.field.password') }}</label>
                                <div style="position: relative;">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <span onclick="togglePassword('#password', '#password-icon')"
                                        style="position: absolute; right:10px; top:50%; transform: translateY(-50%); cursor: pointer;">
                                        <i id="password-icon" class="fa-solid fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation"
                                    class="form-label">{{ __('file.field.password_confirmation') }}</label>
                                <div style="position: relative;">
                                    <input type="password" class="form-control" id="password_confirmation"
                                        name="password_confirmation" required>
                                    <span onclick="togglePassword('#password_confirmation', '#password-icon-confirm')"
                                        style="position: absolute; right:10px; top:50%; transform: translateY(-50%); cursor: pointer;">
                                        <i id="password-icon-confirm" class="fa-solid fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label for="tenant" class="form-label">{{ __('file.field.tenant') }}</label>

                                <div class="d-flex">
                                    <input type="text" class="form-control" id="tenant" name="tenant" required
                                        style="width: 50%;">

                                    <input type="text" class="form-control" id="central_domain" name="central_domain"
                                        value=".{{ env('CENTRAL_DOMAIN') }}" disabled readonly style="width: 50%;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="custom_domain"
                                    class="form-label">{{ __('file.field.custom_domain') }}</label>
                                <input type="text" class="form-control" id="custom_domain" name="custom_domain">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label for="registration_fee"
                                    class="form-label">{{ __('file.field.registration_fee') }}</label>
                                <input type="number" class="form-control" id="registration_fee" name="registration_fee"
                                    required step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label for="subscription_fee"
                                    class="form-label">{{ __('file.field.subscription_fee') }}</label>
                                <input type="number" class="form-control" id="subscription_fee" name="subscription_fee"
                                    required readonly step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label for="received_payment"
                                    class="form-label">{{ __('file.field.received_payment') }}</label>
                                <input type="number" class="form-control" id="received_payment" name="received_payment"
                                    step="0.01">
                            </div>
                        </div>
                        <div class="row mb-2">
                            @if (Auth::user()->role_id == 1)
                                <div class="col-md-6">
                                    <label for="reseller" class="form-label">{{ __('file.field.reseller') }}</label>
                                    <select name="reseller_id" id="reseller" class="form-select">
                                        <option value="0" selected>{{ __('file.field.select_reseller') }}
                                        </option>
                                        @foreach ($resellers as $reseller)
                                            <option value="{{ $reseller->id }}"
                                                @if (isset($user) && $user->reseller_id == $reseller->id) selected @endif>{{ $reseller->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="reseller_id" id="reseller_id" value="{{Auth::user()->reseller_id}}">
                            @endif
                            <div class="col-md-6" id="trialSection" style="display:none;">
                                <label for="is_trial"
                                    class="form-label">{{ __('file.field.package_has_free_trial') }}</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="is_trial"
                                        name="is_trial">
                                    <label class="form-check-label" id="free_trial_label" for="is_trial">
                                        {{ __('file.field.is_trial') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-2">{{ __('file.button.create') }}
                            {{ __('file.client') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('landlord.layouts.partials._datatable_bottom')

    <script>
        $('#password, #password_confirmation').on('keyup', function() {
            if ($('#password').val() != $('#password_confirmation').val()) {
                $('#password_confirmation').addClass('is-invalid');
                $('#password_confirmation').removeClass('is-valid');
            } else {
                $('#password_confirmation').addClass('is-valid');
                $('#password_confirmation').removeClass('is-invalid');
            }
        });

        $('#clientForm').on('submit', function(e) {
            if ($('#password').val() != $('#password_confirmation').val()) {
                e.preventDefault();
                $('#password_confirmation').focus();
                $('#password_confirmation').addClass('is-invalid');
                $('#password_confirmation').removeClass('is-valid');
            } else {
                $('#clientForm').submit();
            }
        });

        deleteClient = (id) => {
            $('#deleteConfirmModal').modal('show');
            let deleteButton = $('#deleteConfirm');

            deleteButton.off('click').on('click', function() {
                let url = '{{ route('landlord.clients.destroy', ['id' => ':id']) }}';
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
                            "{{ __('file.message.client_deleted_successfully') }}");
                        deleteButton.prop('disabled', false).text(
                            "{{ __('file.button.delete') }}");
                        $('#client-table').DataTable().ajax.reload();
                    },
                    error: function() {
                        deleteButton.prop('disabled', false).text(
                            "{{ __('file.button.delete') }}");
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('error',
                            "{{ __('file.message.something_went_wrong') }}");
                    }
                });
            });
        }

        $(document).ready(function() {

            function resetPricing() {
                $('#subscription_type').html('');
                $('#subscription_fee').val('');
                $('#registration_fee').val('');
                $('#registration_fee').attr('min', 0);
            }

            function hideTrial() {
                $("#trialSection").hide();
                $("#is_trial").prop("checked", false).prop("disabled", true);
                $("#free_trial_label").text("Free Trial");
            }

            function showTrial(period) {
                $("#trialSection").show();
                $("#is_trial").prop("disabled", false);
                $("#free_trial_label").text(`Free Trial (${period} days)`);
            }

            $('#package_id').on('change', function() {

                let packageId = $(this).val();
                resetPricing();
                hideTrial();

                const resellerType = '{{ Auth::user()->reseller->type ?? "internal" }}';
                if (!packageId) return;

                let url = '{{ route('landlord.getPackageInfo', ['package' => ':id']) }}';
                url = url.replace(':id', packageId);

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        //console.log(response);

                        // =========== PRICING ===============
                        let pricing = response.pricing;
                        let typeOptions = ``;

                        for (let [type, data] of Object.entries(pricing)) {
                            if (data) { // ensure data exists
                                // type → monthly/yearly/lifetime
                                // data.price → price
                                let label = type.charAt(0).toUpperCase() + type.slice(
                                1); // Capitalize
                                typeOptions +=
                                    `<option value="${type}" data-price="${data.price}">${label}</option>`;
                            }
                        }

                        $('#subscription_type').html(typeOptions);

                        // =========== REGISTRATION FEE ===============
                        $('#registration_fee').val(response.min_reg_fee);
                        if(resellerType == 'external') {
                            $('#registration_fee').attr('min', response.min_reg_fee);
                        }

                        // প্রথম অপশনের price auto set
                        let firstOption = $('#subscription_type option:first');
                        $('#subscription_fee').val(firstOption.data('price') ?? '');

                        // ======== TRIAL =========
                        if (response.is_trial === true) {
                            showTrial(response.trial_period);
                        } else {
                            hideTrial();
                        }
                    }
                });
            });

            // 🔥 subscription_type change → pricing auto set
            $('#subscription_type').on('change', function() {
                let price = $(this).find(':selected').data('price');
                $('#subscription_fee').val(price);
            });
        });

        $('#clientCreateForm').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let url = "{{ route('landlord.clients.store') }}";
            let data = form.serialize();

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text(
                        "{{ __('file.button.saving') }}...");
                },
                success: function(response) {
                    $('#createClientModal').modal('hide');
                    form[0].reset();
                    showFloatingAlert('success', response.message ||
                        "{{ __('file.message.client_added_successfully') }}");
                    $('#client-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON?.errors;
                    let errorMsg = 'Something went wrong!';
                    if (errors) {
                        errorMsg = Object.values(errors).map(e => e.join(', ')).join('\n');
                    }
                    showFloatingAlert('error', errorMsg);
                },
                complete: function() {
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.create') }} {{ __('file.client') }}");
                }
            });
        });
    </script>
@endpush
