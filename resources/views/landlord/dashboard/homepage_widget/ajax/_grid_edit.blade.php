<form action="{{ route('landlord.update-widget', $widget->id) }}" method="POST" id="editWidgetForm"
    enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    {{-- Basic Info --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="title" class="form-label fw-medium">{{ __('file.field.title') }}</label>
            <input type="text" class="form-control shadow-sm" name="title" value="{{ old('title', $widget->title) }}"
                required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="sort_order" class="form-label fw-medium">{{ __('file.field.order') }}</label>
            <input type="number" class="form-control shadow-sm" name="sort_order"
                value="{{ old('sort_order', $widget->sort_order) }}" required>
        </div>
        <div class="col-md-12 mb-3">
            <label for="subtitle" class="form-label fw-medium">{{ __('file.field.subtitle') }}</label>
            <input type="text" class="form-control shadow-sm" name="subtitle" value="{{ old('subtitle', $widget->subtitle) }}">
        </div>
    </div>

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
        <div id="contentContainer">
            @php
                $items = $widget->content['items'] ?? [['image' => '', 'text' => '', 'description' => '']];
            @endphp

            @foreach ($items as $key => $item)
                <div class="custome-content w-100 p-2 mb-3 border border-dashed rounded-3" style="border: 1px dashed #ccc;">
                    <div class="row align-items-start">
                        {{-- Image Upload --}}
                        <div class="col-2">
                            <div class="uploadImageBox position-relative text-center p-1"
                                style="border: 1px dashed #333; width:100%; height:100px; cursor:pointer;"
                                onclick="this.querySelector('input[type=file]').click();">
                                <img src="{{ $item['image'] ? asset('storage/' . $item['image']) : asset('images/preview_image.png') }}" class="img-fluid preview-image"
                                    style="max-height: 100%; object-fit: contain;">
                                @if($item['image'])
                                    <input type="hidden" name="items[{{ $key }}][existing_image]" value="{{ $item['image'] }}" >
                                @endif
                                <input type="file" hidden name="items[{{ $key }}][image]" class="uploadImage"
                                    accept="image/jpeg,image/png,image/webp"
                                    onchange="previewImage(this)">
                            </div>
                        </div>

                        {{-- Text --}}
                        <div class="col-9">
                            <div class="row align-items-center mb-2">
                                <div class="col-10">
                                    <input type="text" class="form-control shadow-sm" name="items[{{ $key }}][text]" value="{{ $item['text'] }}" placeholder="Title" required>
                                </div>
                                <div class="col-2">
                                    <input type="number" class="form-control shadow-sm" name="items[{{ $key }}][order]" value="{{ $item['order'] ?? 1 }}" min="1" title="Order" required>
                                </div>
                            </div>
                            {{-- Description --}}
                            <div class="col-12">
                                <textarea name="items[{{ $key }}][description]" class="form-control shadow-sm" rows="2" placeholder="Short description">{{ $item['description'] }}</textarea>
                            </div>
                        </div>
                        {{-- remove button --}}
                        <div class="col-1 d-flex align-items-center">
                            <button type="button" class="btn btn-danger" onclick="removeRow(this)">
                                <i class="fa-solid fa-trash me-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Add Button --}}
        <div class="text-center">
            <button type="button" class="btn btn-outline-primary mt-2" id="addRowBtn">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.add_new_grid_item') }}
            </button>
        </div>
    @endif
    {{-- grid Settings --}}
    <div class="row mt-3 align-items-end">
        <div class="col-md-2 mb-3">
            <label for="width" class="form-label fw-medium">{{ __('file.field.section_width') }}</label>
            <select name="grid_settings[width]" class="form-select">
                <option value="w-full" {{ ($widget->settings['width'] ?? '') == 'w-full' ? 'selected' : '' }}>{{ __('file.option.full_width') }}</option>
                <option value="container" {{ ($widget->settings['width'] ?? '') == 'container' ? 'selected' : '' }}>{{ __('file.option.container_width') }}</option>
            </select>
        </div>
        <div class="col-md-2 mb-3">
            <label for="grid_per_row" class="form-label">{{ __('file.field.grid_per_row') }}</label>
            <input type="text" class="form-control" name="grid_settings[grid_per_row]" id="grid_per_row" value="{{ old('grid_settings.grid_per_row', $widget->settings['grid_per_row'] ?? '3') }}">
        </div>

        <div class="col-md-2 mb-3">
            <label for="grid_gap" class="form-label">{{ __('file.field.grid_gap') }}</label>
            <input type="number" class="form-control" name="grid_settings[grid_gap]" id="grid_gap" value="{{ old('grid_settings.grid_gap', $widget->settings['grid_gap'] ?? 0) }}">
        </div>

        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="grid_settings[show_title_on_top]" {{ old('grid_settings.show_title_on_top', $widget->settings['show_title_on_top'] ?? false) ? 'checked' : '' }}>
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
            // ✅ Preview selected image instantly
        function previewImage(input) {
            const file = input.files[0];
            const preview = input.closest('.uploadImageBox').querySelector('.preview-image');
            if (file) {
                const reader = new FileReader();
                reader.onload = e => preview.src = e.target.result;
                reader.readAsDataURL(file);
            }
        }

        // ✅ Add new row dynamically
        document.getElementById('addRowBtn').addEventListener('click', function() {
            const container = document.getElementById('contentContainer');
            const index = container.children.length; // new index
            const newRow = document.createElement('div');

            newRow.classList.add('custome-content', 'w-100', 'p-2', 'mb-3', 'border', 'border-dashed', 'rounded-3');
            newRow.innerHTML = `
                <div class="row align-items-start">
                    <div class="col-2">
                        <div class="uploadImageBox position-relative text-center p-1"
                            style="border: 1px dashed #333; width:100%; height:100px; cursor:pointer;"
                            onclick="this.querySelector('input[type=file]').click();">
                            <img src="{{ asset('images/preview_image.png') }}" class="img-fluid preview-image"
                                style="max-height: 100%; object-fit: contain;">
                            <input type="file" hidden name="items[${index}][image]" class="uploadImage"
                                accept="image/jpeg,image/png,image/webp"
                                onchange="previewImage(this)">
                        </div>
                    </div>

                    <div class="col-9">
                        <div class="row align-items-center mb-2">
                            <div class="col-10">
                                <input type="text" class="form-control shadow-sm" name="items[${index}][text]" placeholder="Title" required>
                            </div>
                            <div class="col-2">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" class="form-control shadow-sm" name="items[${index}][order]" value="${index+1}" min="1" title="Order">
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <textarea name="items[${index}][description]" class="form-control shadow-sm" rows="2" placeholder="Short description"></textarea>
                        </div>
                    </div>
                    <div class="col-1 d-flex align-items-center">
                        <button type="button" class="btn btn-danger" onclick="removeRow(this)">
                            <i class="fa-solid fa-trash me-1"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        });

        // ✅ Remove a row
        function removeRow(button) {
            const container = document.getElementById('contentContainer');
            if (container.children.length > 1) {
                button.closest('.custome-content').remove();
            } else {
                showFloatingAlert('error', '{{ __('file.message.at_least_one_row_is_required') }}');
            }
        }
    @endif

</script>
