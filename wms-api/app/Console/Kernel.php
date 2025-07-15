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
        Commands\MonitorThresholds::class,
        Commands\MonitorEventPerformance::class,
        Commands\MonitorInventory::class,
        Commands\CleanupIdempotencyKeys::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run inventory threshold monitoring every hour
        $schedule->command('wms:monitor-thresholds --type=inventory')
                 ->hourly()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/inventory-thresholds.log'));

        // Run capacity threshold monitoring every 2 hours
        $schedule->command('wms:monitor-thresholds --type=capacity')
                 ->cron('0 */2 * * *')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/capacity-thresholds.log'));
                 
        // Run database backup daily
        $schedule->command('backup:run')
                 ->daily()
                 ->at('01:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/backups.log'));
                 
        // Clean up old event logs weekly
        $schedule->command('event-logs:cleanup --days=90')
                 ->weekly()
                 ->sundays()
                 ->at('02:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/event-cleanup.log'));
                 
        // Monitor event performance every 15 minutes
        $schedule->command('wms:monitor-events')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/event-monitoring.log'));
                 
        // Check event backlog every 5 minutes
        $schedule->command('wms:monitor-events --check=backlog')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/event-backlog.log'));
                 
        // Monitor inventory levels every hour
        $schedule->command('wms:monitor-inventory')
                 ->hourly()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/inventory-monitoring.log'));
                 
        // Check for expiring inventory daily
        $schedule->command('wms:monitor-inventory --check=expiring')
                 ->dailyAt('08:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/inventory-expiry.log'));
                 
        // Clean up expired idempotency keys daily
        $schedule->command('wms:cleanup-idempotency-keys')
                 ->dailyAt('03:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/idempotency-cleanup.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
