<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📦 {{ __('file.package_info') }}</h5>
    </div>
    <div class="card-body row g-3">
        {{-- Package Name --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">{{ __('file.package') }} {{ __('file.field.name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $package->name ?? '') }}"
                placeholder="e.g. Premium Plan" required>
        </div>

        {{-- Trial Toggle --}}
        <div class="col-md-6">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_trial" id="is_trial" value="1"
                    {{ old('is_trial', $package->is_trial ?? false) ? 'checked' : '' }}>
                <label class="form-check-label fw-bold" for="is_trial">{{ __('file.field.is_trial') }}</label>
            </div>
            <input type="text" name="trial_period" class="form-control" pattern="^[0-9]+$"
                value="{{ old('trial_period', $package->meta['trial_period'] ?? '') }}" placeholder="e.g. 30">
        </div>

        {{-- Description --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">{{ __('file.field.description') }}</label>
            <textarea name="description" rows="6" class="form-control" placeholder="Short description">{{ old('description', $package->description ?? '') }}</textarea>
        </div>

        {{-- Price --}}
        <div class="col-md-3">
            <label class="form-label fw-bold">{{ __('file.field.reseller_min_reg_fee') }}</label>
            <input type="text" name="reseller_min_reg_fee" class="form-control" pattern="^[0-9]+$"
                value="{{ old('reseller_min_reg_fee', $package->reseller_min_reg_fee ?? '') }}" placeholder="e.g. 100">
        </div>

        {{-- Image --}}
        <div class="col-md-3">
            <label class="form-label fw-bold">{{ __('file.field.image') }}</label>
            <div class="input-group">
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png"
                    onchange="document.getElementById('image_preview').src = window.URL.createObjectURL(this.files[0]);">
                <label class="input-group-text" for="image">
                    <i class="fa-solid fa-upload"></i>
                </label>
            </div>

            <img id="image_preview"
                src="{{ isset($package) && $package->image ? asset('storage/' . $package->image) : asset('images/preview_image.png') }}"
                class="img-thumbnail mt-2" style="max-height: 100px;">
        </div>
    </div>
</div>

{{-- FEATURES --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success d-flex gap-4 justify-content-start align-items-center">
        <h5 class="mb-0">🧩 {{ __('file.select_feature') }}</h5>
        <div class="form-check d-flex gap-2 align-items-center">
            <input type="checkbox" class="form-check-input" style="width: 1.5em; height: 1.5em;"
                id="select_all_features">
            <label class="form-check-label fw-bold" for="select_all_features">Select All</label>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            @foreach ($features as $feature)
                @php
                    $packageFeature = isset($package)
                        ? $package->features->firstWhere('feature_id', $feature->id)
                        : null;
                    $isChecked = old("features.$feature->id.enabled", $packageFeature ? true : false);
                    $meta = $packageFeature ? $packageFeature->meta : [];
                    $limitValue = old("features.$feature->id.limit", $meta['limit'] ?? '');
                @endphp

                <div class="col-md-3 mb-3">
                    <div
                        class="feature-item d-flex align-items-center justify-content-between border rounded-3 p-3 {{ $feature->has_module ? 'bg-light' : '' }}">
                        <div class="flex-grow-1 pe-3">
                            <div class="d-flex align-items-center mb-1">
                                <input type="checkbox" class="form-check-input me-2 feature-checkbox"
                                    name="features[{{ $feature->id }}][enabled]" id="feature_{{ $feature->id }}"
                                    value="1" {{ $isChecked ? 'checked' : '' }}>

                                <label for="feature_{{ $feature->id }}" class="fw-bold mb-0">
                                    {{ $feature->name }}
                                </label>
                            </div>
                        </div>
                        <div style="width: 100px;">
                            <input type="text" name="features[{{ $feature->id }}][limit]"
                                class="form-control form-control-sm feature-limit-input"
                                value="{{ $limitValue }}" {{ $isChecked ? '' : 'disabled' }} placeholder="Limit"
                                autocomplete="off">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- MODULES --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">🧩 {{ __('file.select_module') }}</h5>
        <div class="form-check d-flex gap-2 align-items-center">
            <input type="checkbox" class="form-check-input" style="width: 1.5em; height: 1.5em;"
                id="select_all_modules">
            <label class="form-check-label fw-bold" for="select_all_modules">Select All</label>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            @foreach ($modules as $module)
                @php
                    $packageModule = isset($package)
                        ? $package->modules->firstWhere('module_id', $module->id)
                        : null;
                    $isChecked = old("modules.$module->id.enabled", $packageModule ? true : false);
                @endphp

                <div class="col-md-3 mb-3">
                    <div
                        class="module-item d-flex align-items-center justify-content-between border rounded-3 p-3">
                        <div class="flex-grow-1 pe-3">
                            <div class="d-flex align-items-center mb-1">
                                <input type="checkbox" class="form-check-input me-2 module-checkbox"
                                    name="modules[{{ $module->id }}][enabled]" id="module_{{ $module->id }}"
                                    value="1" {{ $isChecked ? 'checked' : '' }}>

                                <label for="module_{{ $module->id }}" class="fw-bold mb-0">
                                    {{ $module->name }}
                                </label>
                            </div>
                            <span>{{ $module->description ?? '' }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- PRICING --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <h5 class="mb-0">💰 {{ __('file.package_pricing') }}</h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('file.table.type') }}</th>
                        <th>{{ __('file.table.price') }}</th>
                        <th>{{ __('file.table.duration') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $pricing = isset($package) ? $package->pricing->keyBy('type') : collect();
                    @endphp

                    <tr>
                        <td><strong>{{ __('file.field.monthly') }}</strong></td>
                        <td>
                            <input type="number" step="0.01" name="pricing[monthly][price]" class="form-control"
                                value="{{ old('pricing.monthly.price', $pricing['monthly']->price ?? '') }}"
                                placeholder="e.g. 1000">
                        </td>
                        <td>
                            <input type="number" name="pricing[monthly][duration_days]" readonly value="30"
                                class="form-control">
                        </td>
                    </tr>

                    <tr>
                        <td><strong>{{ __('file.field.quarterly') }}</strong></td>
                        <td>
                            <input type="number" step="0.01" name="pricing[quarterly][price]"
                                class="form-control"
                                value="{{ old('pricing.quarterly.price', $pricing['quarterly']->price ?? '') }}"
                                placeholder="e.g. 5000">
                        </td>
                        <td>
                            <input type="number" name="pricing[quarterly][duration_days]" readonly value="90"
                                class="form-control">
                        </td>
                    </tr>

                    <tr>
                        <td><strong>{{ __('file.field.semi_annually') }}</strong></td>
                        <td>
                            <input type="number" step="0.01" name="pricing[semi_annually][price]"
                                class="form-control"
                                value="{{ old('pricing.semi_annually.price', $pricing['semi_annually']->price ?? '') }}"
                                placeholder="e.g. 10000">
                        </td>
                        <td>
                            <input type="number" name="pricing[semi_annually][duration_days]" readonly
                                value="180" class="form-control">
                        </td>
                    </tr>

                    <tr>
                        <td><strong>{{ __('file.field.yearly') }}</strong></td>
                        <td>
                            <input type="number" step="0.01" name="pricing[yearly][price]" class="form-control"
                                value="{{ old('pricing.yearly.price', $pricing['yearly']->price ?? '') }}"
                                placeholder="e.g. 10000">
                        </td>
                        <td>
                            <input type="number" name="pricing[yearly][duration_days]" readonly value="365"
                                class="form-control">
                        </td>
                    </tr>

                    <tr>
                        <td><strong>{{ __('file.field.lifetime') }}</strong></td>
                        <td>
                            <input type="number" step="0.01" name="pricing[lifetime][price]"
                                class="form-control"
                                value="{{ old('pricing.lifetime.price', $pricing['lifetime']->price ?? '') }}"
                                placeholder="e.g. 25000">
                        </td>
                        <td>
                            <input type="number" name="pricing[lifetime][duration_days]" readonly placeholder="∞"
                                class="form-control">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
