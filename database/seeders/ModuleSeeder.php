<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'key' => 'accounting',
                'name' => 'Accounting',
                'description' => 'Manage your business\'s financial transactions, including income, expenses, assets, liabilities, and equity.',
                'codebase_type' => 'internal',
                'sort_order' => 1,
                'icon' => 'fa-solid fa-coins',
                'prices' => [
                    'monthly' => 100,
                    'yearly' => 1000
                ]
            ],
            [
                'key' => 'hrm',
                'name' => 'HRM',
                'description' => 'Manage your business\'s human resources, including employees, departments, and positions.',
                'codebase_type' => 'internal',
                'sort_order' => 3,
                'icon' => 'fa-solid fa-users',
                'prices' => [
                    'monthly' => 100,
                    'yearly' => 1000
                ]
            ],
            [
                'key' => 'crm',
                'name' => 'CRM',
                'description' => 'Manage your business\'s customer relationships, including leads, contacts, and opportunities.',
                'codebase_type' => 'internal',
                'sort_order' => 4,
                'icon' => 'fa-solid fa-user-tie',
                'prices' => [
                    'monthly' => 100,
                    'yearly' => 1000
                ]
            ],
            [
                'key' => 'ecommerce',
                'name' => 'Ecommerce',
                'description' => 'Manage your business\'s online store, including products, categories, and orders.',
                'codebase_type' => 'external',
                'service_provider' => 'Modules\\Ecommerce\\Providers\\EcommerceServiceProvider',
                'base_namespace' => 'App\\Modules\\Ecommerce',
                'sort_order' => 5,
                'icon' => 'fa-solid fa-store',
                'prices' => [
                    'monthly' => 250,
                    'yearly' => 2500
                ]
            ],
            [
                'key' => 'manufacturing',
                'name' => 'Manufacturing',
                'description' => 'Manage your business\'s manufacturing operations, including production, inventory, and quality control.',
                'codebase_type' => 'external',
                'service_provider' => 'Modules\\Manufacturing\\Providers\\ManufacturingServiceProvider',
                'base_namespace' => 'App\\Modules\\Manufacturing',
                'sort_order' => 6,
                'icon' => 'fa-solid fa-industry',
                'prices' => [
                    'monthly' => 250,
                    'yearly' => 2500
                ]
            ],
            [
                'key' => 'marketing',
                'name' => 'Marketing',
                'description' => 'Manage your business\'s marketing activities, including campaigns, leads, and analytics.',
                'codebase_type' => 'internal',
                'sort_order' => 7,
                'icon' => 'fa-solid fa-bullhorn',
                'prices' => [
                    'monthly' => 100,
                    'yearly' => 1000
                ]
            ]
        ];

        DB::table('modules')->upsert(
            array_map(fn($module) => [
                'key'         => $module['key'],
                'name'        => $module['name'],
                'is_active'   => true,
                'description' => $module['description'],
                'codebase_type' => $module['codebase_type'],
                'service_provider' => $module['service_provider'] ?? null,
                'base_namespace' => $module['base_namespace'] ?? null,
                'sort_order'  => $module['sort_order'],
                'meta'        => json_encode(['pricing' => $module['prices']]),
                'icon'        => $module['icon']
            ], $modules),
            ['key'], 
            ['name', 'description', 'sort_order', 'meta', 'icon', 'is_active'] // আপডেট হবে যেসব কলাম
        );
    }
}
