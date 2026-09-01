@php
    /* English Comment:
       Retrieve the grid class passed from the parent view. 
       Default is 'col-md-6' if not provided.
    */
    $grid = $grid_class ?? 'col-md-6';
@endphp

@if (isset($custom_fields) && $custom_fields->count() > 0)
    @foreach ($custom_fields as $field)
        @php
            /* English Comment:
               Retrieve existing value if in Edit mode, else use default.
            */
            $savedValue = null;
            if (isset($model) && $model->customFieldValues) {
                $savedValue = $model->customFieldValues
                    ->where('custom_field_id', $field->id)
                    ->first()?->value;
            }

            $current_value = $savedValue ?? $field->default_value;
            $input_name = 'custom_fields[' . $field->id . ']';
            
            // Textarea is always full width; others follow the grid logic.
            $current_grid = ($field->type == 'textarea') ? 'col-12' : $grid;
        @endphp

        <div class="{{ $current_grid }} mb-3">
            <label class="form-label fw-bold">
                {{ $field->label }}
                @if ($field->is_required)
                    <span class="text-danger">*</span>
                @endif
            </label>

            {{-- Text, Number, Email --}}
            @if (in_array($field->type, ['text', 'number', 'email']))
                <input type="{{ $field->type }}" name="{{ $input_name }}" class="form-control"
                    value="{{ $current_value }}" placeholder="{{ $field->placeholder }}" 
                    {{ $field->is_required ? 'required' : '' }}>

            {{-- Date Type --}}
            @elseif($field->type == 'date')
                <input type="text" name="{{ $input_name }}" class="form-control custom-datepicker" 
                    placeholder="YYYY-MM-DD" value="{{ $current_value }}" readonly
                    {{ $field->is_required ? 'required' : '' }}>

            {{-- Textarea --}}
            @elseif($field->type == 'textarea')
                <textarea name="{{ $input_name }}" class="form-control" rows="3"
                    placeholder="{{ $field->placeholder }}" 
                    {{ $field->is_required ? 'required' : '' }}>{{ $current_value }}</textarea>

            {{-- Select Dropdown --}}
            @elseif($field->type == 'select')
                <select name="{{ $input_name }}" class="form-select select2"
                    data-placeholder="{{ $field->placeholder ?? __('file.select') }}"
                    {{ $field->is_required ? 'required' : '' }}>
                    <option value=""></option>
                    @if ($field->options)
                        @foreach ($field->options as $option)
                            <option value="{{ $option }}" {{ $current_value == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    @endif
                </select>

            {{-- Radio Buttons --}}
            @elseif($field->type == 'radio')
                <div class="d-flex flex-wrap gap-3 mt-1">
                    @if ($field->options)
                        @foreach ($field->options as $option)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="{{ $input_name }}"
                                    id="radio_{{ $field->id }}_{{ $loop->index }}"
                                    value="{{ $option }}" {{ $current_value == $option ? 'checked' : '' }}
                                    {{ $field->is_required ? 'required' : '' }}>
                                <label class="form-check-label" for="radio_{{ $field->id }}_{{ $loop->index }}">
                                    {{ $option }}
                                </label>
                            </div>
                        @endforeach
                    @endif
                </div>

            {{-- Checkbox --}}
            @elseif($field->type == 'checkbox')
                <div class="d-flex flex-wrap gap-3 mt-1">
                    @if ($field->options)
                        @php
                            $current_values = is_array($current_value) 
                                ? $current_value 
                                : explode(',', $current_value);
                            $current_values = array_map('trim', $current_values);
                        @endphp
                        @foreach ($field->options as $option)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="{{ $input_name }}[]"
                                    id="check_{{ $field->id }}_{{ $loop->index }}"
                                    value="{{ $option }}"
                                    {{ in_array($option, $current_values) ? 'checked' : '' }}>
                                <label class="form-check-label" for="check_{{ $field->id }}_{{ $loop->index }}">
                                    {{ $option }}
                                </label>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endif
        </div>
    @endforeach
@endif