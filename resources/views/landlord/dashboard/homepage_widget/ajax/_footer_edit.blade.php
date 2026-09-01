<form action="{{ route('landlord.update-widget', $widget->id) }}" method="POST" id="editWidgetForm"
      enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    {{-- ================================
        LOGO SETTINGS
    ================================= --}}
    <h5 class="mt-2 mb-2 fw-bold">{{ __('file.title.short_description') }}</h5>
    <div class="border rounded p-3 mb-3">
        <div class="row d-none">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.title') }}</label>
                <input type="text" class="form-control shadow-sm" name="title"
                    value="{{ old('title', $widget->title) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.sort_order') }}</label>
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
            <div class="col-md-12 mb-3">
                <label for="description" class="form-label">{{__('file.field.description')}}</label>
                <textarea class="form-control shadow-sm" name="footer[description]" id="description" rows="5">{{ old('footer[description]', $widget->content['description'] ?? '') }}</textarea>
            </div>
        </div>

        {{-- TITLE TOGGLE --}}
        <div class="row mt-3 align-items-end">
            <div class="col-md-2">
                <input type="color" class="form-control form-control-color shadow-sm" name="footer[text_color]"
                value="{{ old('footer.text_color', $widget->content['text_color'] ?? '#000000') }}">
                <label for="text_color" class="form-label">{{ __('file.field.text_color') }}</label>
            </div>
            <div class="col-md-2">
                <input type="color" class="form-control form-control-color shadow-sm" name="footer[bg_color]"
                value="{{ old('footer.bg_color', $widget->content['bg_color'] ?? '#ffffff') }}">
                <label for="bg_color" class="form-label">{{ __('file.field.background_color') }}</label>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="showTitle" name="footer[show_logo]"
                        {{ ($widget->content['show_logo'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ __('file.field.show_site_logo') }}</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="showTitle" name="footer[show_payment_gateway]"
                        {{ ($widget->content['show_payment_gateway'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ __('file.field.show_payment_gateways') }}</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="showNewsletter" name="footer[show_newsletter]"
                        {{ ($widget->content['show_newsletter'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ __('file.field.show_newsletter') }}</label>
                </div>
            </div>
        </div>
    </div>

    <h5 class="mt-2 mb-2 fw-bold">{{ __('file.title.background_settings') }}</h5>
    <div class="border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.background_type') }}</label>
                <select name="footer[bg_type]" class="form-select shadow-sm" id="bgType">
                    <option value="color" {{ ($widget->content['bg_type'] ?? '') == 'color' ? 'selected' : '' }}>{{ __('file.option.color') }}</option>
                    <option value="image" {{ ($widget->content['bg_type'] ?? '') == 'image' ? 'selected' : '' }}>{{ __('file.option.image') }}</option>
                    <option value="gradient" {{ ($widget->content['bg_type'] ?? '') == 'gradient' ? 'selected' : '' }}>{{ __('file.option.gradient') }}</option>
                </select>
            </div>
            <div class="col-md-10">
                <div class="bg-field bg-color {{ ($widget->content['bg_type'] ?? 'color') == 'color' ? '' : 'd-none' }}">
                    <label class="form-label fw-medium">{{ __('file.field.background_color') }}</label>
                    <input type="color" name="footer[bg_color]" class="form-control form-control-color shadow-sm"
                        value="{{ $widget->content['bg_color'] ?? '#ffffff' }}">
                </div>
                <div class="bg-field bg-image {{ ($widget->content['bg_type'] ?? '') == 'image' ? '' : 'd-none' }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label fw-medium">{{ __('file.field.background_image') }} </label>
                            <div class="d-flex align-items-start">
                                <input type="file" name="footer[bg_image]" class="form-control shadow-sm" onchange="previewImage(this, 'bgImagePreview')" accept="image/*">
                            </div>
                            <small class="form-text text-muted">Image size: 1920x500</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">{{ __('file.field.position') }}</label>
                            <select name="footer[bg_image_position]" class="form-select shadow-sm">
                                <option value="center" {{ ($widget->content['bg_image_position'] ?? '') == 'center' ? 'selected' : '' }}>{{ __('file.option.center') }}</option>
                                <option value="left" {{ ($widget->content['bg_image_position'] ?? '') == 'left' ? 'selected' : '' }}>{{ __('file.option.left') }}</option>
                                <option value="right" {{ ($widget->content['bg_image_position'] ?? '') == 'right' ? 'selected' : '' }}>{{ __('file.option.right') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">{{ __('file.field.repeat') }}</label>
                            <select name="footer[bg_image_repeat]" class="form-select shadow-sm">
                                <option value="no-repeat" {{ ($widget->content['bg_image_repeat'] ?? '') == 'no-repeat' ? 'selected' : '' }}>{{ __('file.option.no_repeat') }}</option>
                                <option value="repeat" {{ ($widget->content['bg_image_repeat'] ?? '') == 'repeat' ? 'selected' : '' }}>{{ __('file.option.repeat') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">{{ __('file.field.size') }}</label>
                            <select name="footer[bg_image_size]" class="form-select shadow-sm">
                                <option value="cover" {{ ($widget->content['bg_image_size'] ?? '') == 'cover' ? 'selected' : '' }}>{{ __('file.option.cover') }}</option>
                                <option value="contain" {{ ($widget->content['bg_image_size'] ?? '') == 'contain' ? 'selected' : '' }}>{{ __('file.option.contain') }}</option>
                                <option value="auto" {{ ($widget->content['bg_image_size'] ?? '') == 'auto' ? 'selected' : '' }}>{{ __('file.option.auto') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-3">
                            <img id="bgImagePreview" src="{{ $widget->content['bg_image'] ?? '' ? asset('storage/' . $widget->content['bg_image']) : asset('images/preview_image.png') }}" class="img-thumbnail rounded" style="max-height: 100px;">
                            <input type="hidden" name="footer[existing_bg_image]" value="{{ $widget->content['bg_image'] ?? '' }}">
                        </div>
                        <div class="col-md-3 mt-3">
                            <label class="form-label fw-medium">{{ __('file.field.overlay_color') }}</label>
                            <input type="color" name="footer[overlay_color]" class="form-control form-control-color shadow-sm"
                                value="{{ $widget->content['overlay_color'] ?? '#000000' }}">
                        </div>
                        <div class="col-md-3 mt-3">
                            <label class="form-label fw-medium">{{ __('file.field.overlay_opacity') }}</label>
                            <div class="input-group">
                                <input type="number" name="footer[overlay_opacity]" class="form-control shadow-sm"
                                    value="{{ $widget->content['overlay_opacity'] ?? '0.5' }}" min="0" max="1" step="0.1">
                                <span class="input-group-text">/ 1.0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row bg-field bg-gradient {{ ($widget->content['bg_type'] ?? '') == 'gradient' ? '' : 'd-none' }}">
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-medium">{{ __('file.field.gradient_start') }}</label>
                        <input type="color" name="footer[bg_gradient_start]" class="form-control form-control-color shadow-sm"
                            value="{{ $widget->content['bg_gradient_start'] ?? '#000000' }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-medium">{{ __('file.field.gradient_end') }}</label>
                        <input type="color" name="footer[bg_gradient_end]" class="form-control form-control-color shadow-sm"
                            value="{{ $widget->content['bg_gradient_end'] ?? '#ffffff' }}">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-medium">{{ __('file.field.direction') }}</label>
                        <select name="footer[bg_gradient_direction]" class="form-select shadow-sm">
                            <option value="to right" {{ ($widget->content['bg_gradient_direction'] ?? '') == 'to right' ? 'selected' : '' }}>{{ __('file.option.right') }}</option>
                            <option value="to bottom" {{ ($widget->content['bg_gradient_direction'] ?? '') == 'to bottom' ? 'selected' : '' }}>{{ __('file.option.bottom') }}</option>
                            <option value="to top right" {{ ($widget->content['bg_gradient_direction'] ?? '') == 'to top right' ? 'selected' : '' }}>{{ __('file.option.diagonal') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ================================
        TOPBAR MENU BUILDER
    ================================= --}}
    <h6 class="mt-2 mb-2 fw-bold">{{ __('file.title.social_links') }}</h6>
    <div class="border rounded p-3 mb-3">

        {{-- Menu Alignment --}}
        <div class="row align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-medium">{{ __('file.field.alignment') }}</label>
                <select name="footer[social_links_align]" class="form-select shadow-sm">
                    <option value="start"  {{ ($widget->content['social_links_align'] ?? '') == 'start' ? 'selected' : '' }}>{{ __('file.option.left') }}</option>
                    <option value="center" {{ ($widget->content['social_links_align'] ?? '') == 'center' ? 'selected' : '' }}>{{ __('file.option.center') }}</option>
                    <option value="end"    {{ ($widget->content['social_links_align'] ?? '') == 'end' ? 'selected' : '' }}>{{ __('file.option.right') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-medium">{{ __('file.field.icon_color') }}</label>
                <input type="color" class="form-control form-control-color shadow-sm" name="footer[icon_color]"
                    value="{{ $widget->content['icon_color'] ?? '#000000' }}">
            </div>
        </div>

        <hr>
        <div id="socialLinksRepeater">
            @php
                $socialLinks = $widget->content['social_links'] ?? [];
            @endphp

            @foreach($socialLinks as $index => $item)
                <div class="menu-item border rounded p-2 mb-3" draggable="false">
                    <div class="row alighn-items-center">
                        <div class="col-md-1 d-flex align-items-center justify-content-center">
                            <span class="drag-handle" style="cursor: grab;">
                                <i style="font-size:20px;" class="fa fa-grip-vertical" aria-hidden="true"></i>
                            </span>
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.icon') }}</label>
                            <input type="text" name="footer[social_links][{{ $index }}][icon_class]" class="form-control shadow-sm"
                                   value="{{ $item['icon_class'] ?? '' }}" required>
                        </div>
                        {{-- CUSTOM URL --}}
                        <div class="col-md-4 d-flex align-items-center menu-url">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.url') }}</label>
                            <input type="text" name="footer[social_links][{{ $index }}][url]" class="form-control shadow-sm"
                                   value="{{ $item['url'] ?? '' }}">
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
           {{ __('file.button.add_social_link') }}
        </button>
    </div>

    <h6 class="fw-bold">{{ __('file.title.company_links') }}</h6>
    <div class="border rounded p-3 mb-3">
        <div id="companyLinksRepeater">
            @php
                $companyLinks = $widget->content['company_links'] ?? [];
            @endphp

            @foreach($companyLinks as $index => $companyLink)
                <div class="company-link-item border rounded p-2 mb-3" draggable="false">
                    <div class="row alighn-items-center">
                        <div class="col-md-1 d-flex align-items-center justify-content-center">
                            <span class="drag-handle" style="cursor: grab;">
                                <i style="font-size:20px;" class="fa fa-grip-vertical" aria-hidden="true"></i>
                            </span>
                        </div>
                        <div class="col-md-3 d-flex align-items-center">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.label') }}</label>
                            <input type="text" name="footer[company_links][{{ $index }}][label]" class="form-control shadow-sm"
                                   value="{{ $companyLink['label'] ?? '' }}" required>
                        </div>

                        <div class="col-md-3 d-flex align-items-center">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.type') }}</label>
                            <select class="form-select shadow-sm company-link-type" name="footer[company_links][{{ $index }}][type]">
                                <option value="page"   {{ ($companyLink['type'] ?? '') == 'page' ? 'selected' : '' }}>{{ __('file.option.page') }}</option>
                                <option value="page_widget" {{ ($companyLink['type'] ?? '') == 'page_widget' ? 'selected' : '' }}>{{ __('file.option.page_widget') }}</option>
                                <option value="custom" {{ ($companyLink['type'] ?? '') == 'custom' ? 'selected' : '' }}>{{ __('file.option.custom_URL') }}</option>
                            </select>
                        </div>

                        {{-- PAGE SELECT --}}
                        <div class="col-md-3 d-flex align-items-center company-link-page">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.select_page') }}</label>
                            <select name="footer[company_links][{{ $index }}][page]" class="form-select shadow-sm">
                                <option {{ ($companyLink['url'] ?? '') == url('/') ? 'selected' : '' }} value="/">{{ __('file.option.homepage') }}</option>
                                @foreach($pages as $page)
                                    <option value="pages/{{ $page->slug }}" {{ ($companyLink['url'] ?? '') ==  url('pages/'.$page->slug) ? 'selected' : '' }}>
                                        {{ $page->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- CUSTOM URL --}}
                        <div class="col-md-3 d-flex align-items-center company-link-url d-none">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.url') }}</label>
                            <input type="text" name="footer[company_links][{{ $index }}][url]" class="form-control shadow-sm"
                                   value="{{ $companyLink['url'] ?? '' }}">
                        </div>

                        {{-- widgets --}}
                        <div class="col-md-3 d-flex align-items-center company-link-widgets d-none">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.widgets') }}</label>
                            <select name="footer[company_links][{{ $index }}][widget]" class="form-select shadow-sm">
                                @foreach($widgetList as $item)
                                    <option value="{{ Str::slug($item->title) }}" {{ ($companyLink['url'] ?? '') == Str::slug($item->title) ? 'selected' : '' }}>
                                        {{ $item->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" onclick="$(this).closest('.company-link-item').remove()" class="btn btn-danger w-100">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-primary mt-2" id="addCompanyLinkBtn">
           {{ __('file.button.add_company_link') }}
        </button>
    </div>

    <h6 class="fw-bold">{{ __('file.title.quick_links') }}</h6>
    <div class="border rounded p-3 mb-3">
        <div id="quickLinksRepeater">
            @php
                $quickLinks = $widget->content['quick_links'] ?? [];
            @endphp

            @foreach($quickLinks as $index => $quickLink)
                <div class="quick-link-item border rounded p-2 mb-3" draggable="false">
                    <div class="row alighn-items-center">
                        <div class="col-md-1 d-flex align-items-center justify-content-center">
                            <span class="drag-handle" style="cursor: grab;">
                                <i style="font-size:20px;" class="fa fa-grip-vertical" aria-hidden="true"></i>
                            </span>
                        </div>
                        <div class="col-md-3 d-flex align-items-center">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.label') }}</label>
                            <input type="text" name="footer[quick_links][{{ $index }}][label]" class="form-control shadow-sm"
                                   value="{{ $quickLink['label'] ?? '' }}" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-center">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.type') }}</label>
                            <select class="form-select shadow-sm quick-link-type" name="footer[quick_links][{{ $index }}][type]">
                                <option value="page"   {{ ($quickLink['type'] ?? '') == 'page' ? 'selected' : '' }}>{{ __('file.option.page') }}</option>
                                <option value="page_widget" {{ ($quickLink['type'] ?? '') == 'page_widget' ? 'selected' : '' }}>{{ __('file.option.page_widget') }}</option>
                                <option value="custom" {{ ($quickLink['type'] ?? '') == 'custom' ? 'selected' : '' }}>{{ __('file.option.custom_URL') }}</option>
                            </select>
                        </div>

                        {{-- PAGE SELECT --}}
                        <div class="col-md-3 d-flex align-items-center quick-link-page">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.select_page') }}</label>
                            <select name="footer[quick_links][{{ $index }}][page]" class="form-select shadow-sm">
                                <option {{ ($quickLink['url'] ?? '') == url('/') ? 'selected' : '' }} value="/">{{ __('file.option.homepage') }}</option>
                                @foreach($pages as $page)
                                    <option value="pages/{{ $page->slug }}" {{ ($quickLink['url'] ?? '') ==  url('pages/'.$page->slug) ? 'selected' : '' }}>
                                        {{ $page->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- CUSTOM URL --}}
                        <div class="col-md-3 d-flex align-items-center quick-link-url d-none">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.url') }}</label>
                            <input type="text" name="footer[quick_links][{{ $index }}][url]" class="form-control shadow-sm"
                                   value="{{ $quickLink['url'] ?? '' }}">
                        </div>

                        {{-- widgets --}}
                        <div class="col-md-3 d-flex align-items-center quick-link-widgets d-none">
                            <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.widgets') }}</label>
                            <select name="footer[quick_links][{{ $index }}][widget]" class="form-select shadow-sm">
                                @foreach($widgetList as $item)
                                    <option value="{{ Str::slug($item->title) }}" {{ ($quickLink['url'] ?? '') == Str::slug($item->title) ? 'selected' : '' }}>
                                        {{ $item->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" onclick="$(this).closest('.company-link-item').remove()" class="btn btn-danger w-100">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-primary mt-2" id="addQuickLinkBtn">
           {{ __('file.button.add_quick_link') }}
        </button>
    </div>

    <div class="row mt-3 align-items-end">
        <div class="col-md-12 mb-3">
            <label for="copyright" class="form-label">{{__('file.field.copyright_text')}}</label>
            <input type="text" class="form-control shadow-sm" id="copyright" name="footer[copyright_text]"
                value="{{ old('footer.copyright_text', $widget->content['copyright_text'] ?? '') }}">
        </div>
    </div>

    <div class="row mt-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label fw-medium">{{ __('file.field.section_width') }}</label>
            <select name="footer_settings[width]" class="form-select shadow-sm">
                <option value="w-full" {{ ($widget->settings['width'] ?? '') == 'full' ? 'selected' : '' }}>{{ __('file.option.full_width') }}</option>
                <option value="container" {{ ($widget->settings['width'] ?? '') == 'container' ? 'selected' : '' }}>{{ __('file.option.container_width') }}</option>
            </select>
        </div>

        <div class="col-md-2">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="is_enabled" value="1" {{ old('is_enabled', $widget->is_enabled) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.enable_section') }}</label>
            </div>
        </div>
    </div>


    {{-- SAVE BUTTON --}}
    <div class="d-flex justify-content-between mt-4">
        <button type="submit" class="btn btn-info px-4">
            <i class="fa-solid fa-save me-1"></i> {{ __('file.button.update_footer') }}
        </button>
    </div>
</form>



{{-- ================================
        JS SECTION
================================= --}}

<script>

$(document).ready(function() {

        $('#bgType').on('change', function() {
        var selectedValue = $(this).val();
        if (selectedValue === 'color') {
            $('.bg-color').removeClass('d-none');
            $('.bg-image').addClass('d-none');
            $('.bg-gradient').addClass('d-none');
        } else if (selectedValue === 'image') {
            $('.bg-color').addClass('d-none');
            $('.bg-image').removeClass('d-none');
            $('.bg-gradient').addClass('d-none');
        } else if (selectedValue === 'gradient') {
            $('.bg-color').addClass('d-none');
            $('.bg-image').addClass('d-none');
            $('.bg-gradient').removeClass('d-none');
        } else {
            $('.bg-color').addClass('d-none');
            $('.bg-image').addClass('d-none');
            $('.bg-gradient').addClass('d-none');
        }
    });


    // Add Menu Item
    let socialMenuIndex = {{ count($socialLinks) }};
    $('#addMenuBtn').click(function() {

        const html = `
            <div class="menu-item border rounded p-2 mb-3">
                <div class="row align-items-center">
                    <div class="col-md-1 d-flex align-items-center justify-content-center">
                        <span class="drag-handle" style="cursor: grab;">
                            <i style="font-size: 20px;" class="fa-solid fa-grip-vertical"></i>
                        </span>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <label class="form-label mb-0 me-2 fw-medium">Icon</label>
                        <input type="text" name="footer[social_links][${socialMenuIndex}][icon_class]" class="form-control shadow-sm" required>
                    </div>

                    <div class="col-md-4 d-flex align-items-center menu-url">
                        <label class="form-label mb-0 me-2 fw-medium">URL</label>
                        <input type="text" name="footer[social_links][${socialMenuIndex}][url]" class="form-control shadow-sm">
                    </div>


                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" onclick="$(this).closest('.menu-item').remove()" class="btn btn-danger w-100"><i class="fa-solid fa-trash"></i></button>
                    </div>

                </div>
            </div>
        `;

        $('#socialLinksRepeater').append(html);
        socialMenuIndex++;
    });

    function toggleCompanyLinkType(el) {
        const val = el.val();
        const container = el.closest('.company-link-item');

        if (val === 'page') {
            container.find('.company-link-page').removeClass('d-none');
            container.find('.company-link-url').addClass('d-none');
            container.find('.company-link-widgets').addClass('d-none');
        } else if (val === 'page_widget') {
            container.find('.company-link-widgets').removeClass('d-none');
            container.find('.company-link-page').addClass('d-none');
            container.find('.company-link-url').addClass('d-none');
        }else {
            container.find('.company-link-page').addClass('d-none');
            container.find('.company-link-url').removeClass('d-none');
            container.find('.company-link-widgets').addClass('d-none');
        }
    }

    $('.company-link-type').each(function(){ toggleCompanyLinkType($(this)); });
    $(document).on('change', '.company-link-type', function() { toggleCompanyLinkType($(this)); });

        // Add Menu Item
    let companyLinkIndex = {{ count($companyLinks) }};
    $('#addCompanyLinkBtn').click(function() {

        const html = `
            <div class="company-link-item border rounded p-2 mb-3">
                <div class="row align-items-center">
                    <div class="col-md-1 d-flex align-items-center justify-content-center">
                        <span class="drag-handle" style="cursor: grab;">
                            <i style="font-size: 20px;" class="fa-solid fa-grip-vertical"></i>
                        </span>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.label') }}</label>
                        <input type="text" name="footer[company_links][${companyLinkIndex}][label]" class="form-control shadow-sm" required>
                    </div>

                    <div class="col-md-3 d-flex align-items-center">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.type') }}</label>
                        <select class="form-select shadow-sm company-link-type" name="footer[company_links][${companyLinkIndex}][type]">
                            <option value="page">{{ __('file.option.page') }}</option>
                            <option value="page_widget">{{ __('file.option.page_widget') }}</option>
                            <option value="custom">{{ __('file.option.custom_URL') }}</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-center company-link-page">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.select_page') }}</label>
                        <select name="footer[company_links][${companyLinkIndex}][page]" class="form-select shadow-sm">
                            <option value="/">{{ __('file.option.homepage') }}</option>
                            @foreach($pages as $page)
                                <option value="pages/{{ $page->slug }}">{{ $page->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-center company-link-url d-none">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.url') }}</label>
                        <input type="text" name="footer[company_links][${companyLinkIndex}][url]" class="form-control shadow-sm">
                    </div>

                    <div class="col-md-3 d-flex align-items-center company-link-widgets d-none">
                        <label class="form-label mb-0 me-2 fw-medium">Select Widget</label>
                        <select name="footer[company_links][${companyLinkIndex}][widget]" class="form-select shadow-sm">
                            @foreach($widgetList as $item)
                                <option value="{{ Str::slug($item->title) }}">{{ $item->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" onclick="$(this).closest('.company-link-item').remove()" class="btn btn-danger w-100"><i class="fa-solid fa-trash"></i></button>
                    </div>

                </div>
            </div>
        `;

        $('#companyLinksRepeater').append(html);
        companyLinkIndex++;
    });


    function toggleQuickLinkType(el) {
        const val = el.val();
        const container = el.closest('.quick-link-item');

        if (val === 'page') {
            container.find('.quick-link-page').removeClass('d-none');
            container.find('.quick-link-url').addClass('d-none');
            container.find('.quick-link-widgets').addClass('d-none');
        } else if (val === 'page_widget') {
            container.find('.quick-link-widgets').removeClass('d-none');
            container.find('.quick-link-page').addClass('d-none');
            container.find('.quick-link-url').addClass('d-none');
        }else {
            container.find('.quick-link-page').addClass('d-none');
            container.find('.quick-link-url').removeClass('d-none');
            container.find('.quick-link-widgets').addClass('d-none');
        }
    }

    $('.quick-link-type').each(function(){ toggleQuickLinkType($(this)); });
    $(document).on('change', '.quick-link-type', function() { toggleQuickLinkType($(this)); });

        // Add Menu Item
    let quickLinkIndex = {{ count($quickLinks) }};
    $('#addQuickLinkBtn').click(function() {

        const html = `
            <div class="quick-link-item border rounded p-2 mb-3">
                <div class="row align-items-center">
                    <div class="col-md-1 d-flex align-items-center justify-content-center">
                        <span class="drag-handle" style="cursor: grab;">
                            <i style="font-size: 20px;" class="fa-solid fa-grip-vertical"></i>
                        </span>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.label') }}</label>
                        <input type="text" name="footer[quick_links][${quickLinkIndex}][label]" class="form-control shadow-sm" required>
                    </div>

                    <div class="col-md-3 d-flex align-items-center">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.type') }}</label>
                        <select class="form-select shadow-sm quick-link-type" name="footer[quick_links][${quickLinkIndex}][type]">
                            <option value="page">{{ __('file.option.page') }}</option>
                            <option value="page_widget">{{ __('file.option.page_widget') }}</option>
                            <option value="custom">{{ __('file.option.custom_URL') }}</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-center quick-link-page">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.select_page') }}</label>
                        <select name="footer[quick_links][${quickLinkIndex}][page]" class="form-select shadow-sm">
                            <option value="/">{{ __('file.option.homepage') }}</option>
                            @foreach($pages as $page)
                                <option value="pages/{{ $page->slug }}">{{ $page->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-center quick-link-url d-none">
                        <label class="form-label mb-0 me-2 fw-medium">{{ __('file.field.url') }}</label>
                        <input type="text" name="footer[quick_links][${quickLinkIndex}][url]" class="form-control shadow-sm">
                    </div>

                    <div class="col-md-3 d-flex align-items-center quick-link-widgets d-none">
                        <label class="form-label mb-0 me-2 fw-medium">Select Widget</label>
                        <select name="footer[quick_links][${quickLinkIndex}][widget]" class="form-select shadow-sm">
                            @foreach($widgetList as $item)
                                <option value="{{ Str::slug($item->title) }}">{{ $item->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" onclick="$(this).closest('.quick-link-item').remove()" class="btn btn-danger w-100"><i class="fa-solid fa-trash"></i></button>
                    </div>

                </div>
            </div>
        `;

        $('#quickLinksRepeater').append(html);
        quickLinkIndex++;
    });

});

function previewImage(input, previewId) {
    const file = input.files[0];
    if (file) {
        document.getElementById(previewId).src = URL.createObjectURL(file);
    }
}

new Sortable(document.getElementById('socialLinksRepeater'), {
    animation: 150,
    handle: ".drag-handle",
    ghostClass: "sortable-ghost",
    onEnd: function () {
        // sorting হলে index reassign
        $('#socialLinksRepeater .menu-item').each(function(index) {
            $(this).find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const updated = name.replace(/footer\[social_links\]\[\d+\]/, `footer[social_links][${index}]`);
                    $(this).attr('name', updated);
                }
            });
        });
    }
});

new Sortable(document.getElementById('companyLinksRepeater'), {
    animation: 150,
    handle: ".drag-handle",
    ghostClass: "sortable-ghost",
    onEnd: function () {
        // sorting হলে index reassign
        $('#companyLinksRepeater .company-link-item').each(function(index) {
            $(this).find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const updated = name.replace(/footer\[company_links\]\[\d+\]/, `footer[company_links][${index}]`);
                    $(this).attr('name', updated);
                }
            });
        });
    }
});

new Sortable(document.getElementById('quickLinksRepeater'), {
    animation: 150,
    handle: ".drag-handle",
    ghostClass: "sortable-ghost",
    onEnd: function () {
        // sorting হলে index reassign
        $('#quickLinksRepeater .quick-link-item').each(function(index) {
            $(this).find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const updated = name.replace(/footer\[quick_links\]\[\d+\]/, `footer[quick_links][${index}]`);
                    $(this).attr('name', updated);
                }
            });
        });
    }
});
</script>
