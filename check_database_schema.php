<?php

require_once 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Create the application container
$app = new Application(__DIR__);

// Bind the container instance
Container::setInstance($app);

// Register the event service provider
(new EventServiceProvider($app))->register();

// Register the request
$app->instance('request', Request::capture());

// Load the Laravel application
$app->bootstrapWith([
    \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
    \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
    \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
    \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
    \Illuminate\Foundation\Bootstrap\BootProviders::class,
]);

// Initialize the database connection
$app->make('db');

// Include the models
require_once 'app/Models/Product.php';
require_once 'app/Models/Notification.php';

use App\Models\Product;
use App\Models\Notification;

echo "Checking database schema...\n\n";

// Check if products table exists and has the correct columns
try {
    echo "Checking products table...\n";
    $product = new Product();
    echo "Table name: " . $product->getTable() . "\n";
    
    // Try to get column listing
    $columns = \Schema::getColumnListing($product->getTable());
    echo "Columns: " . implode(', ', $columns) . "\n";
    
    // Check if status column exists
    if (in_array('status', $columns)) {
        echo "✓ Status column exists\n";
    } else {
        echo "✗ Status column does not exist\n";
    }
    
    // Try to query products with status
    $count = Product::where('status', 'active')->count();
    echo "Products with active status: " . $count . "\n\n";
    
} catch (Exception $e) {
    echo "Error checking products table: " . $e->getMessage() . "\n\n";
}

// Check if notifications table exists and has the correct columns
try {
    echo "Checking notifications table...\n";
    $notification = new Notification();
    echo "Table name: " . $notification->getTable() . "\n";
    
    // Try to get column listing
    $columns = \Schema::getColumnListing($notification->getTable());
    echo "Columns: " . implode(', ', $columns) . "\n";
    
    // Check if user_id column exists
    if (in_array('user_id', $columns)) {
        echo "✓ User ID column exists\n";
    } else {
        echo "✗ User ID column does not exist\n";
    }
    
    // Try to query notifications
    $count = Notification::count();
    echo "Total notifications: " . $count . "\n\n";
    
} catch (Exception $e) {
    echo "Error checking notifications table: " . $e->getMessage() . "\n\n";
}

echo "Database schema check completed.\n";