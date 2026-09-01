@php
    $selectedId = $selectedId ?? ($default_currency['id'] ?? null);
    $currencies = $currencies ?? [];
    $selectedCurrency = collect($currencies)->firstWhere('id', $selectedId);
    $exchangeRate = $rate ?? ($selectedCurrency->rate ?? 1);
@endphp

<div class="w-100">
    <label class="form-label fw-bold">{{ __('file.field.currencynrate') }}</label>
    
    <div class="input-group input-group-sm currency-group" style="flex-wrap: nowrap;">
        <!-- Currency Select -->
        <select name="currency" 
            class="form-select currency-select" 
            style="flex: 0 0 40%; border-right: none; background-color: #f8f9fa;"
            onchange="this.closest('.currency-group').querySelector('.rate-input').value = this.options[this.selectedIndex].getAttribute('data-rate') || 1">
            
            @foreach ($currencies as $currency)
                <option value="{{ $currency->id }}" 
                    data-rate="{{ $currency->rate }}"
                    {{ $currency->id == $selectedId ? 'selected' : '' }}>
                    {{ $currency->code }}
                </option>
            @endforeach
        </select>

        <!-- Exchange Rate Input -->
        <input type="number" 
            name="exchange_rate"
            id="exchange_rate" 
            class="form-control rate-input" 
            value="{{ $exchangeRate }}" 
            step="any" 
            placeholder="Rate"
            style="border-left: 1px solid #dee2e6;">
    </div>
</div>