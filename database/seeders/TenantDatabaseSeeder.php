<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        

        $this->call([
                // TenantRolePermissionSeeder::class,
                CurrencySeeder::class,
                TenantInitialSeeder::class,
                ChartOfAccountSeeder::class,
                SystemAccountSeeder::class,
            ]);
    }
}
