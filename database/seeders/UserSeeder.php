<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lambanggelun.id'],
            [
                'name' => 'Admin Desa Lambanggelun',
                'role' => 'admin',
                'password' => 'password123',
            ]
        );

        User::updateOrCreate(
            ['email' => 'operator@lambanggelun.id'],
            [
                'name' => 'Operator Desa Lambanggelun',
                'role' => 'operator',
                'password' => 'password123',
            ]
        );
    }
}
