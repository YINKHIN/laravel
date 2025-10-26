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

// Add tax and discount columns to the orders table
try {
    // Check if columns already exist
    $columns = Capsule::select("SHOW COLUMNS FROM orders WHERE Field IN ('tax', 'discount')");
    
    if (count($columns) == 0) {
        // Add tax column
        Capsule::schema()->table('orders', function ($table) {
            $table->decimal('tax', 10, 2)->default(0.00)->after('total');
            $table->decimal('discount', 10, 2)->default(0.00)->after('tax');
        });
        echo "Successfully added tax and discount columns to orders table\n";
    } else {
        echo "Tax and discount columns already exist in orders table\n";
    }
} catch (Exception $e) {
    echo "Error adding columns to orders table: " . $e->getMessage() . "\n";
}