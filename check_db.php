<?php

// Database configuration
$host = '127.0.0.1';
$dbname = 'software_solution';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // Check current structure of notifications table
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current structure of notifications table:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} ";
        if ($column['Null'] === 'NO') {
            echo "NOT NULL ";
        }
        if ($column['Key'] === 'PRI') {
            echo "PRIMARY KEY ";
        }
        if ($column['Extra']) {
            echo "{$column['Extra']} ";
        }
        echo "\n";
    }
    
    echo "\n";
    
    // Check if id column is auto-incrementing
    $stmt = $pdo->query("SHOW COLUMNS FROM notifications WHERE Field = 'id'");
    $idColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($idColumn) {
        echo "ID column details:\n";
        echo "- Type: {$idColumn['Type']}\n";
        echo "- Extra: {$idColumn['Extra']}\n";
        
        if (strpos($idColumn['Extra'], 'auto_increment') !== false) {
            echo "✅ ID column is already auto-incrementing.\n";
        } else {
            echo "❌ ID column is NOT auto-incrementing. Need to fix.\n";
            // Fix ID column (code for this would go here)
        }
    }
    
    // Check if notifiable_type and notifiable_id have default values
    $stmt = $pdo->query("SHOW COLUMNS FROM notifications WHERE Field IN ('notifiable_type', 'notifiable_id')");
    $notifiableColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $needsUpdate = false;
    foreach ($notifiableColumns as $column) {
        if ($column['Default'] === null && $column['Null'] === 'NO') {
            echo "❌ {$column['Field']} column doesn't have a default value but is NOT NULL.\n";
            $needsUpdate = true;
        } else {
            echo "✅ {$column['Field']} column has proper default value or allows NULL.\n";
        }
    }
    
    if ($needsUpdate) {
        echo "\n🔧 Updating table structure to add default values...\n";
        
        // Add default values to notifiable_type and notifiable_id
        try {
            $pdo->exec("ALTER TABLE notifications MODIFY notifiable_type VARCHAR(255) NOT NULL DEFAULT 'App\Models\User'");
            echo "✅ Updated notifiable_type column with default value\n";
        } catch (Exception $e) {
            echo "⚠️ Could not update notifiable_type column: " . $e->getMessage() . "\n";
        }
        
        try {
            $pdo->exec("ALTER TABLE notifications MODIFY notifiable_id BIGINT UNSIGNED NOT NULL DEFAULT 0");
            echo "✅ Updated notifiable_id column with default value\n";
        } catch (Exception $e) {
            echo "⚠️ Could not update notifiable_id column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✅ Notifiable columns already have proper default values.\n";
    }
    
    echo "\n✅ Database check completed!\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}