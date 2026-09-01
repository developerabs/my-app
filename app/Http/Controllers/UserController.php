<?php

namespace App\Http\Controllers;

use App\DataTables\UserDataTable;
use App\Models\Branch;
use App\Models\User;
use App\Rules\UniqueWithTrashCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(UserDataTable $dataTable)
    {
        $roles = Role::whereNotIn('name', ['Super Admin'])->get();
        $branches = Cache::tags([tenant_tag()])->remember('all_active_branches_' . tenant('id'), 3600, fn() => Branch::active()->get());

        return $dataTable->render('backend.users.index', compact('roles', 'branches'));
    }

    public function store(Request $request)
    {
        // 1. Guard Check for Super Admin
        if (in_array('Super Admin', $request->roles ?? [])) {
            return response()->json([
                'error' => 'You cannot assign the Super Admin role.'
            ], 403);
        }

        // 2. Validation rules
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'username'     => ['required', 'string', 'max:255', new UniqueWithTrashCheck(User::class, 'username')],
            'email'        => ['required', 'string', 'email', 'max:255', new UniqueWithTrashCheck(User::class, 'email')],
            'phone_number' => 'required|string|max:20',
            'password'     => 'required|string|min:8|confirmed',
            'roles'        => ['required', 'array', 'min:1'],
            'roles.*'      => ['required', 'string', 'exists:roles,name'],
            'branch_ids'   => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['required', 'exists:branches,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'name'         => $request->name,
                'username'     => $request->username,
                'email'        => $request->email,
                'phone'        => $request->phone_number,
                'password'     => Hash::make($request->password),
            ]);

            // Assign Multiple Roles (Spatie)
            if ($request->filled('roles')) {
                $user->syncRoles($request->roles);
            }

            // Assign Multiple Branches
            $user->branches()->sync($request->branch_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); 
            Log::error('User creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user. Please try again.'
            ], 500);
        }
    }

    public function edit(User $user)
    {
        if ($user->hasRole('Super Admin')) {
            return response()->json(['error' => 'You cannot edit a Super-Admin user.'], 403);
        }
        
        $roles = Role::whereNotIn('name', ['Super Admin'])->get();
        
        // Fetch all assigned role names as an array
        $userRoles = $user->roles()->pluck('name')->toArray();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();

        return response()->json([
            'user'          => $user,
            'user_roles'    => $userRoles,    // Array of assigned role names
            'roles'         => $roles,
            'user_branches' => $userBranches, // Array of assigned branch UUIDs
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->hasRole('Super Admin')) {
            return response()->json(['error' => 'You cannot update a Super-Admin user.'], 403);
        }
        if (in_array('Super Admin', $request->roles ?? [])) {
            return response()->json(['error' => 'You cannot assign the Super Admin role.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'username'     => ['required', 'string', 'max:255', new UniqueWithTrashCheck(User::class, 'username', $user->id)],
            'email'        => ['required', 'string', 'email', 'max:255', new UniqueWithTrashCheck(User::class, 'email', $user->id)],
            'phone_number' => 'required|string|max:20',
            'roles'        => ['required', 'array', 'min:1'],
            'roles.*'      => ['required', 'string', 'exists:roles,name'],
            'branch_ids'   => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['exists:branches,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user->update([
                'name'     => $request->name,
                'username' => $request->username,
                'email'    => $request->email,
                'phone'    => $request->phone_number,
                'password' => $request->filled('password') ? Hash::make($request->password) : $user->password,
            ]);

            // Sync Multiple Roles
            if ($request->filled('roles')) {
                $user->syncRoles($request->roles);
            } else {
                $user->syncRoles([]);
            }

            // Sync Branches
            $user->branches()->sync($request->branch_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User and branches updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('Super Admin')) {
            return response()->json([
                'error' => 'You cannot delete a Super-Admin user.'
            ], 403);
        }
        try {
            $user->delete();
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No users selected.'], 400);
        }

        try {
            $users = User::whereIn('id', $ids)->get()->filter(function ($user) {
                return !$user->hasRole('Super Admin');
            });

            if ($users->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No deletable users found.'], 400);
            }

            foreach ($users as $user) {
                $user->delete();
            }

            return response()->json([
                'success' => true,
                'message' => count($users) . ' users deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!'
            ], 500);
        }
    }
}