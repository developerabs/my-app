<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AddressService
{
    public function search($query, $provider = 'osm', $apiKey = null)
    {
        return match ($provider) {
            'osm' => $this->searchOSM($query),
            //'mapbox' => $this->searchMapbox($query, $apiKey),
            default => throw new \InvalidArgumentException("Unsupported provider: $provider"),
        };
    }

    protected function searchOSM($query)
    {
        // English: Call Nominatim API from backend
        $response = Http::withHeaders(['User-Agent' => 'SheraziPOS'])
            ->get("https://nominatim.openstreetmap.org/search", [
                'q' => $query,
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 5
            ]);

        return $this->formatOSM($response->json());
    }

    // protected function formatOSM($data)
    // {
    //     // English: Normalize the data so frontend always gets the same structure
    //     return array_map(function ($item) {
    //         $addr = $item['address'] ?? [];

    //         // English: General mapping that works for most countries
    //         $division = $addr['state'] ?? $addr['region'] ?? $addr['province'] ?? '';

    //         // English: For BD state_district is District, for others it could be City/County
    //         $district = $addr['state_district'] ?? $addr['city'] ?? $addr['town'] ?? $addr['county'] ?? '';

    //         return [
    //             'display_name' => $item['display_name'],
    //             'division'     => $division,
    //             'district'     => $district,
    //             'upazila'      => $addr['suburb'] ?? $addr['village'] ?? $addr['municipality'] ?? $addr['neighborhood'] ?? $addr['city_district'] ?? '',
    //             'latitude'     => $item['lat'],
    //             'longitude'    => $item['lon'],
    //             'city'         => $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? '',
    //             'state'        => $addr['state'] ?? '',
    //             'post_code'    => $addr['postcode'] ?? '',
    //             'country'      => $addr['country'] ?? '',
    //             'country_code' => strtoupper($addr['country_code'] ?? ''),
    //         ];
    //     }, $data);
    // }

    protected function formatOSM($data)
    {
        return array_map(function ($item) {
            $addr = $item['address'] ?? [];
            $countryCode = strtolower($addr['country_code'] ?? '');

            // ১. বিভাগ (Division/State)
            $division = $addr['state'] ?? $addr['province'] ?? $addr['region'] ?? '';

            // ২. জেলা (District)
            // বাংলাদেশের জন্য state_district সবচেয়ে সঠিক। 
            // যদি না থাকে, তবে গ্লোবাল ক্ষেত্রে city বা county জেলা হিসেবে কাজ করে।
            $district = $addr['state_district'] ?? '';
            if (empty($district) && $countryCode === 'bd') {
                // বাংলাদেশের ক্ষেত্রে অনেক সময় city ফিল্ডে জেলার নাম থাকে
                $district = $addr['city'] ?? $addr['town'] ?? '';
            } elseif (empty($district)) {
                $district = $addr['city'] ?? $addr['county'] ?? '';
            }

            // ৩. উপজেলা (Upazila/County)
            // বাংলাদেশে 'county' কী-তে সবসময় উপজেলা থাকে। 
            $upazila = $addr['county'] ?? $addr['municipality'] ?? '';

            // যদি উপজেলা খালি থাকে এবং দেশ বাংলাদেশ হয়, তবে suburb/village চেক করবে
            if (empty($upazila) && $countryCode === 'bd') {
                $upazila = $addr['suburb'] ?? $addr['village'] ?? '';
            }

            // ৪. শহর/এলাকা (City/Area)
            $cityArea = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['suburb'] ?? '';

            return [
                'display_name' => $item['display_name'],
                'division'     => $this->cleanSuffix($division),
                'district'     => $this->cleanSuffix($district),
                'upazila'      => $this->cleanSuffix($upazila),
                'city'         => $this->cleanSuffix($cityArea),
                'state'        => $division, // Raw state for fallback
                'post_code'    => $addr['postcode'] ?? '',
                'country'      => $addr['country'] ?? '',
                'country_code' => strtoupper($countryCode),
                'latitude'     => $item['lat'],
                'longitude'    => $item['lon'],
            ];
        }, $data);
    }

    /**
     * English: Clean unnecessary suffixes from address components
     */
    private function cleanSuffix($string)
    {
        if (empty($string)) return '';

        // English: Remove common redundant words
        $replacements = [
            ' District',
            ' Division',
            ' Upazila',
            ' জেলা',
            ' বিভাগ',
            ' উপজেলা'
        ];

        return trim(str_replace($replacements, '', $string));
    }
}
