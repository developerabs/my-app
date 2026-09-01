@extends('landlord.layouts.main')

@section('title'){{ __('file.title.general_settings') }} - SheraziPOS Landlord @endsection

@push('css')
@endpush

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h4 class="mb-0">{{ __('file.title.general_settings') }}</h4>
        <p class="mb-0 text-muted">{{ __('file.title.general_settings_desc') }}</p>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        @php $firstActiveTab = null; @endphp
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="settingsTab" role="tablist">
            @can('manage_general_setting')
                @php $firstActiveTab = $firstActiveTab ?? '#general'; @endphp
                <li class="nav-item"><button class="nav-link {{ $firstActiveTab=='#general'?'active':'' }}" data-bs-toggle="tab" data-bs-target="#general">General</button></li>
            @endcan
            @can('manage_email_setting')
                @php $firstActiveTab = $firstActiveTab ?? '#email'; @endphp
                <li class="nav-item"><button class="nav-link {{ $firstActiveTab=='#email'?'active':'' }}" data-bs-toggle="tab" data-bs-target="#email">Email</button></li>
            @endcan
            @can('manage_seo_setting')
                @php $firstActiveTab = $firstActiveTab ?? '#seo'; @endphp
                <li class="nav-item"><button class="nav-link {{ $firstActiveTab=='#seo'?'active':'' }}" data-bs-toggle="tab" data-bs-target="#seo">SEO</button></li>
            @endcan
            @can('manage_analytics_setting')
                @php $firstActiveTab = $firstActiveTab ?? '#analytics'; @endphp
                <li class="nav-item"><button class="nav-link {{ $firstActiveTab=='#analytics'?'active':'' }}" data-bs-toggle="tab" data-bs-target="#analytics">Analytics</button></li>
            @endcan
            @can('manage_ai_setting')
                @php $firstActiveTab = $firstActiveTab ?? '#ai'; @endphp
                <li class="nav-item"><button class="nav-link {{ $firstActiveTab=='#ai'?'active':'' }}" data-bs-toggle="tab" data-bs-target="#ai">AI</button></li>
            @endcan
        </ul>

        <div class="tab-content" id="settingsTabContent">

            @can('manage_general_setting')
                <div class="tab-pane fade {{ $firstActiveTab=='#general'?'show active':'' }} " id="general">
                    <form action="{{ route('landlord.general-settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.company_name') }}</label>
                                <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.site_title') }}</label>
                                <input type="text" name="site_title" value="{{ old('site_title', $settings['site_title'] ?? '') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.email') }}</label>
                                <input type="email" name="company_email" value="{{ old('company_email', $settings['company_email'] ?? '') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.phone_number') }}</label>
                                <input type="text" name="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('file.field.address') }}</label>
                                <textarea name="company_address" class="form-control">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.comapany_logo') }}</label>
                                <input type="file" name="company_logo" class="form-control"
                                    accept="image/jpg, image/jpeg, image/png"
                                    onchange="previewImage(this, 'logo_preview')">

                                @if(!empty($settings['company_logo']))
                                    <img id="logo_preview" src="{{ asset('storage/'.$settings['company_logo']) }}" class="mt-2" height="50">
                                @else
                                    <img id="logo_preview" src="{{ asset('images/preview_image.png') }}" class="mt-2" height="50">
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.favicon') }}</label>
                                <input type="file" name="favicon" class="form-control"
                                    accept="image/jpg, image/png, image/ico"
                                    onchange="previewImage(this, 'favicon_preview')">

                                @if(!empty($settings['favicon']))
                                    <img id="favicon_preview" src="{{ asset('storage/'.$settings['favicon']) }}" class="mt-2" height="50">
                                @else
                                    <img id="favicon_preview" src="{{ asset('images/preview_image.png') }}" class="mt-2" height="50">
                                @endif
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                        </div>
                    </form>
                </div>
            @endcan

            @can('manage_email_setting')
                <div class="tab-pane fade {{ $firstActiveTab=='#email'?'show active':'' }}" id="email">
                    <form action="{{ route('landlord.email-settings.update') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.mail_host') }}</label>
                                <input type="text" name="mail_host" value="{{ $settings['mail_host'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.mail_port') }}</label>
                                <input type="text" name="mail_port" value="{{ $settings['mail_port'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.username') }}</label>
                                <input type="text" name="mail_username" value="{{ $settings['mail_username'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.password') }}</label>
                                <input type="password" name="mail_password" value="{{ $settings['mail_password'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.encryption') }}</label>
                                <select name="mail_encryption" class="form-select">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.form_address') }}</label>
                                <input type="email" name="mail_from_address" value="{{ $settings['mail_from_address'] ?? '' }}" class="form-control">
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('file.button.save_email_settings') }}</button>
                        </div>
                    </form>
                </div>
            @endcan

            @can('manage_seo_setting')
                <div class="tab-pane fade {{ $firstActiveTab=='#seo'?'show active':'' }}" id="seo">
                    <form action="{{ route('landlord.seo-settings.update')}}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">{{ __('file.field.meta_title') }}</label>
                                <input type="text" name="meta_title" class="form-control" value="{{ $settings['meta_title'] ?? '' }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('file.field.meta_description') }}</label>
                                <textarea name="meta_description" class="form-control" rows="3">{{ $settings['meta_description'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('file.field.meta_keywords') }}</label>
                                <input type="text" name="meta_keywords" class="form-control" value="{{ $settings['meta_keywords'] ?? '' }}">
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('file.button.save_seo_settings') }}</button>
                        </div>
                    </form>
                </div>
            @endcan

            @can('manage_analytics_setting')
                <div class="tab-pane fade {{ $firstActiveTab=='#analytics'?'show active':'' }}" id="analytics">
                    <form action="{{ route('landlord.analytics-settings.update')}}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">{{ __('file.field.google_tag') }}</label>
                                <textarea name="google_tag" class="form-control" rows="4">{{ $settings['google_tag'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('file.field.facebook_pixel') }}</label>
                                <textarea name="facebook_pixel" class="form-control" rows="4">{{ $settings['facebook_pixel'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('file.field.chat_script') }}</label>
                                <textarea name="chat_script" class="form-control" rows="4">{{ $settings['chat_script'] ?? '' }}</textarea>
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('file.button.save_analytics') }}</button>
                        </div>
                    </form>
                </div>
            @endcan

            @can('manage_ai_setting')
                <div class="tab-pane fade {{ $firstActiveTab=='#ai'?'show active':'' }}" id="ai" style="pointer-events: none;">
                    <form action="#" method="POST" style="opacity: 0.5;">
                        @csrf
                        <p class="text-muted mb-3">{{ __('file.title.ai_settings') }}</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.ai_provider') }}</label>
                                <select name="ai_provider" class="form-select" disabled>
                                    <option value="openai">{{ __('file.option.openai') }}</option>
                                    <option value="anthropic">{{ __('file.option.anthropic') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.api_key') }}</label>
                                <input type="text" name="ai_api_key" class="form-control" value="{{ $settings['ai_api_key'] ?? '' }}" disabled>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('file.field.default_model') }}</label>
                                <input type="text" name="ai_model" class="form-control" value="{{ $settings['ai_model'] ?? 'gpt-4' }}" disabled>
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button class="btn btn-primary" disabled><i class="bi bi-save"></i> {{ __('file.button.save_ai_settings') }}</button>
                        </div>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $(document).ready(function () {
        // Persist last opened tab
        const activeTab = localStorage.getItem('activeLandlordSettingsTab');
        if (activeTab) new bootstrap.Tab(document.querySelector(`[data-bs-target="${activeTab}"]`)).show();

        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            localStorage.setItem('activeLandlordSettingsTab', $(e.target).data('bs-target'));
        });

    });

    function previewImage(input, previewId) {
        const file = input.files[0];
        if (file) {
            document.getElementById(previewId).src = URL.createObjectURL(file);
        }
    }
</script>
@endpush
