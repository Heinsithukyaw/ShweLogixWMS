<?php

namespace App\Services\ECommerce;

use App\Models\ECommerce\InventorySync;
use App\Models\ProductInventory;
use App\Services\Integration\ShopifyService;
use App\Services\Integration\MagentoService;
use App\Services\Integration\WooCommerceService;
use App\Services\Integration\AmazonService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InventorySyncService
{
    protected $platformServices = [];

    public function __construct(
        ShopifyService $shopifyService,
        MagentoService $magentoService,
        WooCommerceService $wooCommerceService,
        AmazonService $amazonService
    ) {
        $this->platformServices = [
            'shopify' => $shopifyService,
            'magento' => $magentoService,
            'woocommerce' => $wooCommerceService,
            'amazon' => $amazonService
        ];
    }

    /**
     * Sync inventory for a specific product
     */
    public function syncProductInventory(InventorySync $sync): array
    {
        try {
            // Get current WMS inventory
            $wmsInventory = $this->getWMSInventory($sync->product_id);
            $sync->wms_quantity = $wmsInventory;

            // Get platform service
            $platformService = $this->getPlatformService($sync->platform);
            if (!$platformService) {
                throw new \Exception("Platform service not found for: {$sync->platform}");
            }

            // Apply sync rules
            $syncQuantity = $this->applySyncRules($wmsInventory, $sync->sync_rules);

            // Update platform inventory
            $result = $platformService->updateInventory($sync->platform_product_id, $syncQuantity);

            if ($result['success']) {
                $sync->platform_quantity = $syncQuantity;
                $sync->updateSyncStatus('completed');
                
                Log::info('Inventory sync completed successfully', [
                    'sync_id' => $sync->id,
                    'product_id' => $sync->product_id,
                    'platform' => $sync->platform,
                    'wms_quantity' => $wmsInventory,
                    'sync_quantity' => $syncQuantity
                ]);

                return [
                    'success' => true,
                    'wms_quantity' => $wmsInventory,
                    'sync_quantity' => $syncQuantity,
                    'platform_response' => $result
                ];
            } else {
                $sync->updateSyncStatus('failed', $result['error'] ?? 'Unknown error');
                throw new \Exception($result['error'] ?? 'Platform sync failed');
            }

        } catch (\Exception $e) {
            $sync->updateSyncStatus('failed', $e->getMessage());
            
            Log::error('Inventory sync failed', [
                'sync_id' => $sync->id,
                'product_id' => $sync->product_id,
                'platform' => $sync->platform,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync all pending inventory
     */
    public function syncAllPendingInventory(?string $platform = null): array
    {
        $query = InventorySync::pendingSync();
        
        if ($platform) {
            $query->where('platform', $platform);
        }

        $pendingSyncs = $query->get();
        $results = [
            'total' => $pendingSyncs->count(),
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($pendingSyncs as $sync) {
            $result = $this->syncProductInventory($sync);
            
            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'sync_id' => $sync->id,
                    'product_id' => $sync->product_id,
                    'platform' => $sync->platform,
                    'error' => $result['error']
                ];
            }
        }

        return $results;
    }

    /**
     * Get sync statistics
     */
    public function getSyncStatistics(?string $platform = null): array
    {
        $query = InventorySync::query();
        
        if ($platform) {
            $query->where('platform', $platform);
        }

        $stats = [
            'total_syncs' => $query->count(),
            'pending_syncs' => $query->where('sync_status', 'pending')->count(),
            'completed_syncs' => $query->where('sync_status', 'completed')->count(),
            'failed_syncs' => $query->where('sync_status', 'failed')->count(),
            'last_24h_syncs' => $query->where('last_sync_at', '>=', now()->subDay())->count(),
            'sync_frequency_breakdown' => $query->groupBy('sync_frequency')
                ->selectRaw('sync_frequency, count(*) as count')
                ->pluck('count', 'sync_frequency'),
            'platform_breakdown' => $query->groupBy('platform')
                ->selectRaw('platform, count(*) as count')
                ->pluck('count', 'platform'),
            'average_sync_time' => $this->calculateAverageSyncTime($platform),
            'sync_success_rate' => $this->calculateSyncSuccessRate($platform)
        ];

        return $stats;
    }

    /**
     * Create bulk inventory sync configurations
     */
    public function createBulkSyncConfigurations(array $products, string $platform, array $defaultSettings = []): array
    {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        DB::beginTransaction();
        
        try {
            foreach ($products as $productData) {
                // Check if sync configuration already exists
                $existingSync = InventorySync::where('product_id', $productData['product_id'])
                    ->where('platform', $platform)
                    ->first();

                if ($existingSync) {
                    $results['skipped']++;
                    continue;
                }

                // Create new sync configuration
                InventorySync::create([
                    'product_id' => $productData['product_id'],
                    'platform' => $platform,
                    'platform_product_id' => $productData['platform_product_id'],
                    'sync_frequency' => $defaultSettings['sync_frequency'] ?? 'hourly',
                    'sync_rules' => $defaultSettings['sync_rules'] ?? [],
                    'sync_status' => 'pending'
                ]);

                $results['created']++;
            }

            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get WMS inventory for a product
     */
    private function getWMSInventory(int $productId): float
    {
        return ProductInventory::where('product_id', $productId)
            ->sum('available_quantity');
    }

    /**
     * Apply sync rules to determine the quantity to sync
     */
    private function applySyncRules(float $wmsQuantity, ?array $syncRules): float
    {
        if (!$syncRules) {
            return $wmsQuantity;
        }

        $syncQuantity = $wmsQuantity;

        // Apply safety stock rule
        if (isset($syncRules['safety_stock'])) {
            $syncQuantity = max(0, $syncQuantity - $syncRules['safety_stock']);
        }

        // Apply maximum quantity rule
        if (isset($syncRules['max_quantity'])) {
            $syncQuantity = min($syncQuantity, $syncRules['max_quantity']);
        }

        // Apply minimum quantity rule
        if (isset($syncRules['min_quantity'])) {
            $syncQuantity = max($syncQuantity, $syncRules['min_quantity']);
        }

        // Apply percentage rule
        if (isset($syncRules['sync_percentage'])) {
            $syncQuantity = $syncQuantity * ($syncRules['sync_percentage'] / 100);
        }

        // Round down to avoid overselling
        return floor($syncQuantity);
    }

    /**
     * Get platform service instance
     */
    private function getPlatformService(string $platform)
    {
        return $this->platformServices[$platform] ?? null;
    }

    /**
     * Calculate average sync time
     */
    private function calculateAverageSyncTime(?string $platform = null): ?float
    {
        $query = InventorySync::whereNotNull('last_sync_at')
            ->where('sync_status', 'completed');

        if ($platform) {
            $query->where('platform', $platform);
        }

        // This would require tracking sync duration in the database
        // For now, return a placeholder
        return 2.5; // Average 2.5 seconds
    }

    /**
     * Calculate sync success rate
     */
    private function calculateSyncSuccessRate(?string $platform = null): float
    {
        $query = InventorySync::whereNotNull('last_sync_at');

        if ($platform) {
            $query->where('platform', $platform);
        }

        $total = $query->count();
        $successful = $query->where('sync_status', 'completed')->count();

        return $total > 0 ? ($successful / $total) * 100 : 0;
    }

    /**
     * Handle real-time inventory updates
     */
    public function handleRealTimeInventoryUpdate(int $productId, float $newQuantity): void
    {
        // Find all real-time sync configurations for this product
        $realTimeSyncs = InventorySync::where('product_id', $productId)
            ->where('sync_frequency', 'real_time')
            ->get();

        foreach ($realTimeSyncs as $sync) {
            // Queue the sync job for immediate processing
            \App\Jobs\SyncInventoryJob::dispatch($sync)->onQueue('high');
        }
    }
}