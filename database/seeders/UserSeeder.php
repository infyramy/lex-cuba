<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        $adminEmail    = env('ADMIN_EMAIL', 'admin@abc.com');
        $adminPassword = env('ADMIN_PASSWORD', 'abc123');
        $adminName     = env('ADMIN_NAME', 'Administrator');

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name'      => $adminName,
                'password'  => Hash::make($adminPassword),
                'is_active' => true,
                'role'      => 'admin',
                'role_id'   => $adminRole->id,
            ]
        );
    }
}
