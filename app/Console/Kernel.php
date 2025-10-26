<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\GenerateScheduledReports::class,
        Commands\DatabaseBackup::class,
        Commands\SystemMonitor::class,
        Commands\CleanLogs::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Daily report generation
        $schedule->command('reports:generate-scheduled --period=daily --admin-only')
                 ->dailyAt('01:00')
                 ->description('Generate daily inventory reports and send to admins');

        // Weekly report generation
        $schedule->command('reports:generate-scheduled --period=weekly --admin-only')
                 ->weeklyOn(1, '2:00') // Monday at 2:00 AM
                 ->description('Generate weekly inventory reports and send to admins');

        // Monthly report generation
        $schedule->command('reports:generate-scheduled --period=monthly --admin-only')
                 ->monthlyOn(1, '3:00') // First day of month at 3:00 AM
                 ->description('Generate monthly inventory reports and send to admins');
                 
        // Daily database backup
        $schedule->command('db:backup --compress')
                 ->dailyAt('02:00')
                 ->description('Create daily database backup');
                 
        // Hourly system monitoring
        $schedule->command('system:monitor --log')
                 ->hourly()
                 ->description('Monitor system performance and health');
                 
        // Weekly log cleanup (keep 30 days)
        $schedule->command('logs:clean --days=30')
                 ->weekly()
                 ->description('Clean old log files');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}