<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'manage users',     // Admin only
            'manage products',  // Admin only
            'process sales',    // Admin & Cashier
            'view reports',     // Admin only
            'manage settings',  // Admin only
            'view own orders',  // Customer
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $cashier = Role::firstOrCreate(['name' => 'Cashier']);
        $customer = Role::firstOrCreate(['name' => 'Customer']);

        $admin->syncPermissions($permissions);
        $cashier->syncPermissions([ 'process sales']);
        $customer->syncPermissions(['view own orders']);
    }
}
