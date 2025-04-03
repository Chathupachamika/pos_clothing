<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Admin;

class AdminController extends Controller
{
    public function store($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Check if the user is already an admin
        if ($user->admin) {
            return response()->json(['message' => 'User is already an admin'], 400);
        }

        Admin::create(['user_id' => $user->id]);

        return response()->json(['message' => 'User promoted to admin']);
    }

    public function removeAdmin($userId)
    {
        $admin = Admin::where('user_id', $userId)->first();

        if (!$admin) {
            return response()->json(['message' => 'User is not an admin'], 400);
        }

        $admin->delete();

        return response()->json(['message' => 'User removed from admin']);
    }
}
