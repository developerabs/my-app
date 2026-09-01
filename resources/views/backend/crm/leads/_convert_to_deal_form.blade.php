<div class="row g-3">
    <div class="col-md-12">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.note') }} <span class="text-danger">*</span></label>
        <textarea name="note" class="form-control" rows="3"
            placeholder="{{ __('file.placeholder.add_note') }}" required></textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.type') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" name="type" required>
            <option value="">-- {{ __('file.option.select') }}</option>
            <option value="lead">Lead</option>
            <option value="deal">Deal</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.category') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none selectnew2" name="category_id" required>
            
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.status') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none selectnew2" name="status_id" required>
            
        </select>
    </div>
    <div class="col-md-6 mt-2">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.follow_up_date') }}</label>
        <input type="text" class="form-control shadow-none date-picker" name="follow_up_date" placeholder="DD-MM-YYYY">
    </div>
</div>