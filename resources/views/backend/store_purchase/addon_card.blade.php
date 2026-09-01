<div class="col-lg-2 col-md-3 col-12 mb-4">
    <div class="card h-100 shadow-sm border-0">
        <div class="position-relative">
            {{-- Image section --}}
            @if (isset($addon['meta']['image']))
                <img src="{{ url('storage/' . $addon['meta']['image']) }}"
                    class="card-img-top p-3" alt="{{ $addon['name'] }}"
                    style="height: 140px; object-fit: contain; background: #f8f9fa;">
            @else
                <div class="d-flex align-items-center justify-content-center bg-light"
                    style="height: 140px;">
                    <i class="fa fa-puzzle-piece fa-3x text-secondary"></i>
                </div>
            @endif

            {{-- Badge for Type --}}
            <span
                class="badge bg-{{ $addon['type'] == 'feature' ? 'primary' : 'success' }} position-absolute top-0 end-0 m-2">
                {{ ucfirst($addon['type']) }}
            </span>
        </div>

        <div class="card-body pb-0">
            <h5 class="card-title fw-bold text-dark mb-1">{{ $addon['name'] }}</h5>

            <p class="text-muted small mb-3">
                @if ($addon['type'] == 'limit')
                    <i class="fa fa-plus-circle"></i> {{ $addon['meta']['limit_value'] }}
                    {{ __('file.limit_increase') }}
                @else
                    <i class="fa fa-check-circle text-success"></i>
                    {{ __('file.unlock_feature') }}
                @endif
            </p>

            <div class="mt-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span
                            class="h4 fw-bold text-primary mb-0">৳{{ number_format($addon['price'], 2) }}</span>
                        <small class="text-muted">/ {{ $addon['duration_days'] }}
                            {{ __('file.days') }}</small>
                    </div>
                </div>

                <button type="button"
                    class="btn btn-outline-primary w-100 fw-bold buy-addon-btn"
                    data-id="{{ $addon['id'] }}" onclick="makePayment({{ $addon['id'] }}, 'addon');">
                    <i class="fa fa-shopping-cart me-1"></i> {{ __('file.buy_now') }}
                </button>
            </div>
        </div>
    </div>
</div>
