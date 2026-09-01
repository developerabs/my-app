@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.edit_package') }} - SheraziPOS Landlord
@endsection

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h4 class="mb-0">{{ __('file.title.edit_package') }}</h4>
        <p class="mb-0 text-muted">{{ __('file.title.edit_package_desc') }}</p>
    </div>
    <div>
        <a href="{{ route('landlord.packages') }}" class="btn btn-primary"><i class="fa-solid fa-list me-1"></i> {{ __('file.button.view') }} {{ __('file.package') }}</a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('landlord.packages.update', $package->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    @include('landlord.dashboard.package_manager._form', ['package' => $package, 'features' => $features, 'modules' => $modules])

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4">💾 {{ __('file.button.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).ready(function () {

    initSelectAll(
        "#select_all_features",         // Select All checkbox
        ".feature-checkbox",            // Individual feature checkboxes
        ".feature-limit-input"          // Limit input selector
    );
    // Enable/Disable limit field
    $('.feature-checkbox').on('change', function () {
        let limitInput = $(this).closest('.feature-item').find('.feature-limit-input');
        if ($(this).is(':checked')) limitInput.prop('disabled', false);
        else limitInput.prop('disabled', true).val('');
    });

    toggleTrialPeriod();

    $('#is_trial').on('change', function() {
        toggleTrialPeriod();
    });

    function toggleTrialPeriod() {
        if ($('#is_trial').is(':checked')) {
            $('input[name="trial_period"]').prop('disabled', false);
        } else {
            $('input[name="trial_period"]').prop('disabled', true).val('');
        }
    }


});
</script>
@endpush
