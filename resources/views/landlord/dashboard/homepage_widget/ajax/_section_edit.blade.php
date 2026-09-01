<form action="{{ route('landlord.update-widget', $widget->id) }}" method="POST" id="editWidgetForm"
    enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    {{-- ================================
        BASIC INFO
    ================================= --}}
    <div class="row">
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

    {{-- ================================
        BACKGROUND SETTINGS
    ================================= --}}
    <h5 class="mt-2 mb-2 fw-bold">Background Settings</h5>
    <div class="border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.background_type') }}</label>
                <select name="section[bg_type]" class="form-select shadow-sm" id="bgType">
                    <option value="color" {{ ($widget->content['bg_type'] ?? '') == 'color' ? 'selected' : '' }}>{{ __('file.option.color') }}</option>
                    <option value="image" {{ ($widget->content['bg_type'] ?? '') == 'image' ? 'selected' : '' }}>{{ __('file.option.image') }}</option>
                    <option value="gradient" {{ ($widget->content['bg_type'] ?? '') == 'gradient' ? 'selected' : '' }}>{{ __('file.option.gradient') }}</option>
                </select>
            </div>
            <div class="col-md-10">
                <div class="bg-field bg-color {{ ($widget->content['bg_type'] ?? '') == 'color' ? '' : 'd-none' }}">
                    <label class="form-label fw-medium">{{ __('file.field.background_color') }}</label>
                    <input type="color" name="section[bg_color]" class="form-control form-control-color shadow-sm"
                        value="{{ $widget->content['bg_color'] ?? '#ffffff' }}">
                </div>
                <div class="bg-field bg-image {{ ($widget->content['bg_type'] ?? '') == 'image' ? '' : 'd-none' }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label fw-medium">{{ __('file.field.background_image') }}</label>
                            <div class="d-flex align-items-start">
                                <input type="file" name="section[bg_image]" class="form-control shadow-sm" onchange="previewImage(this, 'bgImagePreview')" accept="image/*">
                            </div>
                            <small class="form-text text-muted">Image size: 1920x500</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">{{ __('file.field.position') }}</label>
                            <select name="section[bg_image_position]" class="form-select shadow-sm">
                                <option value="center" {{ ($widget->content['bg_image_position'] ?? '') == 'center' ? 'selected' : '' }}>{{ __('file.option.center') }}</option>
                                <option value="left" {{ ($widget->content['bg_image_position'] ?? '') == 'left' ? 'selected' : '' }}>{{ __('file.option.left') }}</option>
                                <option value="right" {{ ($widget->content['bg_image_position'] ?? '') == 'right' ? 'selected' : '' }}>{{ __('file.option.right') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">{{ __('file.field.repeat') }}</label>
                            <select name="section[bg_image_repeat]" class="form-select shadow-sm">
                                <option value="no-repeat" {{ ($widget->content['bg_image_repeat'] ?? '') == 'no-repeat' ? 'selected' : '' }}>{{ __('file.option.no_repeat') }}</option>
                                <option value="repeat" {{ ($widget->content['bg_image_repeat'] ?? '') == 'repeat' ? 'selected' : '' }}>{{ __('file.option.repeat') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">{{ __('file.field.size') }}</label>
                            <select name="section[bg_image_size]" class="form-select shadow-sm">
                                <option value="cover" {{ ($widget->content['bg_image_size'] ?? '') == 'cover' ? 'selected' : '' }}>{{ __('file.option.cover') }}</option>
                                <option value="contain" {{ ($widget->content['bg_image_size'] ?? '') == 'contain' ? 'selected' : '' }}>{{ __('file.option.contain') }}</option>
                                <option value="auto" {{ ($widget->content['bg_image_size'] ?? '') == 'auto' ? 'selected' : '' }}>{{ __('file.option.auto') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-3">
                            <img id="bgImagePreview" src="{{ $widget->content['bg_image'] ?? '' ? asset('storage/' . $widget->content['bg_image']) : asset('images/preview_image.png') }}" class="img-thumbnail rounded" style="max-height: 100px;">
                            <input type="hidden" name="section[existing_bg_image]" value="{{ $widget->content['bg_image'] ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="row bg-field bg-gradient {{ ($widget->content['bg_type'] ?? '') == 'gradient' ? '' : 'd-none' }}">
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-medium">{{ __('file.field.gradient_start') }}</label>
                        <input type="color" name="section[bg_gradient_start]" class="form-control form-control-color shadow-sm"
                            value="{{ $widget->content['bg_gradient_start'] ?? '#000000' }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-medium">{{ __('file.field.gradient_end') }}</label>
                        <input type="color" name="section[bg_gradient_end]" class="form-control form-control-color shadow-sm"
                            value="{{ $widget->content['bg_gradient_end'] ?? '#ffffff' }}">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-medium">{{ __('file.field.direction') }}</label>
                        <select name="section[bg_gradient_direction]" class="form-select shadow-sm">
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
        TEXT SETTINGS
    ================================= --}}
    <h5 class="mt-4 mb-2 fw-bold">{{ __('file.title.text_settings') }}</h5>
    <div class="border rounded p-3 mb-3">

        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.heading') }}</label>
                <input type="text" class="form-control shadow-sm" name="section[content]"
                    value="{{ old('section[content]', $widget->content['body'] ?? '') }}">
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.sub_heading') }}</label>
                <input type="text" name="section[sub_heading]" class="form-control form-control shadow-sm"
                    value="{{ $widget->content['sub_heading'] ?? '' }}">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.text_color') }}</label>
                <input type="color" name="section[text_color]" class="form-control form-control-color shadow-sm"
                    value="{{ $widget->content['text_color'] ?? '#000000' }}">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.sub_heading_color') }}</label>
                <input type="color" name="section[sub_heading_color]" class="form-control form-control-color shadow-sm"
                    value="{{ $widget->content['sub_heading_color'] ?? '#000000' }}">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.font_size') }}</label>
                <input type="number" name="section[font_size]" class="form-control shadow-sm"
                    value="{{ $widget->content['font_size'] ?? '' }}">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.text_align') }}</label>
                <select name="section[text_align]" class="form-select shadow-sm">
                    <option value="left" {{ ($widget->content['text_align'] ?? '') == 'left' ? 'selected' : '' }}>{{ __('file.option.left') }}</option>
                    <option value="center" {{ ($widget->content['text_align'] ?? '') == 'center' ? 'selected' : '' }}>{{ __('file.option.center') }}</option>
                    <option value="right" {{ ($widget->content['text_align'] ?? '') == 'right' ? 'selected' : '' }}>{{ __('file.option.right') }}</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.font_weight') }}</label>
                <select name="section[font_weight]" class="form-select shadow-sm">
                    <option value="100" {{ ($widget->content['font_weight'] ?? '') == '100' ? 'selected' : '' }}>{{ __('file.option.thin') }}</option>
                    <option value="300" {{ ($widget->content['font_weight'] ?? '') == '300' ? 'selected' : '' }}>{{ __('file.option.light') }}</option>
                    <option value="400" {{ ($widget->content['font_weight'] ?? '') == '400' ? 'selected' : '' }}>{{ __('file.option.normal') }}</option>
                    <option value="500" {{ ($widget->content['font_weight'] ?? '') == '500' ? 'selected' : '' }}>{{ __('file.option.medium') }}</option>
                    <option value="600" {{ ($widget->content['font_weight'] ?? '') == '600' ? 'selected' : '' }}>{{ __('file.option.semi_bold') }}</option>
                    <option value="700" {{ ($widget->content['font_weight'] ?? '') == '700' ? 'selected' : '' }}>{{ __('file.option.bold') }}</option>
                    <option value="800" {{ ($widget->content['font_weight'] ?? '') == '800' ? 'selected' : '' }}>{{ __('file.option.extra_bold') }}</option>
                    <option value="900" {{ ($widget->content['font_weight'] ?? '') == '900' ? 'selected' : '' }}>{{ __('file.option.black') }}</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.show_total_customers') }}</label>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="section[show_customer_counter]" value="1" {{ old('section.show_customer_counter', $widget->content['show_customer_counter'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.show_customer_counter') }}</label>
            </div>
            </div>
        </div>
        <div class="row">
            {{-- FLOAT IMAGE 1 --}}
            <div class="col-md-6 mb-4">
                <div class="border rounded p-3 h-100">

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="section[show_floating_image_1]" value="1" {{ old('section.show_floating_image_1', $widget->content['show_floating_image_1'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label">{{ __('file.field.show_floating_image_1') }}</label>
                    </div>
                    {{-- Upload --}}
                    <label class="form-label fw-medium">{{ __('file.field.image') }}</label>
                    <input type="file" name="section[floating_image_1]" class="form-control shadow-sm"
                        onchange="previewImage(this, 'floatPreview1')" accept="image/*">

                    <div class="mt-2">
                        <img id="floatPreview1"
                            src="{{ isset($widget->content['floating_image_1']) ? asset('storage/'.$widget->content['floating_image_1']) : asset('images/preview_image.png') }}"
                            class="img-thumbnail rounded" style="max-width:150px;">
                    </div>

                    <input type="hidden" name="section[existing_floating_image_1]"
                        value="{{ $widget->content['floating_image_1'] ?? '' }}">

                </div>
            </div>

            {{-- FLOAT IMAGE 2 --}}
            <div class="col-md-6 mb-4">
                <div class="border rounded p-3 h-100">

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="section[show_floating_image_2]" value="1" {{ old('section.show_floating_image_2', $widget->content['show_floating_image_2'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label">{{ __('file.field.show_floating_image_2') }}</label>
                    </div>

                    {{-- Upload --}}
                    <label class="form-label fw-medium">{{ __('file.field.image') }}</label>
                    <input type="file" name="section[floating_image_2]" class="form-control shadow-sm"
                        onchange="previewImage(this, 'floatPreview2')" accept="image/*">

                    <div class="mt-2">
                        <img id="floatPreview2"
                            src="{{ isset($widget->content['floating_image_2']) ? asset('storage/'.$widget->content['floating_image_2']) : asset('images/preview_image.png') }}"
                            class="img-thumbnail rounded" style="max-width:150px;">
                    </div>

                    <input type="hidden" name="section[existing_floating_image_2]"
                        value="{{ $widget->content['floating_image_2'] ?? '' }}">
                </div>
            </div>
        </div>
    </div>



    {{-- ================================
        BUTTON SETTINGS
    ================================= --}}
    <h5 class="mt-4 mb-2 fw-bold">{{ __('file.title.button_settings') }}</h5>
    <div class="border rounded p-3 mb-3">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.button_text') }}</label>
                <input type="text" name="section[button_text]" class="form-control shadow-sm"
                    value="{{ $widget->content['button_text'] ?? 'Click Here' }}">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.button_type') }}</label>
                <select name="section[button_type]" class="form-select shadow-sm" id="btnType">
                    <option value="internal" {{ ($widget->content['button_type'] ?? '') == 'internal' ? 'selected' : '' }}>{{ __('file.option.internal_link') }}</option>
                    <option value="external" {{ ($widget->content['button_type'] ?? '') == 'external' ? 'selected' : '' }}>{{ __('file.option.external_URL') }}</option>
                </select>
            </div>

            {{-- INTERNAL --}}
            <div class="col-md-4 mb-3 btn-field btn-internal">
                <label class="form-label fw-medium">{{ __('file.field.route_name') }}</label>
                <select name="section[button_route]" class="form-select shadow-sm">
                    <option value="" {{ old('section[button_route]', $widget->content['button_route'] ?? '') == '' ? 'selected' : '' }}>{{ __('file.option.select_route') }}</option>
                    @foreach ($widgetList as $item)
                        <option value="#{{ Str::slug($item->title) }}" {{ old('section[button_route]', $widget->content['button_route'] ?? '') == '#'.Str::slug($item->title) ? 'selected' : '' }}>{{ $item->title }}</option>
                    @endforeach
                </select>
            </div>

            {{-- EXTERNAL --}}
            <div class="col-md-4 mb-3 btn-field btn-external">
                <label class="form-label fw-medium">{{ __('file.field.external_link') }}</label>
                <input type="text" name="section[button_url]" class="form-control shadow-sm"
                       placeholder="https://example.com" value="{{ old('section[button_url]', $widget->content['button_url'] ?? '') }}">
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.button_text_color') }}</label>
                <input type="color" name="section[button_text_color]" class="form-control form-control-color shadow-sm"
                       value="{{ old('section[button_text_color]', $widget->content['button_text_color'] ?? '#000000') }}">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.button_color') }}</label>
                <input type="color" name="section[button_color]" class="form-control form-control-color shadow-sm"
                       value="{{ old('section[button_color]', $widget->content['button_color'] ?? '#000000') }}">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-medium">{{ __('file.field.button_hover_color') }}</label>
                <input type="color" name="section[button_hover_color]" class="form-control form-control-color shadow-sm"
                       value="{{ old('section[button_hover_color]', $widget->content['button_hover_color'] ?? '#000000') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-medium">{{ __('file.field.visibility') }}</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="section[show_button]" id="show_button" {{ old('section[show_button]', $widget->content['show_button'] ?? '') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="show_button">
                       {{ __('file.field.show_button') }}
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================
        WIDGET ENABLE / DISABLE
    ================================= --}}
    <div class="row mt-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label fw-medium">{{ __('file.field.section_width') }}</label>
            <select name="section_settings[width]" class="form-select shadow-sm">
                <option value="w-full" {{ ($widget->settings['width'] ?? '') == 'w-full' ? 'selected' : '' }}>{{ __('file.option.full_width') }}</option>
                <option value="container" {{ ($widget->settings['width'] ?? '') == 'container' ? 'selected' : '' }}>{{ __('file.option.container_width') }}</option>
            </select>
        </div>
        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="section_settings[show_title_on_top]" {{ old('section_settings.show_title_on_top', $widget->settings['show_title_on_top'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.show_title') }}</label>
            </div>
        </div>

        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="is_enabled" value="1" {{ old('is_enabled', $widget->is_enabled) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.enable_section') }}</label>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between mt-4">
        <button type="submit" class="btn btn-info px-4">
            <i class="fa-solid fa-save me-1"></i> {{ __('file.button.update_widget') }}
        </button>

        <button type="button" class="btn btn-danger" onclick="deleteWidget({{ $widget->id }})">
            <i class="fa-solid fa-trash me-1"></i> {{ __('file.button.delete') }}
        </button>
    </div>
</form>


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
    })

    function previewImage(input, previewId) {
        const file = input.files[0];
        if (file) {
            document.getElementById(previewId).src = URL.createObjectURL(file);
        }
    }
</script>
