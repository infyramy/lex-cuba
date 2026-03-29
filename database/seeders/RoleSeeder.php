<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'description' => 'Full system access — manages all platform features',
                'permissions' => Permission::all(),
            ]
        );

        Role::updateOrCreate(
            ['name' => 'content_manager'],
            [
                'description' => 'Manages content and members, but not admin users, roles, packages, or system settings',
                'permissions' => Permission::contentManagerPermissions(),
            ]
        );
    }
}
