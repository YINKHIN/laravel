<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking notifications table structure...\n";

// Check if notifications table exists
if (Schema::hasTable('notifications')) {
    echo "Notifications table exists.\n";
    
    // Get table columns
    $columns = DB::select("SHOW COLUMNS FROM notifications");
    echo "Table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type}) ";
        if ($column->Null === 'NO') {
            echo "NOT NULL ";
        }
        if ($column->Key === 'PRI') {
            echo "PRIMARY KEY ";
        }
        if ($column->Extra === 'auto_increment') {
            echo "AUTO_INCREMENT ";
        }
        if ($column->Default !== null) {
            echo "DEFAULT '{$column->Default}'";
        }
        echo "\n";
    }
    
    // Check for specific columns
    $requiredColumns = ['notifiable_type', 'notifiable_id'];
    foreach ($requiredColumns as $column) {
        $exists = false;
        foreach ($columns as $col) {
            if ($col->Field === $column) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            echo "ERROR: Column {$column} does not exist!\n";
        } else {
            echo "Column {$column} exists.\n";
        }
    }
    
    // Check if ID column is auto-incrementing
    $idColumn = null;
    foreach ($columns as $column) {
        if ($column->Field === 'id') {
            $idColumn = $column;
            break;
        }
    }
    
    if ($idColumn) {
        if ($idColumn->Extra === 'auto_increment') {
            echo "ID column is auto-incrementing.\n";
        } else {
            echo "WARNING: ID column is NOT auto-incrementing!\n";
        }
    }
    
} else {
    echo "ERROR: Notifications table does not exist!\n";
}

echo "Check complete.\n";