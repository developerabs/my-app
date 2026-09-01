@if (isset($custom_fields) && $custom_fields->count() > 0)
    <div class="row custom-fields-wrapper">
        @foreach ($custom_fields as $field)
            @php
                /* English Comment:
                   Check if we are in Edit mode by checking $model. 
                   Retrieve the value from polymorphic 'customFieldValues' relation.
                */
                $savedValue = null;
                if (isset($model) && $model->customFieldValues) {
                    $savedValue = $model->customFieldValues->where('custom_field_id', $field->id)->first()?->value;
                }

                $current_value = $savedValue ?? $field->default_value;
                $input_name = 'custom_fields[' . $field->id . ']';
            @endphp

            <div class="{{ $field->type == 'textarea' ? 'col-md-12' : 'col-md-6' }} mb-3">
                <label class="form-label fw-bold">
                    {{ $field->label }}
                    @if ($field->is_required)
                        <span class="text-danger">*</span>
                    @endif
                </label>

                {{-- Text, Number, Date, Email --}}
                @if (in_array($field->type, ['text', 'number', 'email']))
                    <input type="{{ $field->type }}" name="{{ $input_name }}" class="form-control"
                        value="{{ $current_value }}" placeholder="{{ $field->placeholder }}" {{ $field->is_required ? 'required' : '' }}>

                    {{-- Date Type with Flatpickr --}}
                @elseif($field->type == 'date')
                    <div class="input-group">
                        <input type="text" name="{{ $input_name }}" class="form-control custom-datepicker" placeholder="YYYY-MM-DD" value="{{ $current_value }}" readonly
                            {{ $field->is_required ? 'required' : '' }}>
                    </div>

                    {{-- Textarea --}}
                @elseif($field->type == 'textarea')
                    <textarea name="{{ $input_name }}" class="form-control shadow-sm" rows="3"
                        placeholder="{{ $field->placeholder }}" {{ $field->is_required ? 'required' : '' }}>{{ $current_value }}</textarea>

                    {{-- Select Dropdown --}}
                @elseif($field->type == 'select')
                    <select name="{{ $input_name }}" class="form-select select2 shadow-sm"
                        data-placeholder="{{ $field->placeholder ?? __('file.select') }}"
                        {{ $field->is_required ? 'required' : '' }}>
                        @if ($field->options)
                            @foreach ($field->options as $option)
                                <option value="{{ $option }}"
                                    {{ in_array($option, (array) $field->default_value) ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                            @endforeach
                        @endif
                    </select>

                    {{-- Radio Buttons --}}
                @elseif($field->type == 'radio')
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        @if ($field->options)
                            @foreach ($field->options as $option)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="{{ $input_name }}"
                                        id="radio_{{ $field->id }}_{{ $loop->index }}"
                                        value="{{ $option }}" {{ $current_value == $option ? 'checked' : '' }}
                                        {{ $field->is_required ? 'required' : '' }}>
                                    <label class="form-check-label"
                                        for="radio_{{ $field->id }}_{{ $loop->index }}">
                                        {{ $option }}
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    {{-- Checkbox (Stored as comma separated or array) --}}
                @elseif($field->type == 'checkbox')
                    <div class="d-flex flex-wrap gap-3 mt-2">
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
                                    <label class="form-check-label"
                                        for="check_{{ $field->id }}_{{ $loop->index }}">
                                        {{ $option }}
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif