<form action="{{ route('landlord.update-widget', $widget->id) }}" method="POST" id="editWidgetForm"
    enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    {{-- Basic Info --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="title" class="form-label fw-medium">{{ __('file.field.title') }}</label>
            <input type="text" class="form-control" name="title" value="{{ old('title', $widget->title) }}"
                required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="sort_order" class="form-label fw-medium">{{ __('file.field.order') }}</label>
            <input type="number" class="form-control " name="sort_order"
                value="{{ old('sort_order', $widget->sort_order) }}" required>
        </div>
        <div class="col-md-12 mb-3">
            <label for="subtitle" class="form-label fw-medium">{{ __('file.field.subtitle') }}</label>
            <input type="text" class="form-control" name="subtitle" value="{{ old('subtitle', $widget->subtitle) }}">
        </div>

    </div>

    {{-- Dynamic Slide Items --}}
    @if($widget->content_type == 'dynamic')
        <div class="row align-items-center">
            <div class="col-md-3 mb-3">
                <label for="model" class="form-label fw-medium">{{ __('file.field.content') }}</label>
                <select name="model" id="model" class="form-select">
                    <option {{ old('model', $widget->content['model'] ?? '') == 'App\Models\landlord\Feature' ? 'selected' : '' }} value="App\Models\landlord\Feature">{{ __('file.option.features') }}</option>
                    <option {{ old('model', $widget->content['model'] ?? '') == 'App\Models\landlord\Package' ? 'selected' : '' }}  value="App\Models\landlord\Package">{{ __('file.option.packages') }}</option>
                    <option {{ old('model', $widget->content['model'] ?? '') == 'App\Models\landlord\Blog' ? 'selected' : '' }}  value="App\Models\landlord\Blog">{{ __('file.option.blogs') }}</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="limit" class="form-label fw-medium">{{ __('file.field.limit') }}</label>
                <input type="number" class="form-control" name="limit" value="{{ old('limit', $widget->content['limit'] ?? '') }}" required>
            </div>
            <div class="col-md-3 mb-3">
                <label for="order_by" class="form-label fw-medium">{{ __('file.field.order_by') }}</label>
                <select name="order_by" id="order_by" class="form-select">
                    <option {{ old('order_by', $widget->content['order_by'] ?? '') == 'created_at' ? 'selected' : '' }} value="created_at">{{ __('file.option.created_at') }}</option>
                    <option {{ old('order_by', $widget->content['order_by'] ?? '') == 'price' ? 'selected' : '' }} value="price">{{ __('file.option.price') }}</option>
                    <option {{ old('order_by', $widget->content['order_by'] ?? '') == 'name' ? 'selected' : '' }} value="name">{{ __('file.option.name') }}</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="order" class="form-label fw-medium">{{ __('file.field.order') }}</label>
                <select name="order" id="order" class="form-select">
                    <option {{ old('order', $widget->content['order'] ?? '') == 'asc' ? 'selected' : '' }} value="asc">{{ __('file.option.asc') }}</option>
                    <option {{ old('order', $widget->content['order'] ?? '') == 'desc' ? 'selected' : '' }} value="desc">{{ __('file.option.desc') }}</option>
                </select>
            </div>
        </div>
    @else
    <div id="slideItemsContainer">
        @php
            $items = $widget->content['items'] ?? [['image' => '', 'text' => '']];
        @endphp

        @foreach ($items as $index => $item)
            <div class="slide-item border rounded-3 p-3 mb-3 bg-light shadow-sm">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    {{-- Preview --}}
                    <div class="d-flex justify-content-center align-items-center" style="width: 10%">
                        <img src="{{ $item['image'] ? asset('storage/' . $item['image']) : asset('images/preview_image.png') }}"
                            class="img-thumbnail border-0 rounded-3 shadow-sm preview-image" style="max-height: 50px;">
                        @if($item['image'])
                            <input type="hidden" name="slides[{{ $index }}][existing_image]" value="{{ $item['image'] }}" >
                        @endif
                    </div>

                    {{-- Image --}}
                    <div class="d-flex justify-content-center align-items-center" style="width: 25%">
                        <div class="input-group shadow-sm">
                            <input type="file" name="slides[{{ $index }}][image]" accept=".jpg, .png, .webp"
                                class="form-control" onchange="previewImage(this)">
                            <label class="input-group-text bg-primary text-white">
                                <i class="fa-solid fa-upload"></i>
                            </label>
                        </div>
                    </div>

                    {{-- Text --}}
                    <div class="d-flex justify-content-center align-items-center" style="width: 60%">
                        <input type="text" name="slides[{{ $index }}][text]" value="{{ $item['text'] ?? '' }}"
                            class="form-control shadow-sm" placeholder="Enter slide caption...">
                    </div>

                    {{-- Remove Button --}}
                    <div class="d-flex justify-content-center align-items-center" style="width: 5%">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeSlideItem(this)">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

        {{-- Add New Button --}}
    <div class="text-center mt-3">
        <button type="button" class="btn btn-outline-primary" id="addSlideItemBtn">
            <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.add_new_slide') }}
        </button>
    </div>
    @endif

    {{-- Slider Settings --}}
    <div class="row mt-3 align-items-end">
        <div class="col-md-2 mb-3">
            <label class="form-label">{{ __('file.field.slides_to_show') }}</label>
            <input type="number" name="slider_settings[slides_to_show]" class="form-control" value="{{ $widget->settings['slides_to_show'] ?? 1 }}"
                min="1">
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label">{{ __('file.field.slides_to_show_tablet') }}</label>
            <input type="number" name="slider_settings[slides_to_show_tablet]" class="form-control" value="{{ $widget->settings['slides_to_show_tablet'] ?? 1 }}"
                min="1">
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label">{{ __('file.field.slides_to_show_mobile') }}</label>
            <input type="number" name="slider_settings[slides_to_show_mobile]" class="form-control" value="{{ $widget->settings['slides_to_show_mobile'] ?? 1 }}"
                min="1">
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label">{{ __('file.field.autoplay_speed') }} (ms)</label>
            <input type="number" name="slider_settings[autoplay_speed]" class="form-control" value="{{ $widget->settings['autoplay_speed'] ?? 3000 }}">
        </div>

        <div class="col-md-2 mb-3 d-flex align-items-center">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="slider_settings[autoplay]" {{ old('slider_settings.autoplay', $widget->settings['autoplay'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.autoplay') }}</label>
            </div>
        </div>

        <div class="col-md-2 mb-3 d-flex align-items-center">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="slider_settings[arrows]" {{ old('slider_settings.arrows', $widget->settings['arrows'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.arrows') }}</label>
            </div>
        </div>

        <div class="col-md-2 mb-3 d-flex align-items-center">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="slider_settings[dots]" {{ old('slider_settings.dots', $widget->settings['dots'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.dots') }}</label>
            </div>
        </div>

        <div class="col-md-2 mb-3 d-flex align-items-center">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="slider_settings[infinite_loop]" {{ old('slider_settings.infinite_loop', $widget->settings['infinite_loop'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.infinite_loop') }}</label>
            </div>
        </div>

        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="slider_settings[show_title_on_top]" {{ old('slider_settings.show_title_on_top', $widget->settings['show_title_on_top'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.show_title') }}</label>
            </div>
        </div>

        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="is_enabled" value="1" {{ old('is_enabled', $widget->is_enabled) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.enable_section') }}</label>
            </div>
        </div>

        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="slider_settings[show_caption]" {{ old('slider_settings.show_caption', $widget->settings['show_caption'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ __('file.field.show_caption') }}</label>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <label for="caption_color" class="form-label">{{ __('file.field.caption_color') }}</label>
            <input type="color" class="form-control form-control-color" name="slider_settings[caption_color]" value="{{ old('slider_settings.caption_color', $widget->settings['caption_color'] ?? '#000000') }}">
        </div>

        <div class="col-md-2 mb-3">
            <label for="width" class="form-label fw-medium">{{ __('file.field.section_width') }}</label>
            <select name="slider_settings[width]" class="form-select">
                <option value="w-full" {{ ($widget->settings['width'] ?? '') == 'w-full' ? 'selected' : '' }}>{{ __('file.option.full_width') }}</option>
                <option value="container" {{ ($widget->settings['width'] ?? '') == 'container' ? 'selected' : '' }}>{{ __('file.option.container_width') }}</option>
            </select>
        </div>
    </div>

    {{-- Save Button --}}
    <div class="d-flex justify-content-between mt-4">
        <button type="submit" class="btn btn-info px-4">
            <i class="fa-solid fa-save me-1"></i> {{ __('file.button.update') }} {{ __('file.widget') }}
        </button>
        <button type="button" class="btn btn-danger" onclick="deleteWidget({{ $widget->id }})">
            <i class="fa-solid fa-trash me-1"></i> {{ __('file.button.delete') }}
        </button>
    </div>
</form>

<script>
    @if($widget->content_type == 'static')
        document.getElementById('addSlideItemBtn').addEventListener('click', function() {
            const container = document.getElementById('slideItemsContainer');
            const index = container.children.length;

            const newItem = document.createElement('div');
            newItem.classList.add('slide-item', 'border', 'rounded-3', 'p-3', 'mb-3', 'bg-light', 'shadow-sm');
            newItem.innerHTML = `
            <div class="d-flex align-items-center justify-content-between gap-3">

                <!-- Preview -->
                <div class="d-flex justify-content-center align-items-center" style="width: 10%">
                    <img src="{{ asset('images/preview_image.png') }}"
                        class="img-thumbnail border-0 rounded-3 shadow-sm preview-image"
                        style="max-height: 50px;">
                </div>

                <!-- Image -->
                <div class="d-flex justify-content-center align-items-center" style="width: 25%">
                    <div class="input-group shadow-sm">
                        <input type="file" name="slides[${index}][image]" accept=".jpg, .png, .webp"
                            class="form-control" onchange="previewImage(this)">
                        <label class="input-group-text bg-primary text-white">
                            <i class="fa-solid fa-upload"></i>
                        </label>
                    </div>
                </div>

                <!-- Text -->
                <div class="d-flex justify-content-center align-items-center" style="width: 60%">
                    <input type="text" name="slides[${index}][text]" value=""
                        class="form-control shadow-sm" placeholder="Enter slide caption...">
                </div>

                <!-- Remove Button -->
                <div class="d-flex justify-content-center align-items-center" style="width: 5%">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeSlideItem(this)">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
            </div>
        `;

            container.appendChild(newItem);
        });

        function previewImage(input) {
            const file = input.files[0];
            const preview = input.closest('.slide-item').querySelector('.preview-image');
            if (file) {
                const reader = new FileReader();
                reader.onload = e => preview.src = e.target.result;
                reader.readAsDataURL(file);
            }
        }

        function removeSlideItem(button) {
            const container = document.getElementById('slideItemsContainer');
            if (container.children.length > 1) {
                button.closest('.slide-item').remove();
            } else {
                showFloatingAlert('error', '{{ __('file.message.at_least_one_row_is_required') }}');
            }
        }
    @endif
</script>
