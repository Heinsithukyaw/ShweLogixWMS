<?php

namespace App\Console\Commands;

use App\Services\EventMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorEventPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wms:monitor-events {--check=all : What to check (all, performance, backlog)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor event processing performance and backlogs';

    /**
     * The event monitoring service.
     *
     * @var EventMonitoringService
     */
    protected $eventMonitoringService;

    /**
     * Create a new command instance.
     *
     * @param EventMonitoringService $eventMonitoringService
     * @return void
     */
    public function __construct(EventMonitoringService $eventMonitoringService)
    {
        parent::__construct();
        $this->eventMonitoringService = $eventMonitoringService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $check = $this->option('check');
        
        $this->info("Starting event monitoring: {$check}");
        Log::info("Starting event monitoring", ['check' => $check]);
        
        try {
            switch ($check) {
                case 'performance':
                    $this->monitorPerformance();
                    break;
                    
                case 'backlog':
                    $this->checkBacklog();
                    break;
                    
                case 'all':
                default:
                    $this->monitorPerformance();
                    $this->checkBacklog();
                    break;
            }
            
            $this->info('Event monitoring completed successfully');
            Log::info('Event monitoring completed successfully');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Event monitoring failed: ' . $e->getMessage());
            Log::error('Event monitoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }

    /**
     * Monitor event processing performance.
     *
     * @return void
     */
    protected function monitorPerformance()
    {
        $this->info('Monitoring event processing performance...');
        $this->eventMonitoringService->monitorEventPerformance();
        $this->info('Performance monitoring completed');
    }

    /**
     * Check for event processing backlogs.
     *
     * @return void
     */
    protected function checkBacklog()
    {
        $this->info('Checking for event processing backlogs...');
        $this->eventMonitoringService->checkEventBacklog();
        $this->info('Backlog check completed');
    }
}