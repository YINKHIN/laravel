<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup 
                            {--database= : Database name (defaults to .env setting)} 
                            {--destination= : Backup destination path (defaults to storage/app/backups)}
                            {--compress : Compress backup file with gzip}
                            {--keep-last=5 : Number of backups to keep (default: 5)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting database backup...');
        
        try {
            // Get database configuration
            $database = $this->option('database') ?? config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);
            
            // Create backup filename
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$database}_{$timestamp}.sql";
            
            // Get destination path
            $destination = $this->option('destination') ?? 'backups';
            $fullPath = storage_path("app/{$destination}");
            
            // Create directory if it doesn't exist
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            
            $backupFile = "{$fullPath}/{$filename}";
            
            // Create SQL backup using PHP
            $this->info("Creating backup: {$backupFile}");
            $this->createSqlBackup($backupFile, $database);
            
            // Compress if requested
            if ($this->option('compress')) {
                $compressedFile = "{$backupFile}.gz";
                $this->info("Compressing backup to: {$compressedFile}");
                
                $gz = gzopen($compressedFile, 'wb9');
                if ($gz) {
                    gzwrite($gz, file_get_contents($backupFile));
                    gzclose($gz);
                    
                    // Remove uncompressed file
                    unlink($backupFile);
                    $backupFile = $compressedFile;
                    $filename = "{$filename}.gz";
                } else {
                    $this->error('Failed to compress backup file');
                }
            }
            
            $this->info("Database backup created successfully: {$filename}");
            
            // Clean up old backups
            $this->cleanupOldBackups($fullPath, $this->option('keep-last'));
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Database backup failed: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Create SQL backup using PHP
     *
     * @param string $backupFile
     * @param string $database
     * @return void
     */
    private function createSqlBackup($backupFile, $database)
    {
        // Get all table names
        $tables = DB::select('SHOW TABLES');
        $tableNames = [];
        
        $tableNameKey = 'Tables_in_' . $database;
        foreach ($tables as $table) {
            $tableNames[] = $table->$tableNameKey;
        }
        
        // Open file for writing
        $file = fopen($backupFile, 'w');
        
        // Write header
        fwrite($file, "-- Database Backup\n");
        fwrite($file, "-- Generated on " . Carbon::now()->toDateTimeString() . "\n\n");
        fwrite($file, "SET FOREIGN_KEY_CHECKS=0;\n\n");
        
        // Backup each table
        foreach ($tableNames as $tableName) {
            $this->backupTable($file, $tableName);
        }
        
        // Write footer
        fwrite($file, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        
        // Close file
        fclose($file);
    }
    
    /**
     * Backup a single table
     *
     * @param resource $file
     * @param string $tableName
     * @return void
     */
    private function backupTable($file, $tableName)
    {
        // Drop table statement
        fwrite($file, "\n-- Table structure for table `{$tableName}`\n");
        fwrite($file, "DROP TABLE IF EXISTS `{$tableName}`;\n");
        
        // Create table statement
        $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
        $createSql = $createTable->{'Create Table'};
        fwrite($file, $createSql . ";\n\n");
        
        // Data insertion
        fwrite($file, "-- Dumping data for table `{$tableName}`\n");
        
        // Get all rows
        $rows = DB::table($tableName)->get();
        
        if ($rows->count() > 0) {
            // Get column names
            $columns = array_keys((array) $rows[0]);
            
            // Process rows in batches to avoid memory issues
            $batchSize = 100;
            $batches = array_chunk($rows->toArray(), $batchSize);
            
            foreach ($batches as $batch) {
                $values = [];
                foreach ($batch as $row) {
                    $rowValues = [];
                    foreach ($columns as $column) {
                        $value = $row->$column;
                        if (is_null($value)) {
                            $rowValues[] = 'NULL';
                        } else {
                            $rowValues[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }
                
                $columnsList = '`' . implode('`, `', $columns) . '`';
                $valuesList = implode(",\n", $values);
                
                fwrite($file, "INSERT INTO `{$tableName}` ({$columnsList}) VALUES\n{$valuesList};\n");
            }
        }
        
        fwrite($file, "\n");
    }
    
    /**
     * Clean up old backup files
     *
     * @param string $path
     * @param int $keepLast
     * @return void
     */
    private function cleanupOldBackups($path, $keepLast)
    {
        $this->info("Cleaning up old backups, keeping last {$keepLast}...");
        
        // Get all backup files
        $files = glob("{$path}/backup_*.sql*");
        
        // Sort by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Remove old files
        $removed = 0;
        for ($i = $keepLast; $i < count($files); $i++) {
            if (unlink($files[$i])) {
                $removed++;
            }
        }
        
        $this->info("Removed {$removed} old backup files");
    }
}