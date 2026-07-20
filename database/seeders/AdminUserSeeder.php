<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin233@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin233'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        
        // Random users
        User::factory(10)->create();
    }
}
