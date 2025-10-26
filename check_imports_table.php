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

// Check if supplier column exists in imports table
try {
    $columns = Capsule::select("SHOW COLUMNS FROM imports WHERE Field = 'supplier'");
    
    if (count($columns) > 0) {
        echo "Supplier column exists in imports table\n";
    } else {
        echo "Supplier column does not exist in imports table\n";
        // Add the column
        Capsule::schema()->table('imports', function ($table) {
            $table->string('supplier', 100)->after('sup_id');
        });
        echo "Supplier column added successfully\n";
    }
} catch (Exception $e) {
    echo "Error checking/adding supplier column: " . $e->getMessage() . "\n";
}