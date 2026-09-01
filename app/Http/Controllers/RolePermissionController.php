<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        $permissions = Permission::all();

        return view('backend.role-permission.roles', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);
        return redirect()->route('roles-permissions')->with('success', 'Role created successfully.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($request->role_id),
            ],
        ]);

        try {
            $role = Role::findOrFail($validated['role_id']);
            $role->update(['name' => $validated['name']]);

            return redirect()
                ->route('roles-permissions')
                ->with('success', 'Role updated successfully.');
        } catch (ModelNotFoundException $e) {
            return back()->with('error', 'Role not found.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating role: ' . $e->getMessage());
        }
    }

    // public function managePermissions(Role $role)
    // {
    //     $allowdPermissions = getTenantAllowedPermissions(tenant());
    //     $permissions = Permission::whereIn('name', $allowdPermissions)
    //         ->get()
    //         ->groupBy(function ($perm) {
    //             return ucfirst(explode('_', $perm->name)[0]); // Capitalize first letter
    //         });
    //     $rolePermissions = $role->permissions->pluck('name')->toArray();
    //     return view('backend.role-permission.permissions', compact('role', 'permissions', 'rolePermissions'));
    // }

    public function managePermissions(Role $role)
    {
        $allowdPermissions = getTenantAllowedPermissions(tenant());
        $defaultPermissions = [
            'access_all_branches',
            'manage_general_settings',
            'manage_email_settings',
            'manage_analytics_settings',
            'manage_ai_settings',
            'manage_role',
            'manage_user',
            'manage_local_db',
            'manage_currency_settings',
            'manage_store_purchase',
            'manage_trash',
            'manage_custom_fields',
            'manage_attributes',
        ];

        $allowdPermissions = array_merge($allowdPermissions, $defaultPermissions);

        // মডিউল প্রিফিক্স এবং তাদের পূর্ণ নাম এখানে ডিফাইন করুন
        $moduleMap = [
            'acc'      => 'Accounting',
            'crm'      => 'Customer Relation Management (CRM)',
            'hrm'      => 'Human Resource (HRM)',
            'products' => 'Product Management',
            'pos'      => 'Point of Sale',
            'access' => 'Branch & Global Access',
        ];

        $permissions = Permission::whereIn('name', $allowdPermissions)
            ->get()
            ->map(function ($permission) use ($moduleMap) {
                $name = $permission->name;
                $parts = explode('_', $name);
                $prefix = strtolower($parts[0]);

                // Set group name
                $permission->group_full_name = $moduleMap[$prefix] ?? ucfirst($prefix);

                // Set Permission Label
                $shortPrefixes = ['acc', 'crm', 'hrm'];
                if (in_array($prefix, $shortPrefixes)) {
                    // acc_accounts_view -> Accounts View
                    $cleanLabel = substr($name, strpos($name, '_') + 1);
                } else {
                    // products_view -> Products View
                    $cleanLabel = $name;
                }

                $permission->display_custom_name = Str::title(str_replace('_', ' ', $cleanLabel));
                return $permission;
            })
            ->groupBy('group_full_name')
            ->sortKeys();

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('backend.role-permission.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    public function assignPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        try {
            $role->syncPermissions($validated['permissions'] ?? []);
            return redirect()
                ->route('roles-permissions')
                ->with('success', 'Permissions updated successfully for role: ' . $role->name);
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating permissions: ' . $e->getMessage());
        }
    }
    public function destroy(Role $role)
    {
        try {
            $role->delete();
            return redirect()->back()->with('success', $role->name . 'Role deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting role: ' . $e->getMessage());
        }
    }
}
