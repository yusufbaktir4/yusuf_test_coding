<?php

namespace Database\Seeders;

use App\Models\RoleMaster;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('123456'),
            'created_by' => 0
        ]);

        RoleMaster::insert([
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'created_by' => 0
            ],
            [
                'name' => 'Employee',
                'slug' => 'employee',
                'created_by' => 0
            ],
        ]);
    }
}
