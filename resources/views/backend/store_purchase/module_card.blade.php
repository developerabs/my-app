<div class="col-lg-2 col-md-3 col-12 mb-4">
    <div class="card h-100 shadow-sm border-0 clickable-card @if($item['owned'] && $item['owned_type'] == 'package') opacity-50 @endif" onclick="viewDetials({{ $item['id'] }})">
        <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between  mb-3">
                <i class="{{ $item['icon'] }} fa-2x me-2"></i>
                <h5 class="mb-0">{{ $item['name'] }}</h5>
            </div>
            <p class="mb-2">{{ $item['description'] }}</p>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">Yearly: {{ number_format($item['meta']['pricing']['yearly'], 2) }}</span>
                <span class="text-muted small">Monthly: {{ number_format($item['meta']['pricing']['monthly'], 2) }}</span>
            </div>
            <div class="mt-3">
                @if($item['owned'] && $item['owned_type'] == 'package' && $item['owned_is_active'])
                <button type="button" class="btn btn-success w-100 fw-bold" disabled>
                    <i class="fa fa-check-circle me-1"></i> {{ __('file.included_with_package') }}
                </button>
                @elseif($item['owned'] && $item['owned_type'] == 'addon' && $item['owned_is_active'])
                <button type="button" class="btn btn-warning w-100 fw-bold renew-module-btn" data-id="{{ $item['id'] }}" onclick="event.stopPropagation(); makePayment({{ $item['id'] }}, 'module', true);">
                    <i class="fa fa-sync me-1"></i> {{ __('file.renew_now') }}
                </button>
                @else
                <button type="button" class="btn btn-primary w-100 fw-bold buy-module-btn"  onclick="event.stopPropagation(); makePayment({{ $item['id'] }}, 'module');" data-id="{{ $item['id'] }}">
                    <i class="fa fa-shopping-cart me-1"></i> {{ __('file.buy_now') }}
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

