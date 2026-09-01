@extends('layouts.setup')

@push('css')
    <style>
        /* Main page wrapper */
        html, body {
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
            display: flex;
            flex-direction: column;
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

        /* Keep all original elements */
        .logo-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .15);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo-circle i { font-size: 2rem; }

        .progress { height: 8px; border-radius: 30px; }

        .setup-steps { margin-top: 20px; flex: 0 1 auto; }

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
            background: #edf4ff;
            flex-shrink: 0;
            z-index: 2;
        }

        .step-content h6 { margin-bottom: 2px; font-weight: 700; font-size: 0.9rem; color: #198754; }
        .step-content p { margin: 0; color: #6c757d; font-size: 0.8rem; }

        .setup-step.active .step-icon { background: #198754; }
        .setup-step.active .step-icon i { color: #fff; }

        .summary-box {
            width: 100%;
            background: rgba(255, 255, 255, .12);
            border-radius: 16px;
            padding: 15px;
            margin-top: 25px;
        }

        .summary-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.85rem; }

        @media(max-width:991px) {
            .right-panel { display: none; }
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
                        <h3 class="fw-bold">ERP Initial Setup</h3>
                        <p class="opacity-75 mt-2">This wizard will guide you through the initial configuration of your ERP system.</p>
                        <div class="summary-box">
                            <div class="summary-item"><span>Total Steps</span> <strong>5</strong></div>
                            <div class="summary-item"><span>Current Step</span> <strong>Complete Setup</strong></div>
                            <div class="summary-item"><span>Completion</span> <strong>100%</strong></div>
                        </div>
                    </div>
                </div>
                {{-- Left Panel (As it was) --}}
                <div class="col-lg-7">
                    <div class="left-panel h-100">
                        <div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="fw-bold mb-1">Almost Done 👋</h2>
                                    <div class="text-muted">{{ ucfirst(tenant()->id) }}</div>
                                </div>
                                <i class="bi bi-building-check display-6 text-success"></i>
                            </div>
                            <p class="text-muted mt-2 mb-3">Well done! you have been complete your initial setup, now you can visit dashboard and setup your opening balance to start your Accounting</p>
                            <div>
                                <div class="d-flex justify-content-between mb-1 small fw-semibold"><span>Setup Progress</span> <span>100%</span></div>
                                <div class="progress"><div class="progress-bar bg-success" style="width:100%"></div></div>
                            </div>
                        </div>

                        <div class="setup-steps">
                            <div class="setup-step active">
                                <div class="step-icon"><i class="bi bi-check"></i></div>
                                <div class="step-content">
                                    <h6>Regional Settings</h6>
                                    <p>Configure your country, timezone, language and default currency.</p>
                                </div>
                            </div>
                            <div class="setup-step active">
                                <div class="step-icon"><i class="bi bi-calculator"></i></div>
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
                            <div class="setup-step active">
                                <div class="step-icon"><i class="bi bi-check2-circle"></i></div>
                                <div class="step-content">
                                    <h6>Complete Setup</h6>
                                    <p>Finish configuration and start using ERP.</p>
                                </div>
                            </div>
                        </div>
                        
                        <form action="{{ route('setup.complete.store') }}" method="POST" class="mt-auto pt-3">
                            @csrf
                            <button class="btn btn-success w-100"><i class="bi bi-arrow-right-circle me-2"></i> Visit Dashboard</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection