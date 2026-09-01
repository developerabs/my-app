<?php

use App\Models\Currency;
use App\Models\landlord\PackageFeature;
use App\Models\landlord\TenantModule;
use App\Services\FeatureService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

if (!function_exists('tenant_tag')) {

    function tenant_tag(): string
    {
        return 'tenant_' . tenant('id');
    }
}

if (!function_exists('tenant_url_current')) {
    function tenant_url_current(string $route, array $params = [])
    {
        if (!tenant()) {
            return route('login');
        }

        $host = request()->getHost();

        if (!is_string($host)) {
            throw new \RuntimeException('Invalid host detected');
        }

        return tenant_route(
            tenant(),   // Tenant model
            $route,     // route name
            $params,
            $host       // ✅ ONLY STRING DOMAIN
        );
    }
}

if (!function_exists('getTenantActiveFeatureIds')) {
    function getTenantActiveFeatureIds($tenant)
    {
        $packageFeatureIds = PackageFeature::on('sherazipos_landlord')->where('package_id', $tenant->package_id)->pluck('feature_id')->toArray();

        $moduleFeatureIds = DB::connection('sherazipos_landlord')->table('features')
            ->whereIn('module_id', function ($q) use ($tenant) {
                $q->select('module_id')
                    ->from('tenant_modules')
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', 1)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', Carbon::now());
                    });
            })
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($packageFeatureIds, $moduleFeatureIds));
    }
}

if (!function_exists('getTenantAllowedPermissions')) {
    function getTenantAllowedPermissions($tenant)
    {
        $featureIds = getTenantActiveFeatureIds($tenant);

        return DB::connection('sherazipos_landlord')->table('feature_permissions')
            ->whereIn('feature_id', $featureIds)
            ->pluck('permission')
            ->toArray();
    }
}

if (!function_exists('generateProductItemCode')) {
    function generateProductItemCode()
    {
        $maxCode = 00001;
        $maxVarCode = 00015;

        $latestCode = max($maxCode, $maxVarCode);

        if (!$latestCode) {
            return '00001';
        }

        return str_pad($latestCode + 1, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('format_currency')) {
    /**
     * Universal Multi-Currency Formatter for PHP/Blade.
     * Supports: Currency Model, Array, Currency Code ('USD'), Symbol ('$'), or Default Base Currency.
     *
     * @param float|int|string|null $amount
     * @param mixed $currency - Currency Model, Array, Code ('USD'), Symbol ('$'), or null
     * @param int|null $customDecimals
     * @return string
     */
    function format_currency($amount, $currency = null, ?int $customDecimals = null): string
    {
        $settings = view()->shared('general_settings') ?? [];
        $defaultCurrency = view()->shared('default_currency') ?? [];

        $decimalDigits = $customDecimals ?? ($settings['decimal_digits'] ?? 2);
        $thousandSepSetting = $settings['thousand_separator'] ?? '';
        $currencyPosition = $settings['currency_position'] ?? 'left';
        $displayType = $settings['currency_display_type'] ?? 'symbol';

        // 🟢 Resolve Currency Symbol and Code dynamically
        $symbol = '$';
        $code = 'USD';

        if ($currency instanceof Currency) {
            $symbol = $currency->symbol ?? ($currency->code ?? '$');
            $code = $currency->code ?? 'USD';
        } elseif (is_array($currency)) {
            $symbol = $currency['symbol'] ?? ($currency['code'] ?? '$');
            $code = $currency['code'] ?? 'USD';
        } elseif (is_string($currency) && trim($currency) !== '') {
            $currency = trim($currency);
            if (strlen($currency) === 3 && ctype_alpha($currency)) {
                $code = strtoupper($currency);
                $symbol = $code;
            } else {
                $symbol = $currency;
                $code = $currency;
            }
        } else {
            // Fallback to System Base Currency
            $symbol = $defaultCurrency['symbol'] ?? ($defaultCurrency['code'] ?? '$');
            $code = $defaultCurrency['code'] ?? 'USD';
        }

        $displayUnit = ($displayType === 'code') ? $code : $symbol;
        $thousandSeparator = ($thousandSepSetting === 'space') ? ' ' : $thousandSepSetting;

        $numericAmount = is_numeric($amount) ? (float) $amount : 0.00;
        $formatted = number_format($numericAmount, (int) $decimalDigits, '.', $thousandSeparator);

        if ($currencyPosition === 'left') {
            return $displayUnit . ' ' . $formatted;
        } else {
            return $formatted . ' ' . $displayUnit;
        }
    }
}

if (!function_exists('formatDate')){
    function formatDate($date, $showTime = false){
        if(!$date) return 'N/A';
        $settings = view()->shared('general_settings');
        $dateFormat = $settings['date_format'] ?? 'd/m/Y';
        $timeType = $settings['time_format'] ?? '24';
        $timezone = $settings['timezone'] ?? 'asia/Dhaka';
        $carboneDate = Carbon::parse($date, 'UTC')->setTimezone($timezone);

        if($showTime){
            $dateFormat .= ($timeType === '12') ? ' g:i A' : ' H:i';
        }

        return $carboneDate->format($dateFormat);
    }
}

// if (!function_exists('get_sorted_units')) {
//    /**
//      * Calculates the true conversion ratio for product units and sorts them from largest to smallest.
//      * * @param array $unitDetails
//      * @return \Illuminate\Support\Collection
//      */
//     function get_sorted_units(array $unitDetails) {
//         $calculateRatio = function($unitId, $unitDetails) use (&$calculateRatio) {
//             if (!isset($unitDetails[$unitId])) return 1;
//             $unit = $unitDetails[$unitId];
//             $ratio = 1;

//             if (!empty($unit['is_formulaic']) && !empty($unit['formula'])) {
//                 $formula = $unit['formula'];
//                 $formula = preg_replace('/\bx\b/', '1', $formula);

//                 if (!empty($unit['user_vars'])) {
//                     foreach ($unit['user_vars'] as $key => $val) {
//                         $v = $val ?? 0;
//                         $formula = preg_replace('/\b' . preg_quote($key, '/') . '\b/', $v, $formula);
//                     }
//                 }

//                 try {
//                     $ratio = eval("return ($formula);") ?: 1;
//                 } catch (\Throwable $e) {
//                     $ratio = (float) ($unit['operator_val'] ?? 1);
//                 }
//             } else {
//                 $ratio = (float) ($unit['operator_val'] ?? 1);
//             }

//             if (($unit['operator'] ?? '') === '/') {
//                 if (empty($unit['is_formulaic'])) {
//                     $ratio = 1 / $ratio;
//                 }
//             }

//             if (!empty($unit['base_unit_id']) && isset($unitDetails[$unit['base_unit_id']])) {
//                 return $ratio * $calculateRatio($unit['base_unit_id'], $unitDetails);
//             }

//             return $ratio;
//         };

//         return collect($unitDetails)->sortByDesc(function($u) use ($calculateRatio, $unitDetails) {
//             return $calculateRatio($u['unit_id'], $unitDetails);
//         });
//     }
// }

if (!function_exists('get_sorted_units')) {
    /**
     * Calculates the true conversion ratio for product units using UnitFormulaService
     * and sorts them from largest scale (e.g. Master Carton) to base unit (e.g. Piece).
     *
     * @param array|string|null $unitDetails
     * @return \Illuminate\Support\Collection
     */
    function get_sorted_units(array|string|null $unitDetails): \Illuminate\Support\Collection
    {
        if (empty($unitDetails)) {
            return collect([]);
        }

        // 1. Safe JSON decoding if string is passed
        if (is_string($unitDetails)) {
            $unitDetails = json_decode($unitDetails, true) ?? [];
        }

        if (!is_array($unitDetails) || empty($unitDetails)) {
            return collect([]);
        }

        // 2. Resolve the central UnitFormulaService from Laravel Container
        $unitFormulaService = app(\App\Services\UnitFormulaService::class);

        // 3. Calculate true ratio using the service & sort descending safely
        return collect($unitDetails)
            ->map(function ($unit) use ($unitFormulaService, $unitDetails) {
                $unitId = $unit['unit_id'] ?? null;
                $ratio = $unitId ? (float) $unitFormulaService->getRatioFromJSON($unitDetails, $unitId) : 1.0;
                $unit['calculated_ratio'] = $ratio;
                return $unit;
            })
            ->sortByDesc('calculated_ratio')
            ->values(); // Reset array keys sequentially
    }
}

if (!function_exists('format_stock_with_unit')) {
    /**
     * Formats raw stock into compound units based on dynamic unit details and their individual precisions.
     *
     * @param float|int $totalStock
     * @param array $unitDetails
     * @return string
     */
    function format_stock_with_unit($totalStock, array $unitDetails) {
        $remainingQty = (float) $totalStock;

        // 1. Sort units from largest to smallest ratio
        $sortedUnits = get_sorted_units($unitDetails);

        if ($remainingQty <= 0 || $sortedUnits->isEmpty()) {
            $baseUnit = $sortedUnits->last();
            $baseUnitName = $baseUnit ? ($baseUnit['short_name'] ?? $baseUnit['name'] ?? 'Pcs') : 'Pcs';
            $basePrecision = isset($baseUnit['precision']) ? (int)$baseUnit['precision'] : 2; // Fallback to 2
            return number_format(0, $basePrecision) . ' ' . $baseUnitName;
        }

        // Recursive ratio calculation logic
        $calculateRatio = function($unitId, $unitDetails) use (&$calculateRatio) {
            if (!isset($unitDetails[$unitId])) return 1;
            $unit = $unitDetails[$unitId];
            $ratio = 1;

            if (!empty($unit['is_formulaic']) && !empty($unit['formula'])) {
                $formula = $unit['formula'];
                $formula = preg_replace('/\bx\b/', '1', $formula);

                if (!empty($unit['user_vars'])) {
                    foreach ($unit['user_vars'] as $key => $val) {
                        $v = $val ?? 0;
                        $formula = preg_replace('/\b' . preg_quote($key, '/') . '\b/', $v, $formula);
                    }
                }

                try {
                    $ratio = eval("return ($formula);") ?: 1;
                } catch (\Throwable $e) {
                    $ratio = (float) ($unit['operator_val'] ?? 1);
                }
            } else {
                $ratio = (float) ($unit['operator_val'] ?? 1);
            }

            if (($unit['operator'] ?? '') === '/') {
                if (empty($unit['is_formulaic'])) {
                    $ratio = 1 / $ratio;
                }
            }

            if (!empty($unit['base_unit_id']) && isset($unitDetails[$unit['base_unit_id']])) {
                return $ratio * $calculateRatio($unit['base_unit_id'], $unitDetails);
            }

            return $ratio;
        };

        $resultStrings = [];
        $totalUnitsCount = $sortedUnits->count();
        $currentIndex = 0;

        foreach ($sortedUnits as $unit) {
            $currentIndex++;
            
            // 2. Get true conversion ratio
            $ratio = (float) $calculateRatio($unit['unit_id'], $unitDetails);
            
            // Fetch individual unit precision dynamically (Default to 2 if not found)
            $unitPrecision = isset($unit['precision']) ? (int)$unit['precision'] : 2;

            if ($ratio > 0 && $remainingQty > 0) {
                // 3. Check if this is the ABSOLUTE SMALLEST unit in the sorted collection
                if ($currentIndex === $totalUnitsCount) {
                    // Use the unit's specific precision for the final rounding
                    $currentUnitQty = round($remainingQty / $ratio, $unitPrecision);
                    if ($currentUnitQty > 0) {
                        $resultStrings[] = "{$currentUnitQty} " . ($unit['short_name'] ?? $unit['name']);
                    }
                    $remainingQty = 0;
                } else {
                    // 4. For larger units, calculate quantity based on floor logic
                    $currentUnitQty = floor(round($remainingQty / $ratio, 7));
                    if ($currentUnitQty > 0) {
                        $resultStrings[] = "{$currentUnitQty} " . ($unit['short_name'] ?? $unit['name']);
                        $remainingQty -= $currentUnitQty * $ratio;
                        $remainingQty = round($remainingQty, 7); // Floating point precision fix
                    }
                }
            }
        }

        // Safety fallback if empty
        if (empty($resultStrings)) {
            $baseUnit = $sortedUnits->last();
            $baseUnitName = $baseUnit ? ($baseUnit['short_name'] ?? $baseUnit['name'] ?? 'Pcs') : 'Pcs';
            $basePrecision = isset($baseUnit['precision']) ? (int)$baseUnit['precision'] : 2;
            return number_format(0, $basePrecision) . ' ' . $baseUnitName;
        }

        // 5. Join with comma
        return implode(', ', $resultStrings);
    }
}

if (!function_exists('format_base_unit_stock')) {
    /**
     * Formats the stock quantity for a product based on its base unit.
     * * @param float $quantity
     * * @param array $unitDetails
     * * @param int $baseUnitId
     * * @return string
     */
    function format_base_unit_stock($quantity, $unitDetails, $baseUnitId)
    {
        $baseUnit = $unitDetails[$baseUnitId] ?? null;
        $baseUnitName = $baseUnit ? ($baseUnit['short_name'] ?? $baseUnit['name'] ?? 'Pcs') : 'Pcs';
        $basePrecision = isset($baseUnit['precision']) ? (int)$baseUnit['precision'] : 2;

        return number_format($quantity, $basePrecision) . ' ' . $baseUnitName;
    }
}

if (!function_exists('user_can_access_all_branches')) {
    /**
     * Check if a user has full unrestricted access to all company branches
     */
    function user_can_access_all_branches(?\App\Models\User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;

        // Super Admin role OR explicit 'access_all_branches' permission holder
        return $user->hasRole('Super Admin') || $user->can('access_all_branches');
    }
}

if (!function_exists('get_auth_permitted_branch_ids')) {
    /**
     * Get array of Branch UUIDs the authenticated user is allowed to access
     */
    function get_auth_permitted_branch_ids(): array
    {
        $user = auth()->user();
        if (!$user) return [];

        // 1. Global Branch Access Users
        if (user_can_access_all_branches($user)) {
            return \App\Models\Branch::active()->pluck('id')->toArray();
        }

        // 2. Branch-restricted Employees
        return $user->branches()->where('branches.is_active', true)->pluck('branches.id')->toArray();
    }
}

if (!function_exists('get_auth_permitted_branches')) {
    /**
     * Get Collection of Branch Models with their Currency and Default Account
     */
    function get_auth_permitted_branches()
    {
        $user = auth()->user();
        if (!$user) return collect([]);

        $query = \App\Models\Branch::active()->with(['currency', 'defaultAccount'])->orderBy('name');

        if (user_can_access_all_branches($user)) {
            return $query->get();
        }

        return $user->branches()->where('branches.is_active', true)->with(['currency', 'defaultAccount'])->orderBy('name')->get();
    }
}

if(!function_exists('is_feature_active')) {
    function is_feature_active($featureKey)
    {
        $sanitizedKey = strtolower(str_replace([' ', '-'], '_', $featureKey));
        
        if (!str_ends_with($sanitizedKey, '_active')) {
            $sanitizedKey .= '_active';
        }
        return FeatureService::getActive($sanitizedKey);
    }
}

if(!function_exists('unique_array')){
    function unique_array($array) {
        return array_values(array_unique(array_filter($array)));
    }
}