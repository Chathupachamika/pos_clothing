<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\json;

class RoleController extends Controller
{
    public function index(){
        $roles = Role::with('permissions')->get();
        return response()->json($roles);
    }

    public function store(Request $request){
        $request->validate([
            'name'=>'required|unique:roles,name',
            'permission'=>'array'
        ]);

        $role = Role::create(['name'=>$request->name, 'guard_name' => 'web']);
        if ($request->has('permission')) {
            foreach ($request->permission as $perm) {
                $permission = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
                $role->givePermissionTo($permission);
            }
        }

        return response()->json(['message'=>'Role Created Successfully', 'role'=>$role]);
    }

    public function destory(Request $request){
        $rolen = Role::where('name', $request->role)->first();
        if (!$rolen) {
            return response()->json(['message' => 'Role not found'], 404);
        }
        $rolen->delete();
        return response()->json(['message' => 'Role deleted successfully.']);
    }
}
