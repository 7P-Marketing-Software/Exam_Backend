<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $AdminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $superAdminUser = User::updateOrCreate([
            'email' => 'shokry@gmail.com',
        ], [
            'name' => 'shokry',
            'phone' => '01014001055',
            'password' => bcrypt('123456789'),
        ]);


        $superAdminUser->assignRole($AdminRole);
    }
}
