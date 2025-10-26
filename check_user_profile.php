<?php
// Script to check if a user profile exists in the database

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Profile;

echo "=== Checking User Profile Data ===\n\n";

// Check if there are any users in the database
$userCount = User::count();
echo "Total users in database: " . $userCount . "\n";

if ($userCount > 0) {
    // Get the first user
    $user = User::first();
    echo "First user ID: " . $user->id . "\n";
    echo "First user name: " . $user->name . "\n";
    echo "First user email: " . $user->email . "\n";
    
    // Load the profile relationship
    $user->load('profile');
    
    echo "User profile loaded: " . ($user->profile ? 'Yes' : 'No') . "\n";
    
    if ($user->profile) {
        echo "Profile ID: " . $user->profile->id . "\n";
        echo "Profile phone: " . ($user->profile->phone ?? 'Not set') . "\n";
        echo "Profile address: " . ($user->profile->address ?? 'Not set') . "\n";
        echo "Profile image: " . ($user->profile->image ?? 'Not set') . "\n";
        echo "Profile type: " . ($user->profile->type ?? 'Not set') . "\n";
    } else {
        echo "No profile found for user. Creating one...\n";
        
        // Create a profile for the user
        $profile = Profile::create([
            'user_id' => $user->id,
            'phone' => '123-456-7890',
            'address' => 'Test Address',
            'type' => $user->user_type
        ]);
        
        echo "Profile created with ID: " . $profile->id . "\n";
    }
} else {
    echo "No users found in the database.\n";
}

echo "\n=== Check Complete ===\n";