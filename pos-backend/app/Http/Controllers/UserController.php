<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->syncRoles([$request->role]);

        return response()->json(['message' => 'Role assigned successfully.']);
    }

    public function assignPermission(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|exists:permissions,name'
        ]);

        $user = User::findOrFail($request->user_id);
        $user->givePermissionTo($request->permission);

        return response()->json(['message' => 'Permission assigned successfully.']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return response()->json(['message' => 'User created successfully.']);
    }

    public function isAdmin(Request $request)
    {
        if (Gate::allows('is-admin')) {
            return response()->json([
                'is_admin' => true,
                'message' => 'User is an admin'
            ]);
        } else {
            return response()->json([
                'is_admin' => false,
                'message' => 'User is not an admin'
            ]);
        }
    }

    public function checkAuth()
    {
        if (Gate::allows('is-admin')) {
            return response()->json([
                'message' => 'User is an admin.',
                'result' => true,
            ]);
        } else {
            return response()->json(['message' => 'User is not an admin.', 'result' => false]);
        }
    }
}
