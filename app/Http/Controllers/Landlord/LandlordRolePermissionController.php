<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LandlordRolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        $permissions = Permission::all();
        return view('landlord.dashboard.role-permission.roles', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);
        return redirect()->route('landlord.roles-permissions')->with('success', 'Role created successfully.');
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
                ->route('landlord.roles-permissions')
                ->with('success', 'Role updated successfully.');
        } catch (ModelNotFoundException $e) {
            return back()->with('error', 'Role not found.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating role: ' . $e->getMessage());
        }
    }

    public function managePermissions(Role $role)
    {
        $permissions = Permission::all()->groupBy(function ($perm) {
            return ucfirst(explode('_', $perm->name)[0]); // Capitalize first letter
        });
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('landlord.dashboard.role-permission.permissions', compact('role', 'permissions', 'rolePermissions'));
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
                ->route('landlord.roles-permissions')
                ->with('success', 'Permissions updated successfully for role: ' . $role->name);
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating permissions: ' . $e->getMessage());
        }
    }
    public function destroy(Role $role)
    {
        try {
            $role->delete();
            return redirect()->back()->with('success', $role->name .'Role deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting role: ' . $e->getMessage());
        }
    }
}
