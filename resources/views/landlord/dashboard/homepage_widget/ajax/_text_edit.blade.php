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

    <div class="mb-3">
        <label for="content" class="form-label">{{ __('file.field.content') }}</label> <button type="button" class="btn btn-sm btn-info" id="toggle-source">Toggle HTML</button>
        <div id="content">{!! old('content', $widget->content['description'] ?? '') !!}</div>
        <textarea id="source-container" style="display:none; width:100%; height:300px;"></textarea>

        <input type="hidden" name="content" id="content_input" value="{{ old('content', $widget->content['description'] ?? '') }}">
    </div>
    {{-- grid Settings --}}
    <div class="row mt-3 align-items-end">
        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="text_settings[show_title_on_top]" {{ old('text_settings.show_title_on_top', $widget->settings['show_title_on_top'] ?? false) ? 'checked' : '' }}>
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
