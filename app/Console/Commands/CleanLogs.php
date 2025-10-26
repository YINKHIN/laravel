<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CleanLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clean 
                            {--days=30 : Number of days to keep logs}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old log files to free up disk space';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = $this->option('days');
        $logDirectory = storage_path('logs');
        
        $this->info("Cleaning log files older than {$days} days...");
        
        if (!is_dir($logDirectory)) {
            $this->error("Log directory does not exist: {$logDirectory}");
            return 1;
        }
        
        // Get all log files
        $logFiles = File::files($logDirectory);
        $oldFiles = [];
        $cutoffDate = Carbon::now()->subDays($days);
        
        foreach ($logFiles as $file) {
            if ($file->getMTime() < $cutoffDate->timestamp) {
                $oldFiles[] = $file;
            }
        }
        
        if (empty($oldFiles)) {
            $this->info("No log files older than {$days} days found.");
            return 0;
        }
        
        $this->info("Found " . count($oldFiles) . " old log files:");
        
        foreach ($oldFiles as $file) {
            $this->line("- " . $file->getFilename() . " (" . $this->formatBytes($file->getSize()) . ")");
        }
        
        // Confirm deletion unless force option is used
        if (!$this->option('force')) {
            if (!$this->confirm("Do you want to delete these files?")) {
                $this->info("Operation cancelled.");
                return 0;
            }
        }
        
        // Delete files
        $deleted = 0;
        $errors = 0;
        
        foreach ($oldFiles as $file) {
            try {
                File::delete($file->getPathname());
                $deleted++;
            } catch (\Exception $e) {
                $this->error("Failed to delete {$file->getFilename()}: " . $e->getMessage());
                $errors++;
            }
        }
        
        $this->info("Deleted {$deleted} log files.");
        
        if ($errors > 0) {
            $this->error("Failed to delete {$errors} files.");
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}