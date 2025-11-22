<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();

        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Superadmin sees all, Admin sees admins and regulars (but logic below simplifies to all for now, 
        // or we can filter if needed. Let's show all but restrict actions in update/delete)
        return User::all();
    }

    public function store(Request $request)
    {
        $currentUser = $request->user();

        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['superadmin', 'admin', 'regular'])],
        ]);

        // Only superadmin can create superadmins or admins
        if (($request->role === 'superadmin' || $request->role === 'admin') && !$currentUser->isSuperAdmin()) {
             return response()->json(['message' => 'Only Superadmin can create admins'], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $currentUser = $request->user();

        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Prevent admin from modifying superadmin
        if ($user->isSuperAdmin() && !$currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(['superadmin', 'admin', 'regular'])],
            'password' => 'sometimes|string|min:8',
        ]);

        // Only superadmin can assign admin/superadmin roles
        if ($request->has('role')) {
             if (($request->role === 'superadmin' || $request->role === 'admin') && !$currentUser->isSuperAdmin()) {
                 return response()->json(['message' => 'Only Superadmin can assign admin roles'], 403);
             }
        }

        $data = $request->only(['name', 'email', 'role']);
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user();

        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Prevent admin from deleting superadmin
        if ($user->isSuperAdmin()) {
             return response()->json(['message' => 'Cannot delete Superadmin'], 403);
        }
        
        // Prevent admin from deleting other admins (optional, but safer)
        if ($user->isAdmin() && !$currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Only Superadmin can delete admins'], 403);
        }

        $user->delete();

        return response()->noContent();
    }
}
