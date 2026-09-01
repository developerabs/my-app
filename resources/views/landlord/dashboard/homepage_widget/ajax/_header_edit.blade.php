<form action="{{ route('landlord.update-widget', $widget->id) }}" method="POST" id="editWidgetForm"
      enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    {{-- ================================
        LOGO SETTINGS
    ================================= --}}
    <h5 class="mt-2 mb-2 fw-bold">{{ __('file.title.logo_setting') }}</h5>
    <div class="border rounded p-3 mb-3">
        <div class="row d-none">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.title') }}</label>
                <input type="text" class="form-control shadow-sm" name="title"
                    value="{{ old('title', $widget->title) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.short_order') }}</label>
                <input type="number" class="form-control shadow-sm" name="sort_order"
                    value="{{ old('sort_order', $widget->sort_order) }}" required>
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.subtitle') }}</label>
                <input type="text" class="form-control shadow-sm" name="subtitle"
                    value="{{ old('subtitle', $widget->subtitle) }}">
            </div>
        </div>
        <div class="row">
            {{-- Logo Type --}}
            <div class="col-md-3 mb-3">
                <label class="form-label fw-medium">{{ __('file.title.logo_type') }}</label>
                <select name="header[logo_type]" class="form-select shadow-sm" id="logoType">
                    <option value="site" {{ ($widget->content['logo_type'] ?? '') == 'site' ? 'selected' : '' }}>{{ __('file.option.site_logo') }}</option>
                    <option value="custom" {{ ($widget->content['logo_type'] ?? '') == 'custom' ? 'selected' : '' }}>{{ __('file.option.custom_logo') }}</option>
                </select>
            </div>

            {{-- Custom Logo Upload --}}
            <div class="col-md-9 mb-3 logo-upload-area d-none">
                <label class="form-label fw-medium">{{ __('file.field.upload_custom_logo') }}</label>
                <div class="d-flex align-items-start">
                    <input type="file" name="header[custom_logo]" class="form-control shadow-sm"
                           accept="image/*" onchange="previewImage(this, 'customLogoPreview')" style="width: calc(100% - 210px);">
                    <div class="ms-4">
                        <img id="customLogoPreview"
                             src="{{ !empty($widget->content['custom_logo']) ? asset('storage/'.$widget->content['custom_logo']) : asset('images/preview_image.png') }}"
                             class="img-thumbnail rounded" style="max-height: 100px;">
                    </div>
                    <input type="hidden" name="header[existing_custom_logo]" value="{{ $widget->content['custom_logo'] ?? '' }}">
                </div>
            </div>

        </div>

        {{-- TITLE TOGGLE --}}
        <div class="row mt-3">
            <div class="col-md-3 mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="showTitle" name="header[show_title]"
                        {{ ($widget->content['show_title'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ __('file.field.show_app_title') }}</label>
                </div>
            </div>
        </div>
    </div>


    {{-- ================================
        TOPBAR MENU BUILDER
    ================================= --}}
    <h5 class="mt-2 mb-2 fw-bold">{{ __('file.title.topbar_menu') }}</h5>
    <div class="border rounded p-3 mb-3">

        {{-- Menu Alignment --}}
        <div class="row align-items-end">
            <div class="col-md-3 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.menu_text_color') }}</label>
                <input type="color" name="header[menu_text_color]" class="form-control form-control-color shadow-sm"
                    value="{{ $widget->content['menu_text_color'] ?? '#000000' }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.menu_hover_text_color') }}</label>
                <input type="color" name="header[menu_hover_text_color]" class="form-control form-control-color shadow-sm"
                    value="{{ $widget->content['menu_hover_text_color'] ?? '#ffffff' }}">
            </div>
            <div class="col-md-3 mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="showLanguageSwitcher" value="1" name="header[show_language_switcher]"
                        {{ ($widget->content['show_language_switcher'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ __('file.field.show_language_switcher') }}</label>
                </div>
            </div>
        </div>

        <hr>

        {{-- MENU REPEATER --}}
        <h6 class="fw-bold">{{ __('file.title.menu_items') }}</h6>

        <div id="menuRepeater">
            @php
                $menus = $widget->content['menus'] ?? [];
            @endphp

            @foreach($menus as $index => $menu)
                <div class="menu-item border rounded p-2 mb-3" draggable="false">
                    <div class="row alighn-items-center">
                        <div class="col-md-1 d-flex align-items-center justify-content-center">
                            <span class="drag-handle" style="cursor: grab;">
                                <i style="font-size:20px;" class="fa fa-grip-vertical" aria-hidden="true"></i>
                            </span>
                        </div>
                        <div class="col-md-3 d-flex align-items-center">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.label') }}</label>
                            <input type="text" name="header[menus][{{ $index }}][label]" class="form-control shadow-sm"
                                   value="{{ $menu['label'] ?? '' }}" required>
                        </div>

                        <div class="col-md-3 d-flex align-items-center">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.type') }}</label>
                            <select class="form-select shadow-sm menu-type" name="header[menus][{{ $index }}][type]">
                                <option value="page"   {{ ($menu['type'] ?? '') == 'page' ? 'selected' : '' }}>{{ __('file.option.page') }}</option>
                                <option value="page_widget" {{ ($menu['type'] ?? '') == 'page_widget' ? 'selected' : '' }}>{{ __('file.option.page_widget') }}</option>
                                <option value="custom" {{ ($menu['type'] ?? '') == 'custom' ? 'selected' : '' }}>{{ __('file.option.custom_URL') }}</option>
                            </select>
                        </div>

                        {{-- PAGE SELECT --}}
                        <div class="col-md-3 d-flex align-items-center menu-page">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.select_page') }}</label>
                            <select name="header[menus][{{ $index }}][page]" class="form-select shadow-sm">
                                <option {{ ($menu['url'] ?? '') == url('/') ? 'selected' : '' }} value="/">{{ __('file.option.homepage') }}</option>
                                @foreach($pages as $page)
                                    <option value="pages/{{ $page->slug }}" {{ ($menu['url'] ?? '') ==  url('pages/'.$page->slug) ? 'selected' : '' }}>
                                        {{ $page->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- CUSTOM URL --}}
                        <div class="col-md-3 d-flex align-items-center menu-url d-none">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.url') }}</label>
                            <input type="text" name="header[menus][{{ $index }}][url]" class="form-control shadow-sm"
                                   value="{{ $menu['url'] ?? '' }}">
                        </div>

                        {{-- widgets --}}
                        <div class="col-md-3 d-flex align-items-center menu-widgets d-none">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.widgets') }}</label>
                            <select name="header[menus][{{ $index }}][widget]" class="form-select shadow-sm">
                                @foreach($widgetList as $item)
                                    <option value="{{ Str::slug($item->title) }}" {{ ($menu['url'] ?? '') == Str::slug($item->title) ? 'selected' : '' }}>
                                        {{ $item->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" onclick="$(this).closest('.menu-item').remove()" class="btn btn-danger w-100">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-primary mt-2" id="addMenuBtn">
           {{ __('file.button.add_menu_item') }}
        </button>
    </div>

    <div class="row mt-3 align-items-end">
        <div class="col-md-2 mb-3">
            <label class="form-label fw-medium">{{ __('file.field.section_width') }}</label>
            <select name="header_settings[width]" class="form-select shadow-sm">
                <option value="w-full" {{ ($widget->settings['width'] ?? '') == 'w-full' ? 'selected' : '' }}>{{ __('file.option.full_width') }}</option>
                <option value="container" {{ ($widget->settings['width'] ?? '') == 'container' ? 'selected' : '' }}>{{ __('file.option.container_width') }}</option>
            </select>
        </div>
        <div class="col-md-2 mb-3">
            <label class="form-label fw-medium">{{ __('file.field.position') }}</label>
            <select name="header_settings[position]" class="form-select shadow-sm">
                <option value="fixed" {{ ($widget->settings['position'] ?? '') == 'fixed' ? 'selected' : '' }}>{{ __('file.option.fixed') }}</option>
                <option value="sticky" {{ ($widget->settings['position'] ?? '') == 'sticky' ? 'selected' : '' }}>{{ __('file.option.sticky') }}</option>
                <option value="relative" {{ ($widget->settings['position'] ?? '') == 'relative' ? 'selected' : '' }}>{{ __('file.option.relative') }}</option>
            </select>
        </div>
        <div class="col-md-2 mb-3">
            <label class="form-label fw-medium">{{ __('file.field.background_type') }}</label>
            <select name="header_settings[background_type]" class="form-select shadow-sm">
                <option value="transparent" {{ ($widget->settings['background_type'] ?? '') == 'transparent' ? 'selected' : '' }}>{{ __('file.option.transparent') }}</option>
                <option value="color" {{ ($widget->settings['background_type'] ?? '') == 'color' ? 'selected' : '' }}>{{ __('file.option.color') }}</option>
            </select>
        </div>
        <div class="col-md-2 mb-3">
            <label class="form-label fw-medium">{{ __('file.field.background_color') }}</label>
            <input type="color" name="header_settings[background_color]" class="form-control form-control-color shadow-sm"
                value="{{ $widget->settings['background_color'] ?? '#ffffff' }}">
        </div>
        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="header_settings[bottom_border]" value="1" {{ old('header_settings.bottom_border', $widget->settings['bottom_border'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.bottom_border') }}</label>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="header_settings[shadow]" value="1" {{ old('header_settings.shadow', $widget->settings['shadow'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.shadow') }}</label>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="is_enabled" value="1" {{ old('is_enabled', $widget->is_enabled) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.enable_section') }}</label>
            </div>
        </div>
    </div>

    {{-- SAVE BUTTON --}}
    <div class="d-flex justify-content-between mt-4">
        <button type="submit" class="btn btn-info px-4">
            <i class="fa-solid fa-save me-1"></i> {{ __('file.button.update_header') }}
        </button>
    </div>
</form>



{{-- ================================
        JS SECTION
================================= --}}

<script>
$(document).ready(function() {

    // Logo Type Toggle
    function toggleLogoUpload() {
        if ($('#logoType').val() === 'custom') {
            $('.logo-upload-area').removeClass('d-none');
        } else {
            $('.logo-upload-area').addClass('d-none');
        }
    }
    toggleLogoUpload();
    $('#logoType').on('change', toggleLogoUpload);


    // Title Toggle
    // function toggleTitleField() {
    //     if ($('#showTitle').is(':checked')) {
    //         $('.title-field').removeClass('d-none');
    //     } else {
    //         $('.title-field').addClass('d-none');
    //     }
    // }
    // toggleTitleField();
    // $('#showTitle').on('change', toggleTitleField);


    // Menu Type Toggle
    function toggleMenuType(el) {
        const val = el.val();
        const container = el.closest('.menu-item');

        if (val === 'page') {
            container.find('.menu-page').removeClass('d-none');
            container.find('.menu-url').addClass('d-none');
            container.find('.menu-widgets').addClass('d-none');
        } else if (val === 'page_widget') {
            container.find('.menu-widgets').removeClass('d-none');
            container.find('.menu-page').addClass('d-none');
            container.find('.menu-url').addClass('d-none');
        }else {
            container.find('.menu-page').addClass('d-none');
            container.find('.menu-url').removeClass('d-none');
            container.find('.menu-widgets').addClass('d-none');
        }
    }
    $('.menu-type').each(function(){ toggleMenuType($(this)); });
    $(document).on('change', '.menu-type', function() { toggleMenuType($(this)); });


    // Add Menu Item
    let menuIndex = {{ count($menus) }};
    $('#addMenuBtn').click(function() {

        const html = `
            <div class="menu-item border rounded p-2 mb-3">
                <div class="row align-items-center">
                    <div class="col-md-1 d-flex align-items-center justify-content-center">
                        <span class="drag-handle" style="cursor: grab;">
                            <i style="font-size: 20px;" class="fa-solid fa-grip-vertical"></i>
                        </span>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.label') }}</label>
                        <input type="text" name="header[menus][${menuIndex}][label]" class="form-control shadow-sm" required>
                    </div>

                    <div class="col-md-3 d-flex align-items-center">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.type') }}</label>
                        <select class="form-select shadow-sm menu-type" name="header[menus][${menuIndex}][type]">
                            <option value="page">{{ __('file.option.page') }}</option>
                            <option value="page_widget">{{ __('file.option.page_widget') }}</option>
                            <option value="custom">{{ __('file.option.custom_URL') }}</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-center menu-page">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.select_page') }}</label>
                        <select name="header[menus][${menuIndex}][page]" class="form-select shadow-sm">
                            <option value="/">{{ __('file.option.homepage') }}</option>
                            @foreach($pages as $page)
                                <option value="pages/{{ $page->slug }}">{{ $page->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-center menu-url d-none">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.url') }}</label>
                        <input type="text" name="header[menus][${menuIndex}][url]" class="form-control shadow-sm">
                    </div>

                    <div class="col-md-3 d-flex align-items-center menu-widgets d-none">
                        <label class="form-label mb-0 me-2 fw-medium">Select Widget</label>
                        <select name="header[menus][${menuIndex}][widget]" class="form-select shadow-sm">
                            @foreach($widgetList as $item)
                                <option value="{{ Str::slug($item->title) }}">{{ $item->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" onclick="$(this).closest('.menu-item').remove()" class="btn btn-danger w-100"><i class="fa-solid fa-trash"></i></button>
                    </div>

                </div>
            </div>
        `;

        $('#menuRepeater').append(html);
        menuIndex++;
    });




});

function previewImage(input, previewId) {
    const file = input.files[0];
    if (file) {
        document.getElementById(previewId).src = URL.createObjectURL(file);
    }
}

new Sortable(document.getElementById('menuRepeater'), {
    animation: 150,
    handle: ".drag-handle",
    ghostClass: "sortable-ghost",
    onEnd: function () {
        // sorting হলে index reassign
        $('#menuRepeater .menu-item').each(function(index) {
            $(this).find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const updated = name.replace(/header\[menus\]\[\d+\]/, `header[menus][${index}]`);
                    $(this).attr('name', updated);
                }
            });
        });
    }
});
</script>
