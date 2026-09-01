<?php

namespace Database\Seeders;

use App\Models\landlord\FeaturePermission;
use Illuminate\Container\Attributes\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = FeaturePermission::on('sherazipos_landlord')
            ->pluck('permission')
            ->unique()
            ->toArray();

        $othersPermissions = [
            'manage_general_settings',
            'manage_email_settings',
            'manage_currency_settings',
            'manage_analytics_settings',
            'manage_ai_settings',
            'manage_role',
            'manage_user',
            'manage_local_db',
            'manage_store_purchase',
            'manage_trash',
            'manage_custom_fields',
            'manage_attributes',
        ];

        $permissions = array_merge($permissions, $othersPermissions);

        $permissionData = array_map(fn($name) => [
            'name'       => $name,
            'guard_name' => 'web', 
            'created_at' => now(),
            'updated_at' => now(),
        ], $permissions);

        Permission::insertOrIgnore($permissionData);

        $roles = ['Super Admin', 'Admin'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }


        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $allPermissionNames = Permission::pluck('name');
            $adminRole->syncPermissions($allPermissionNames);
        }
    }
}
