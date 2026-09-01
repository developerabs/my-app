<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ValidCurrencyCode implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validCodes = Cache::tags([landlord_tag()])->remember('valid_currency_codes', 86400, function () {
            $ratesJson = DB::connection('sherazipos_landlord')
                ->table('currency_rates')
                ->value('rates');
            
            if(!$ratesJson) return [];
            $rates = json_decode($ratesJson, true);
            return array_keys($rates);
        });

        if (!in_array(strtoupper($value), $validCodes)) {
            $fail('The selected :attribute is not a valid currency code.');
        }
    }
}
