<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LandlordRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard_view',
            'client_view',
            'client_create',
            'client_edit',
            'client_delete',
            'client_dues',
            'client_notes',
            'proposal_view',
            'proposal_create',
            'proposal_store',
            'proposal_edit',
            'proposal_update',
            'proposal_delete',
            'note_view',
            'note_create',
            'note_edit',
            'note_delete',
            'reseller_view',
            'reseller_create',
            'reseller_edit',
            'reseller_delete',
            'reseller_dues',
            'payment_view',
            'payment_create',
            'payment_edit',
            'payment_delete',
            'payment_approval',
            'report_sale',
            'report_profit',
            'report_expense',
            'report_payment',
            'report_cashflow',
            'manage_user',
            'manage_role',
            'manage_package',
            'manage_feature',
            'manage_module',
            'manage_addons',
            'manage_cms',
            'manage_general_setting',
            'manage_email_setting',
            'manage_payment_setting',
            'manage_sms_setting',
            'manage_theme_setting',
            'manage_seo_setting',
            'manage_analytics_setting',
            'manage_ai_setting',
            'manage_database_backup',
            'manage_database_update',
            'manage_language',
            'manage_currency',
        ];

        $permissionData = array_map(fn($p) => [
            'name' => $p,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now()
        ], $permissions);

        Permission::insertOrIgnore($permissionData);

        $roles = ['Super-Admin', 'Accounts', 'Editor', 'Reseller'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $allPermissions = Permission::all();
        Role::where('name', 'Super-Admin')->first()->syncPermissions($allPermissions);

        $limitedPermissions = $allPermissions->reject(function ($permission) {
            return str_contains($permission->name, 'delete') || str_contains($permission->name, 'manage');
        });

        Role::where('name', 'Accounts')->first()->syncPermissions($limitedPermissions);
        Role::where('name', 'Editor')->first()->syncPermissions($limitedPermissions);

        $resellerPermissions = [
            'dashboard_view',
            'client_view',
            'client_create',
            'client_dues',
            'client_notes',
            'proposal_view',
            'proposal_create',
            'proposal_store',
            'proposal_edit',
            'proposal_update',
            'proposal_delete',
            'note_view',
            'note_create',
            'note_edit',
            'note_delete',
            'payment_view',
            'report_sale',
            'report_profit',
        ];
        Role::where('name', 'Reseller')->first()->syncPermissions($resellerPermissions);
    }
}
