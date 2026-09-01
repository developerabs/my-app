<?php

namespace App\Http\Controllers\Landlord;

use App\DataTables\Landlord\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class LandlordUserController extends Controller
{
    public function index(UsersDataTable $dataTable)
    {
        $roles = Role::whereNotIn('name', ['Super-Admin', 'Reseller'])->get(); // Fetch roles excluding super-admin and reseller
        return $dataTable->render('landlord.dashboard.users.index', compact('roles'));
    }

    public function store(Request $request)
    {
        if ($request->role == 'Super-Admin') {
            return response()->json([
                'error' => 'You cannot assign the Super-Admin role.'
            ], 403);
        }
        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone_number' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed', // Laravel confirmed rule expects password_confirmation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone_number,
                'password' => Hash::make($request->password), // hash password
            ]);

            // Assign role (Spatie)
            if ($request->role) {
                $user->assignRole($request->role);
            }

            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
            ], 201);

        } catch (\Exception $e) {
            // Log the error for debugging (optional)
            Log::error('User creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function edit(User $user)
    {
        if ($user->hasRole('Super-Admin')) {
            return response()->json([
                'error' => 'You cannot edit a Super-Admin user.'
            ], 403);
        }
        $roles = Role::whereNotIn('name', ['Super-Admin', 'Reseller'])->get();
        $userRole = $user->roles()->first();
        $user['role'] = $userRole ? $userRole->name : null;
        return response()->json([
            'user' => $user,
            'roles' => $roles
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->hasRole('Super-Admin')) {
            return response()->json([
                'error' => 'You cannot update a Super-Admin user.'
            ], 403);
        }
        if ($request->role == 'Super-Admin') {
            return response()->json([
                'error' => 'You cannot assign the Super-Admin role.'
            ], 403);
        }
        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update user details
            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email;
            $user->phone = $request->phone_number;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password); // hash new password
            }

            $user->save();

            // Sync role (Spatie)
            if ($request->role) {
                $user->roles()->detach();
                $user->assignRole($request->role);
            } else {
                $user->roles()->detach(); // Remove all roles if none selected
            }

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
            ], 200);

        } catch (\Exception $e) {
            // Log the error for debugging (optional)
            //Log::error('User update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('Super-Admin')) {
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
}
