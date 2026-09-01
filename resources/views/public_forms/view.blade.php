<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $form->title }} - {{ $general_settings['site_title'] ?? $general_settings['company_name'] ?? 'SheraziPOS' }}</title>
    
    <link rel="icon" href="{{ file_url($general_settings['favicon'] ?? 'backend/assets/images/brand-logos/favicon.ico') }}" type="image/x-icon">
    
    <!-- Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="{{ url('backend') }}/assets/css/icons.css" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ url('auth/assets/app.css') }}">
    
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Inter', sans-serif; }
    .bg-custom-gradient { background: linear-gradient(135deg, #00ADEE 0%, #3E458E 100%); }
    
    .form-control-pro, .form-select-pro {
        width: 100%;
        height: 34px;
        padding: 4px 10px;
        font-size: 0.8125rem;
        line-height: 1.25;
        border-radius: 0.375rem;
        border: 1px solid #CBD5E1;
        background-color: #F8FAFC;
        color: #1E293B;
        outline: none;

        -webkit-transition: border-color 0.3s ease-in-out, background-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        -moz-transition: border-color 0.3s ease-in-out, background-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        transition: border-color 0.3s ease-in-out, background-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    /* Hover Effect */
    .form-control-pro:hover, .form-select-pro:hover {
        border-color: #94A3B8;
        background-color: #FFFFFF;
    }

    /* Focus (Click) Effect */
    .form-control-pro:focus, .form-select-pro:focus {
        background-color: #FFFFFF;
        border-color: #00ADEE;
        box-shadow: 0 0 0 3px rgba(0, 173, 238, 0.25);
    }

    textarea.form-control-pro {
        height: 52px;
        resize: none;
    }
</style>
</head>
<body class="min-h-screen w-full p-3 sm:p-5 flex items-center justify-center overflow-y-auto relative bg-slate-100">

    <!-- Card Outer Border Gradient Wrapper -->
    <div class="w-full max-w-4xl p-[2px] bg-gradient-to-r from-[#00ADEE] to-[#3E458E] rounded-xl shadow-2xl z-10 my-auto">
        
        <!-- White Card Content Container -->
        <div class="bg-white w-full rounded-lg overflow-hidden flex flex-col max-h-[92vh]">
            
            <!-- Header Banner -->
            <div class="bg-custom-gradient p-4 sm:p-6 text-center relative shrink-0">
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                {{-- @dd($form) --}}
                @if($form->custom_logo != null)
                    <div class="inline-block bg-white p-2 sm:p-3 rounded-xl shadow-md mb-2 sm:mb-3">
                        <img src="{{ $form->custom_logo_url }}" 
                            alt="{{ $form->title }}" 
                            class="h-9 sm:h-12 w-auto object-contain">
                    </div>
                @elseif(!empty($general_settings['company_logo']))
                    <div class="inline-block bg-white p-2 sm:p-3 rounded-xl shadow-md mb-2 sm:mb-3">
                        <img src="{{ file_url($general_settings['company_logo']) }}" 
                            alt="Logo" 
                            class="h-9 sm:h-12 w-auto object-contain">
                    </div>
                @endif
                
                <h1 class="text-xl sm:text-2xl font-bold text-white tracking-wide">
                    {{ $form->title }}
                </h1>
                @if($form->subtitle)
                    <p class="text-blue-100 text-xs sm:text-sm mt-0.5">
                        {{ $form->subtitle }}
                    </p>
                @endif
            </div>

            <!-- Form Body Area (Scrollable) -->
            <form action="{{ route('public-forms.submit', [$form->slug, $token]) }}" method="POST" enctype="multipart/form-data" class="p-4 sm:p-5 flex-1 overflow-y-auto flex flex-col justify-between">
                @csrf

                <!-- Honeypot Security Field -->
                <div class="hidden" aria-hidden="true">
                    <label>Website<input name="website_hp" tabindex="-1" autocomplete="off"></label>
                </div>

                <div>
                    <!-- Compact Error Alert -->
                    @if ($errors->any())
                        <div class="mb-3 p-2 bg-red-50 border-l-4 border-red-500 rounded text-xs text-red-700 flex items-center">
                            <i class="ri-error-warning-line text-red-500 text-sm mr-2 shrink-0"></i>
                            <span><strong class="font-semibold">Validation Error:</strong> {{ implode(', ', $errors->all()) }}</span>
                        </div>
                    @endif

                    <!-- Dynamic Fields Grid Layout -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-3">
                        @foreach($fields as $field)
                            @php
                                $columnWidth = (int) ($field['column_width'] ?? 1);
                                $spanClass = $columnWidth >= 3 ? 'md:col-span-2 lg:col-span-3' : ($columnWidth === 2 ? 'md:col-span-2 lg:col-span-2' : '');
                            @endphp
                            <div class="{{ $spanClass }}">
                                <label for="field-{{ $loop->index }}" class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                    {{ $field['label'] }} @if($field['is_required'])<span class="text-red-500">*</span>@endif
                                </label>

                                @if($field['type'] === 'textarea')
                                    <textarea id="field-{{ $loop->index }}" name="{{ $field['name'] }}" placeholder="{{ $field['placeholder'] ?? '' }}" @required($field['is_required']) class="form-control-pro">{{ old($field['name']) }}</textarea>

                                @elseif($field['type'] === 'select')
                                    <select id="field-{{ $loop->index }}" name="{{ $field['name'] }}" @required($field['is_required']) class="form-select-pro">
                                        <option value="">{{ $field['placeholder'] ?? '-- Select Option --' }}</option>
                                        @foreach((array) ($field['options'] ?? []) as $option)
                                            @php($optionValue = is_array($option) ? ($option['value'] ?? '') : $option)
                                            <option value="{{ $optionValue }}" @selected(old($field['name']) == $optionValue)>
                                                {{ is_array($option) ? ($option['label'] ?? $optionValue) : $option }}
                                            </option>
                                        @endforeach
                                    </select>

                                @elseif($field['type'] === 'file')
                                    <input id="field-{{ $loop->index }}" type="file" name="{{ $field['name'] }}" @required($field['is_required']) class="form-control-pro bg-white cursor-pointer py-1 text-slate-500">

                                @else
                                    <input id="field-{{ $loop->index }}" type="{{ in_array($field['type'], ['email', 'number', 'date', 'url']) ? $field['type'] : 'text' }}" name="{{ $field['name'] }}" value="{{ old($field['name']) }}" placeholder="{{ $field['placeholder'] ?? '' }}" @required($field['is_required']) class="form-control-pro">
                                @endif

                                @error($field['name'])
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Submit Button Bar -->
                <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-center shrink-0">
                    <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-[#00ADEE] to-[#3E458E] hover:opacity-95 text-white font-semibold text-xs rounded-lg shadow-md transition-all duration-150 transform active:scale-[0.99] flex items-center justify-center gap-1.5 cursor-pointer uppercase tracking-wider">
                        <i class="ri-send-plane-fill text-sm"></i> {{ $form->submit_button_text ?? 'Submit Form' }}
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Script assets -->
    <script src="{{ url('auth/assets/app.js') }}"></script>
</body>
</html>