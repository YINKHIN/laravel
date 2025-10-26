<?php
// Script to list all users in the database

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== Listing All Users ===\n\n";

$users = User::all();

if ($users->count() > 0) {
    foreach ($users as $user) {
        echo "ID: " . $user->id . "\n";
        echo "Name: " . $user->name . "\n";
        echo "Email: " . $user->email . "\n";
        echo "User Type: " . $user->user_type . "\n";
        echo "Created At: " . $user->created_at . "\n";
        echo "------------------------\n";
    }
} else {
    echo "No users found in the database.\n";
}

echo "Total users: " . $users->count() . "\n";
echo "\n=== List Complete ===\n";