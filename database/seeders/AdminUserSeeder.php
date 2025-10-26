<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $existingAdmin = DB::table('users')->where('email', 'admin@sekhin1999.com')->first();
        
        if (!$existingAdmin) {
            // Create admin user
            $userId = DB::table('users')->insertGetId([
                'name' => 'Admin User',
                'email' => 'admin@sekhin1999.com',
                'password' => Hash::make('admin@sekhin1999'),
                'user_type' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Create profile for admin user
            DB::table('profiles')->insert([
                'user_id' => $userId,
                'phone' => '+885 4567890',
                'address' => 'Admin Address',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            echo "Admin user created successfully!\n";
            echo "Email:admin@sekhin1999.com\n";
            echo "Password: admin@sekhin1999\n";
            echo "You can change these credentials after logging in.\n";
        } else {
            echo "Admin user already exists.\n";
        }
    }
}