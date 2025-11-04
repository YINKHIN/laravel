<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

try {
    // Check if user already exists
    $existingUser = User::where('email', 'realadmin@example.com')->first();
    
    if ($existingUser) {
        echo "User already exists: realadmin@example.com\n";
        echo "You can log in with: realadmin@example.com / password123\n";
    } else {
        // Create new admin user
        $user = User::create([
            'name' => 'Real Admin',
            'email' => 'realadmin@example.com',
            'password' => bcrypt('password123'),
            'type' => 'admin'
        ]);
        
        echo "Created new admin user successfully!\n";
        echo "Email: realadmin@example.com\n";
        echo "Password: password123\n";
        echo "Type: admin\n";
    }
} catch (Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
}