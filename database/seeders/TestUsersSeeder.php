<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Sale Staff User
        $saleStaff = User::firstOrCreate(
            ['email' => 'sale@inventory.com'],
            [
                'name' => 'Sale Staff',
                'password' => Hash::make('password'),
                'user_type' => 'sales',
            ]
        );
        
        Profile::firstOrCreate(
            ['user_id' => $saleStaff->id],
            [
                'phone' => '+855123456789',
                'address' => '123 Sale Street',
            ]
        );

        // Create Inventory Staff User
        $inventoryStaff = User::firstOrCreate(
            ['email' => 'inventory@inventory.com'],
            [
                'name' => 'Inventory Staff',
                'password' => Hash::make('password'),
                'user_type' => 'inventory',
            ]
        );
        
        Profile::firstOrCreate(
            ['user_id' => $inventoryStaff->id],
            [
                'phone' => '+855987654321',
                'address' => '456 Inventory Avenue',
            ]
        );

        echo "Test users created successfully!\n";
        echo "Sale Staff - Email: sale@inventory.com, Password: password\n";
        echo "Inventory Staff - Email: inventory@inventory.com, Password: password\n";
    }
}