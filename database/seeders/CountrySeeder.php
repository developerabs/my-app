<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = file_get_contents(public_path('json/CountryCodes.json'));
        $countries = json_decode($json); // এটি অবজেক্ট রিটার্ন করছে

        foreach ($countries as $country) {
            DB::table('countries')->updateOrInsert(
                ['code' => $country->code], // ডট বা অ্যারো চিহ্ন ব্যবহার হবে
                [
                    'name' => $country->name,
                    'dial_code' => $country->dial_code,
                ]
            );
        }
    }
}
