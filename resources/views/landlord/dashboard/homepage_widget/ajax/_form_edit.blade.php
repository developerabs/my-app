<form action="{{ route('landlord.update-widget', $widget->id) }}" method="POST" id="editWidgetForm" enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    {{-- ---------------- Basic Info ---------------- --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-medium">{{ __('file.field.title') }}</label>
            <input type="text" class="form-control shadow-sm" name="title" value="{{ old('title', $widget->title) }}" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-medium">{{ __('file.field.order') }}</label>
            <input type="number" class="form-control shadow-sm" name="sort_order" value="{{ old('sort_order', $widget->sort_order) }}" required>
        </div>
        <div class="col-md-12 mb-3">
            <label class="form-label fw-medium">{{ __('file.field.subtitle') }}</label>
            <input type="text" class="form-control shadow-sm" name="subtitle" value="{{ old('subtitle', $widget->subtitle) }}">
        </div>
    </div>

    {{-- ---------------- Dynamic Fields ---------------- --}}
    @if($widget->content_type == 'static')
        <div id="contentContainer">
            @php
                $items = $widget->content['items'] ?? [['field_type'=>'text','field_name'=>'','field_label'=>'','field_value'=>'','width'=>'','is_required'=>0]];
            @endphp

            @foreach($items as $key => $item)
                <div class="custome-content w-100 p-2 mb-3 border border-dashed rounded-3">
                    <div class="row align-items-start">
                        <div class="col-11">
                            <div class="row align-items-end">
                                {{-- Field Type --}}
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('file.field.field_type') }}</label>
                                    <select name="items[{{ $key }}][field_type]" class="form-select">
                                        <option value="text" {{ $item['field_type']=='text'?'selected':'' }}>{{ __('file.option.text') }}</option>
                                        <option value="number" {{ $item['field_type']=='number'?'selected':'' }}>{{ __('file.option.number') }}</option>
                                        <option value="email" {{ $item['field_type']=='email'?'selected':'' }}>{{ __('file.option.email') }}</option>
                                        <option value="date" {{ $item['field_type']=='date'?'selected':'' }}>{{ __('file.option.date') }}</option>
                                        <option value="select" {{ $item['field_type']=='select'?'selected':'' }}>{{ __('file.option.select') }}</option>
                                        <option value="textarea" {{ $item['field_type']=='textarea'?'selected':'' }}>{{ __('file.option.textarea') }}</option>
                                    </select>
                                </div>

                                {{-- Field Label --}}
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.field_label') }}</label>
                                    <input type="text" name="items[{{ $key }}][field_label]" class="form-control" value="{{ old("items.$key.field_label",$item['field_label'] ?? '') }}">
                                </div>

                                {{-- Field Name --}}
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.field_name') }}</label>
                                    <input type="text" name="items[{{ $key }}][field_name]" class="form-control" value="{{ old("items.$key.field_name",$item['field_name'] ?? '') }}">
                                </div>

                                {{-- Width --}}
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('file.field.width') }}</label>
                                    <select name="items[{{ $key }}][width]" class="form-select">
                                        <option value="12" {{ $item['width']=='12'?'selected':'' }}>12</option>
                                        <option value="6" {{ $item['width']=='6'?'selected':'' }}>6</option>
                                        <option value="4" {{ $item['width']=='4'?'selected':'' }}>4</option>
                                        <option value="3" {{ $item['width']=='3'?'selected':'' }}>3</option>
                                    </select>
                                </div>

                                {{-- Required --}}
                                <div class="col-md-2">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" name="items[{{ $key }}][is_required]" class="form-check-input" value="1" {{ $item['is_required']?'checked':'' }}>
                                        <label class="form-check-label">{{ __('file.field.is_required') }}</label>
                                    </div>
                                </div>

                                {{-- Default Value --}}
                                <div class="col-12 mt-2">
                                    <label class="form-label">{{ __('file.field.field_value') }}</label>
                                    <input type="text" name="items[{{ $key }}][field_value]" class="form-control" value="{{ old("items.$key.field_value",$item['field_value'] ?? '') }}">
                                </div>
                            </div>
                        </div>

                        {{-- Remove Button --}}
                        <div class="col-1 d-flex align-items-center">
                            <button type="button" class="btn btn-danger" onclick="removeRow(this)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Add Button --}}
        <div class="text-center">
            <button type="button" class="btn btn-outline-primary mt-2" id="addRowBtn">
                <i class="fa-solid fa-plus"></i> {{ __('file.button.add_new_field') }}
            </button>
        </div>
    @else
        <div class="alert alert-danger">{{ __('file.message.widget_type_not_supported') }}</div>
    @endif

    <div class="row mt-3">
        <div class="col-md-3">
            <label class="form-label">{{ __('file.field.form_submitted_for') }}</label>
            <select name="form_settings[form_submitted_for]" class="form-select">
                <option value="contact_form" {{ ($widget->settings['form_submitted_for'] ?? '')=='contact_form'?'selected':'' }}>{{ __('file.option.contact_form') }}</option>
                <option value="booking" {{ ($widget->settings['form_submitted_for'] ?? '')=='booking'?'selected':'' }}>{{ __('file.option.booking') }}</option>
                <option value="newsletter" {{ ($widget->settings['form_submitted_for'] ?? '')=='newsletter'?'selected':'' }}>{{ __('file.option.newsletter') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('file.field.button_text') }}</label>
            <input type="text" name="form_settings[button_text]" class="form-control" value="{{ $widget->settings['button_text'] ?? 'Submit' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('file.field.button_color') }}</label>
            <select name="form_settings[button_color]" class="form-select">
                <option value="btn-primary" {{ ($widget->settings['button_color'] ?? '')=='btn-primary'?'selected':'' }}>{{ __('file.option.primary') }}</option>
                <option value="btn-info" {{ ($widget->settings['button_color'] ?? '')=='btn-info'?'selected':'' }}>{{ __('file.option.info') }}</option>
                <option value="btn-success" {{ ($widget->settings['button_color'] ?? '')=='btn-success'?'selected':'' }}>{{ __('file.option.success') }}</option>
                <option value="btn-danger" {{ ($widget->settings['button_color'] ?? '')=='btn-danger'?'selected':'' }}>{{ __('file.option.danger') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('file.field.button_align') }}</label>
            <select name="form_settings[button_align]" class="form-select">
                <option value="start" {{ ($widget->settings['button_align'] ?? '')=='start'?'selected':'' }}>{{ __('file.option.left') }}</option>
                <option value="center" {{ ($widget->settings['button_align'] ?? '')=='center'?'selected':'' }}>{{__('file.option.center') }}</option>
                <option value="end" {{ ($widget->settings['button_align'] ?? '')=='end'?'selected':'' }}>{{ __('file.option.right') }}</option>
            </select>
        </div>
    </div>
    {{-- ---------------- Grid / Form Settings ---------------- --}}
    <div class="row mt-3 align-items-end">
        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" name="form_settings[show_title_on_top]" class="form-check-input" {{ old('form_settings.show_title_on_top', $widget->settings['show_title_on_top'] ?? false)?'checked':'' }}>
                <label class="form-check-label">{{ __('file.field.show_title') }}</label>
            </div>
        </div>

        <div class="col-md-2 mb-3">
            <div class="form-check">
                <input type="checkbox" name="is_enabled" class="form-check-input" value="1" {{ old('is_enabled', $widget->is_enabled)?'checked':'' }}>
                <label class="form-check-label">{{ __('file.field.enable_section') }}</label>
            </div>
        </div>
    </div>

    {{-- ---------------- Save / Delete Button ---------------- --}}
    <div class="d-flex justify-content-between mt-4">
        <button type="submit" class="btn btn-info px-4"><i class="fa-solid fa-save me-1"></i>{{ __('file.button.update_widget') }}</button>
        <button type="button" class="btn btn-danger" onclick="deleteWidget({{ $widget->id }})"><i class="fa-solid fa-trash me-1"></i> {{ __('file.option.delete_widget') }}</button>
    </div>
</form>

{{-- ---------------- JS for Dynamic Fields ---------------- --}}
<script>
@if($widget->content_type == 'static')
    // Add new row
    document.getElementById('addRowBtn').addEventListener('click', function(){
        const container = document.getElementById('contentContainer');
        const index = container.children.length;

        const newRow = document.createElement('div');
        newRow.classList.add('custome-content','w-100','p-2','mb-3','border','border-dashed','rounded-3');
        newRow.innerHTML = `
        <div class="row align-items-start">
            <div class="col-11">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">{{ __('file.field.field_type') }}</label>
                        <select name="items[${index}][field_type]" class="form-select">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="email">Email</option>
                            <option value="date">Date</option>
                            <option value="select">Select</option>
                            <option value="textarea">Textarea</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('file.field.field_label') }}</label>
                        <input type="text" name="items[${index}][field_label]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Field Name</label>
                        <input type="text" name="items[${index}][field_name]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('file.field.width') }}</label>
                        <select name="items[${index}][width]" class="form-select">
                            <option value="12" selected>12</option>
                            <option value="6">6</option>
                            <option value="4">4</option>
                            <option value="3">3</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="items[${index}][is_required]" class="form-check-input" value="1">
                            <label class="form-check-label">{{ __('file.field.is_required') }}</label>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label">{{ __('file.field.field_value') }}</label>
                        <input type="text" name="items[${index}][field_value]" class="form-control">
                    </div>
                </div>
            </div>
            <div class="col-1 d-flex align-items-center">
                <button type="button" class="btn btn-danger" onclick="removeRow(this)">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
        `;
        container.appendChild(newRow);
    });

    // Remove row
    function removeRow(btn){
        const container = document.getElementById('contentContainer');
        if(container.children.length > 1){
            btn.closest('.custome-content').remove();
        } else {
            alert('At least one field is required.');
        }
    }
@endif
</script>
