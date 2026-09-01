<?php

namespace Database\Seeders;

use App\Models\landlord\Currency;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class LandlordUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = [
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'sheraziit.internal@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'remember_token' => Str::random(10),
            'phone' => '01819011229',
            'company_name' => 'sheraziit',
            'role_id' => 1,
            'reseller_id' => 0,
            'is_active' => 1
        ];

        if (!User::where('email', $user['email'])->exists()) {
            User::create($user)->assignRole('Super-Admin');
        }

        $currency = [
            'code' => 'BDT',
            'name' => 'Bangladeshi Taka',
            'symbol' => '৳',
            'is_active' => 1,
            'is_base' => 1
        ];
        if (!Currency::where('code', $currency['code'])->exists()) {
            Currency::create($currency);
        }

        Artisan::call('currency:update-rates');
    }
}
