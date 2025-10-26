<?php
require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configure database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'],
    'database'  => $_ENV['DB_DATABASE'],
    'username'  => $_ENV['DB_USERNAME'],
    'password'  => $_ENV['DB_PASSWORD'],
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

// Add percentage columns to the orders table
try {
    // Check if columns already exist
    $columns = Capsule::select("SHOW COLUMNS FROM orders WHERE Field IN ('subtotal', 'tax_percent', 'discount_percent')");
    
    if (count($columns) < 3) {
        // Add the new columns
        Capsule::schema()->table('orders', function ($table) {
            $table->decimal('subtotal', 10, 2)->default(0.00)->after('total');
            $table->decimal('tax_percent', 5, 2)->default(0.00)->after('tax');
            $table->decimal('discount_percent', 5, 2)->default(0.00)->after('discount');
        });
        echo "Successfully added percentage columns to orders table\n";
    } else {
        echo "Percentage columns already exist in orders table\n";
    }
} catch (Exception $e) {
    echo "Error adding columns to orders table: " . $e->getMessage() . "\n";
}