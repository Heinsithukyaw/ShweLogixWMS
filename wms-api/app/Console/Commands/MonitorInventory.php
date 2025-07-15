<?php

namespace App\Console\Commands;

use App\Services\InventoryMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wms:monitor-inventory {--check=all : What to check (all, low-stock, high-stock, expiring)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor inventory levels and generate threshold alerts';

    /**
     * The inventory monitoring service.
     *
     * @var \App\Services\InventoryMonitoringService
     */
    protected $inventoryMonitoringService;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\InventoryMonitoringService  $inventoryMonitoringService
     * @return void
     */
    public function __construct(InventoryMonitoringService $inventoryMonitoringService)
    {
        parent::__construct();
        $this->inventoryMonitoringService = $inventoryMonitoringService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $check = $this->option('check');
        
        $this->info("Starting inventory monitoring: {$check}");
        Log::info("Starting inventory monitoring", ['check' => $check]);
        
        try {
            $results = [];
            
            switch ($check) {
                case 'low-stock':
                    $count = $this->inventoryMonitoringService->checkLowStockThresholds();
                    $results['low_stock'] = $count;
                    $this->info("Found {$count} items below reorder point");
                    break;
                    
                case 'high-stock':
                    $count = $this->inventoryMonitoringService->checkHighStockThresholds();
                    $results['high_stock'] = $count;
                    $this->info("Found {$count} items above maximum level");
                    break;
                    
                case 'expiring':
                    $count = $this->inventoryMonitoringService->checkExpiringInventory();
                    $results['expiring_soon'] = $count;
                    $this->info("Found {$count} items expiring soon");
                    break;
                    
                case 'all':
                default:
                    $results = $this->inventoryMonitoringService->monitorInventoryLevels();
                    $this->info("Low stock: {$results['low_stock']} items");
                    $this->info("High stock: {$results['high_stock']} items");
                    $this->info("Expiring soon: {$results['expiring_soon']} items");
                    break;
            }
            
            $this->info('Inventory monitoring completed successfully');
            Log::info('Inventory monitoring completed successfully', $results);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Inventory monitoring failed: ' . $e->getMessage());
            Log::error('Inventory monitoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }
}