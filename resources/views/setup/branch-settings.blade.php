@extends('layouts.setup')

@push('css')
    <style>
        /* Main page wrapper */
        html,
        body {
            height: 100%;
            margin: 0;
            background: #f4f7fb;
        }

        .setup-page {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* Reduced height slightly to make it compact */
        .setup-card {
            border: 0;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 18px 50px rgba(0, 0, 0, .08);
            width: 100%;
            max-width: 1000px;
            height: auto;
            min-height: 550px;
        }

        .left-panel {
            padding: 30px;
            background: #fff;
        }

        .right-panel {
            background: linear-gradient(135deg, #0d6efd 0%, #4d84ff 100%);
            color: #fff;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .right-panel p {
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Keep all original elements */
        .logo-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .15);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo-circle i {
            font-size: 2rem;
        }

        .progress {
            height: 8px;
            border-radius: 30px;
        }

        .setup-steps {
            margin-top: 20px;
            flex: 0 1 auto;
        }

        .setup-step {
            display: flex;
            gap: 15px;
            position: relative;
            padding-bottom: 15px;
        }

        .setup-step:not(:last-child)::after {
            content: "";
            position: absolute;
            left: 15px;
            top: 20px;
            width: 2px;
            height: calc(100% - 15px);
            background: #d8dde7;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
            color: #000000;
            flex-shrink: 0;
            z-index: 2;
        }

        .step-content h6 {
            margin-bottom: 2px;
            font-weight: 700;
            font-size: 0.9rem;
            text-align: left;
        }

        .step-content p {
            margin: 0;
            color: #ffffff;
            font-size: 0.8rem;
            text-align: left;
        }

        .setup-step.active .step-icon {
            background: #fffb2a;
        }

        .setup-step.active .step-icon i {
            color: #000000;
        }

        .setup-step.active .step-content p {
            color: #fffb2a;
        }

        .setup-step.active .step-content h6 {
            color: #fffb2a;
        }

        .setup-step.complete .step-icon {
            background: #2aff58;
        }

        .setup-step.complete .step-icon i {
            color: #000000;
        }

        .setup-step.complete .step-content p {
            color: #2aff58;
        }

        .setup-step.complete .step-content h6 {
            color: #2aff58;
        }

        .form_section {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        @media(max-width:991px) {
            .right-panel {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="setup-page">
        <div class="card setup-card">
            <div class="row g-0 h-100">
                {{-- Right Panel (As it was) --}}
                <div class="col-lg-5">
                    <div class="right-panel h-100">
                        <div class="logo-circle"><i class="bi bi-gear-wide-connected"></i></div>
                        <h4 class="fw-bold">ERP Initial Setup</h4>
                        <p class="opacity-75 mt-2">This wizard will guide you through the initial configuration of your ERP
                            system.</p>
                        <div class="w-100">
                            <div class="d-flex justify-content-between mb-1 small fw-semibold"><span>Setup Progress</span>
                                <span>75%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width:75%; background-color: #91ff2a"></div>
                            </div>
                        </div>
                        <div class="setup-steps">
                            <div class="setup-step complete">
                                <div class="step-icon"><i class="bi bi-check"></i></div>
                                <div class="step-content">
                                    <h6>Regional Settings</h6>
                                    <p>Configure your country, timezone, language and default currency.</p>
                                </div>
                            </div>
                            <div class="setup-step complete">
                                <div class="step-icon"><i class="bi bi-check"></i></div>
                                <div class="step-content">
                                    <h6>Accounting Settings</h6>
                                    <p>Configure accounting preferences, numbering and financial options.</p>
                                </div>
                            </div>
                            <div class="setup-step active">
                                <div class="step-icon"><i class="bi bi-diagram-3"></i></div>
                                <div class="step-content">
                                    <h6>Branch Settings</h6>
                                    <p>Create your primary business branch.</p>
                                </div>
                            </div>
                            <div class="setup-step">
                                <div class="step-icon"><i class="bi bi-check2-circle"></i></div>
                                <div class="step-content">
                                    <h6>Complete Setup</h6>
                                    <p>Finish configuration and start using ERP.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Left Panel (As it was) --}}
                <div class="col-lg-7">
                    <div class="left-panel h-100">
                        <form action="{{ route('setup.branch.store') }}" method="POST" class="mt-auto pt-3 form_section">
                            <div>
                                <h4 class="fw-bold">Branch Settings</h4>
                            </div>
                            @csrf
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <label for="branch_name" class="form-label">Branch Name</label>
                                    <input type="text" name="branch_name" id="branch_name" class="form-control"
                                        placeholder="E.g. Main Branch">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="branch_code" class="form-label">Branch Code</label>
                                    <input type="text" name="branch_code" id="branch_code" class="form-control"
                                        placeholder="E.g. B001">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="bin_number" class="form-label">BIN Number</label>
                                    <input type="text" name="bin_number" id="bin_number" class="form-control"
                                        placeholder="E.g. 12345678">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label for="street" class="form-label">Street</label>
                                    <input type="text" class="form-control" id="street" name="address[street_address]"
                                        placeholder="E.g. 123 Main Street">
                                </div>
                                @if (($countryName ?? 'Bangladesh') === 'Bangladesh')
                                    <div class="col-md-6 mb-2">
                                        <label for="division" class="form-label">Select Division</label>
                                        <select name="address[division]" id="division" class="form-control"
                                            onchange="getDistrict();">
                                            @include('setup._address_options', [
                                                'items' => $divisions,
                                                'itemName' => 'Division',
                                                'selected' => 'Dhaka',
                                            ])
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="district" class="form-label">Select District</label>
                                        <select name="address[district]" id="district" class="form-control"
                                            onchange="getUpazilla();">
                                            <option value="">Select District</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="upazilla" class="form-label">Select Upazilla</label>
                                        <select name="address[upazilla]" id="upazilla" class="form-control"
                                            onchange="getUnion();">
                                            <option value="">Select Upazilla</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="union" class="form-label">Select Union</label>
                                        <select name="address[union]" id="union" class="form-control">
                                            <option value="">Select Union</option>
                                        </select>
                                    </div>
                                @else
                                    <div class="col-md-6 mb-2">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="address[city]"
                                            placeholder="E.g. Miami">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="state" class="form-label">State</label>
                                        <input type="text" class="form-control" id="state" name="address[state]"
                                            placeholder="E.g. Florida">
                                    </div>
                                @endif
                                <div class="col-md-6 mb-2">
                                    <label for="zipcode" class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" id="zipcode" name="address[zipcode]"
                                        placeholder="E.g. 33101">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-2">Save & Next <i
                                    class="bi bi-arrow-right-circle me-2"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#division').on('chnage', function() {
                getDistrict();
            })
            $('#division').trigger('change');


        });

        function getDistrict() {
            $('#district').empty();
            $('#upazilla').empty();
            $('#upazilla').append('<option value="">Select Upazilla</option>');
            $('#union').empty();
            $('#union').append('<option value="">Select Union</option>');
            const val = $('#division').find(':selected').val();
            const divId = val.split('-')[0];
            const url = "{{ route('setup.getDistricts', ':id') }}".replace(':id', divId);
            $.get(url, function(response) {
                $('#district').append(response);
            });
        }

        function getUpazilla() {
            $('#upazilla').empty();
            $('#union').empty();
            $('#union').append('<option value="">Select Union</option>');
            const val = $('#district').find(':selected').val();
            const distId = val.split('-')[0];
            const url = "{{ route('setup.getUpazillas', ':id') }}".replace(':id', distId);
            $.get(url, function(response) {
                $('#upazilla').append(response);
            });
        }

        function getUnion() {
            $('#union').empty();
            const val = $('#upazilla').find(':selected').val();
            const upazillaId = val.split('-')[0];
            const url = "{{ route('setup.getUnions', ':id') }}".replace(':id', upazillaId);
            $.get(url, function(response) {
                $('#union').append(response);
            });
        }
    </script>
@endpush
