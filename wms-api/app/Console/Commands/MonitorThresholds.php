<?php

namespace App\Console\Commands;

use App\Services\ThresholdMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorThresholds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wms:monitor-thresholds {--type=all : The type of thresholds to monitor (all, inventory, capacity)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor system thresholds and generate alerts';

    /**
     * The threshold monitoring service.
     *
     * @var ThresholdMonitoringService
     */
    protected $thresholdMonitoringService;

    /**
     * Create a new command instance.
     *
     * @param ThresholdMonitoringService $thresholdMonitoringService
     * @return void
     */
    public function __construct(ThresholdMonitoringService $thresholdMonitoringService)
    {
        parent::__construct();
        $this->thresholdMonitoringService = $thresholdMonitoringService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->option('type');
        
        $this->info("Starting threshold monitoring for type: {$type}");
        Log::info("Starting threshold monitoring", ['type' => $type]);
        
        try {
            switch ($type) {
                case 'inventory':
                    $this->monitorInventoryThresholds();
                    break;
                    
                case 'capacity':
                    $this->monitorCapacityThresholds();
                    break;
                    
                case 'all':
                default:
                    $this->monitorInventoryThresholds();
                    $this->monitorCapacityThresholds();
                    break;
            }
            
            $this->info('Threshold monitoring completed successfully');
            Log::info('Threshold monitoring completed successfully');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Threshold monitoring failed: ' . $e->getMessage());
            Log::error('Threshold monitoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }

    /**
     * Monitor inventory thresholds.
     *
     * @return void
     */
    protected function monitorInventoryThresholds()
    {
        $this->info('Monitoring inventory thresholds...');
        $this->thresholdMonitoringService->checkInventoryThresholds();
        $this->info('Inventory threshold monitoring completed');
    }

    /**
     * Monitor capacity thresholds.
     *
     * @return void
     */
    protected function monitorCapacityThresholds()
    {
        $this->info('Monitoring capacity thresholds...');
        $this->thresholdMonitoringService->checkCapacityThresholds();
        $this->info('Capacity threshold monitoring completed');
    }
}