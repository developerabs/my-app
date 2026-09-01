<?php

namespace App\Console\Commands;

use App\Models\landlord\CurrencyRate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateCurrencyRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest currency rates from external API and update Landlord database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Fetching latest currency rates for Landlord Central System...');

        $baseCurrency = 'BDT';
        $apiKey = config('services.exchangerate_api.key') ?? env('EXCHANGE_RATE_API_KEY');

        if (empty($apiKey)) {
            $this->error('EXCHANGE_RATE_API_KEY is missing in your .env or configuration.');
            return self::FAILURE;
        }

        try {
            $response = Http::timeout(15)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCurrency}");

            if ($response->successful()) {
                $data = $response->json();

                $rates = $data['conversion_rates'] ?? [];
                if (empty($rates)) {
                    $this->error('Received empty conversion rates from ExchangeRate-API.');
                    return self::FAILURE;
                }

                // 🟢 Extract exact publication timestamp from API
                $sourceTimestamp = !empty($data['time_last_update_unix'])
                    ? Carbon::createFromTimestamp($data['time_last_update_unix'])->format('Y-m-d H:i:s')
                    : Carbon::parse($data['time_last_update_utc'])->format('Y-m-d H:i:s');

                CurrencyRate::updateOrCreate(
                    ['base_code' => $data['base_code'] ?? $baseCurrency],
                    [
                        'last_updated_at' => $sourceTimestamp,
                        'rates'           => $rates,
                    ]
                );

                // Clear Landlord Cache
                Cache::tags([landlord_tag()])->forget('daily_currency_rates_all');
                Cache::tags([landlord_tag()])->forget('daily_currency_rates');

                $this->info("Currency rates updated successfully. Official Source Timestamp: {$sourceTimestamp}");
                return self::SUCCESS;
            }

            $this->error('Failed to fetch currency rates. API HTTP Status: ' . $response->status());
            return self::FAILURE;

        } catch (\Throwable $e) {
            Log::error('UpdateCurrencyRates Command Failed: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}