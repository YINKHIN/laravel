<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;

// Create a test user
$email = 'test@example.com';
$password = 'password123';

// Check if user already exists
if (User::where('email', $email)->exists()) {
    echo "User already exists. Deleting...\n";
    User::where('email', $email)->delete();
}

// Create the user
$user = User::create([
    'name' => 'Test User',
    'email' => $email,
    'password' => Hash::make($password),
    'user_type' => 'admin',
]);

echo "✅ Test user created successfully!\n";
echo "📧 Email: $email\n";
echo "🔑 Password: $password\n";
echo "👤 Type: admin\n";
echo "🆔 User ID: {$user->id}\n";