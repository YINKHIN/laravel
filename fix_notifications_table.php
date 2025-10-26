<?php

// This script fixes the notifications table structure to ensure the ID field is auto-incrementing
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Fixing Notifications Table ===\n\n";

try {
    // Check if the table exists
    if (!Schema::hasTable('notifications')) {
        echo "❌ Error: notifications table does not exist!\n";
        exit(1);
    }
    
    // Check the current structure of the id column
    $columns = DB::select("SHOW COLUMNS FROM notifications WHERE Field = 'id'");
    
    if (empty($columns)) {
        echo "❌ Error: id column not found in notifications table!\n";
        exit(1);
    }
    
    $idColumn = $columns[0];
    echo "Current ID column type: " . $idColumn->Type . "\n";
    echo "Current ID column extra: " . $idColumn->Extra . "\n";
    
    // Check if it's already auto-incrementing
    if (strpos($idColumn->Extra, 'auto_increment') !== false) {
        echo "✅ ID column is already auto-incrementing. No changes needed.\n";
        exit(0);
    }
    
    echo "🔧 ID column is not auto-incrementing. Fixing...\n";
    
    // Create a new table with the correct structure
    DB::statement("CREATE TABLE notifications_new (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NULL,
        type VARCHAR(255) NOT NULL,
        title VARCHAR(255) NULL,
        message TEXT NULL,
        related_url VARCHAR(255) NULL,
        is_read TINYINT(1) DEFAULT 0,
        read_at TIMESTAMP NULL,
        data TEXT NULL,
        notifiable_type VARCHAR(255) NOT NULL,
        notifiable_id BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        INDEX notifications_user_id_index (user_id),
        INDEX notifications_notifiable_type_notifiable_id_index (notifiable_type, notifiable_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    echo "✅ Created new notifications table with correct structure\n";
    
    // Copy data from old table to new table
    DB::statement("INSERT INTO notifications_new 
        (user_id, type, title, message, related_url, is_read, read_at, data, notifiable_type, notifiable_id, created_at, updated_at)
        SELECT user_id, type, title, message, related_url, is_read, read_at, data, notifiable_type, notifiable_id, created_at, updated_at 
        FROM notifications");
    
    echo "✅ Copied data to new table\n";
    
    // Drop the old table
    DB::statement("DROP TABLE notifications");
    echo "✅ Dropped old notifications table\n";
    
    // Rename the new table to the original name
    DB::statement("RENAME TABLE notifications_new TO notifications");
    echo "✅ Renamed new table to notifications\n";
    
    echo "\n✅ Notifications table fixed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error fixing notifications table: " . $e->getMessage() . "\n";
    exit(1);
}