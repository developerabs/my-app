@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.create_proposal') }} - SheraziPOS Landlord
@endsection

@push('css')
    <style>

    </style>
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{ __('file.title.create_proposal') }}</h4>
            <p class="mb-0 text-muted">{{ __('file.title.create_proposal_desc') }}</p>
        </div>
        <div>
            <a href="{{ route('landlord.proposals') }}" class="btn btn-primary"><i class="fa-solid fa-list me-1"></i>
                {{ __('file.button.view') }} {{ __('file.proposal') }}</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('landlord.proposals.store') }}" method="POST" id="packageForm" enctype="multipart/form-data">
                        @csrf

                        @include('landlord.dashboard.proposals._form',['packages' => $packages,'proposal'=> null ])

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">💾 {{__('file.button.save')}} {{__('file.proposal')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
@endsection

@push('js')
    <script>
        $(document).ready(function () {
            $('.feature-checkbox').on('change', function () {
                let limitInput = $(this).closest('.feature-item').find('.feature-limit-input');
                if ($(this).is(':checked')) {
                    limitInput.prop('disabled', false);
                } else {
                    limitInput.prop('disabled', true).val('');
                }
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
        })
    </script>


@endpush
