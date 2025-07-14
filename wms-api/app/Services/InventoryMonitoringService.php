<?php

namespace App\Services;

use App\Events\Inventory\InventoryThresholdEvent;
use App\Models\ProductInventory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryMonitoringService
{
    /**
     * Monitor inventory levels for threshold violations.
     *
     * @return array
     */
    public function monitorInventoryLevels()
    {
        $results = [
            'low_stock' => 0,
            'high_stock' => 0,
            'expiring_soon' => 0,
            'errors' => 0,
        ];

        try {
            // Check for low stock thresholds
            $results['low_stock'] = $this->checkLowStockThresholds();
            
            // Check for high stock thresholds
            $results['high_stock'] = $this->checkHighStockThresholds();
            
            // Check for expiring inventory
            $results['expiring_soon'] = $this->checkExpiringInventory();
            
            Log::info('Inventory monitoring completed', $results);
        } catch (\Exception $e) {
            Log::error('Failed to monitor inventory levels', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $results['errors']++;
        }
        
        return $results;
    }

    /**
     * Check for low stock thresholds.
     *
     * @return int
     */
    protected function checkLowStockThresholds()
    {
        $count = 0;
        
        try {
            // Get products with inventory below reorder point
            $lowStockItems = DB::table('product_inventories as pi')
                ->join('products as p', 'pi.product_id', '=', 'p.id')
                ->select('pi.id', 'pi.product_id', 'pi.location_id', 'pi.quantity', 'p.reorder_point')
                ->whereNotNull('p.reorder_point')
                ->whereRaw('pi.quantity <= p.reorder_point')
                ->get();
            
            foreach ($lowStockItems as $item) {
                try {
                    $inventory = ProductInventory::find($item->id);
                    
                    if ($inventory) {
                        // Dispatch low stock event
                        event(InventoryThresholdEvent::lowStock(
                            $inventory,
                            $item->reorder_point,
                            $item->quantity
                        ));
                        
                        $count++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to process low stock item', [
                        'inventory_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            Log::info('Low stock check completed', [
                'items_found' => $lowStockItems->count(),
                'alerts_generated' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check low stock thresholds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $count;
    }

    /**
     * Check for high stock thresholds.
     *
     * @return int
     */
    protected function checkHighStockThresholds()
    {
        $count = 0;
        
        try {
            // Get products with inventory above maximum level
            $highStockItems = DB::table('product_inventories as pi')
                ->join('products as p', 'pi.product_id', '=', 'p.id')
                ->select('pi.id', 'pi.product_id', 'pi.location_id', 'pi.quantity', 'p.maximum_level')
                ->whereNotNull('p.maximum_level')
                ->whereRaw('pi.quantity >= p.maximum_level')
                ->get();
            
            foreach ($highStockItems as $item) {
                try {
                    $inventory = ProductInventory::find($item->id);
                    
                    if ($inventory) {
                        // Dispatch high stock event
                        event(InventoryThresholdEvent::highStock(
                            $inventory,
                            $item->maximum_level,
                            $item->quantity
                        ));
                        
                        $count++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to process high stock item', [
                        'inventory_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            Log::info('High stock check completed', [
                'items_found' => $highStockItems->count(),
                'alerts_generated' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check high stock thresholds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $count;
    }

    /**
     * Check for expiring inventory.
     *
     * @return int
     */
    protected function checkExpiringInventory()
    {
        $count = 0;
        
        try {
            // Get products with expiry dates approaching
            $expiryThresholdDays = config('inventory.expiry_threshold_days', 30);
            $expiryDate = now()->addDays($expiryThresholdDays);
            
            $expiringItems = DB::table('product_inventories as pi')
                ->select('pi.id', 'pi.product_id', 'pi.location_id', 'pi.expiry_date')
                ->whereNotNull('pi.expiry_date')
                ->where('pi.expiry_date', '<=', $expiryDate)
                ->where('pi.expiry_date', '>', now())
                ->where('pi.quantity', '>', 0)
                ->get();
            
            foreach ($expiringItems as $item) {
                try {
                    $inventory = ProductInventory::find($item->id);
                    
                    if ($inventory) {
                        $daysUntilExpiry = now()->diffInDays($inventory->expiry_date);
                        
                        // Dispatch expiring soon event
                        event(InventoryThresholdEvent::expiringSoon(
                            $inventory,
                            $expiryThresholdDays,
                            $daysUntilExpiry
                        ));
                        
                        $count++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to process expiring item', [
                        'inventory_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            Log::info('Expiry check completed', [
                'items_found' => $expiringItems->count(),
                'alerts_generated' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check expiring inventory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $count;
    }

    /**
     * Get inventory metrics for dashboard.
     *
     * @return array
     */
    public function getInventoryMetrics()
    {
        try {
            // Get total inventory count
            $totalCount = ProductInventory::sum('quantity');
            
            // Get total inventory value
            $totalValue = DB::table('product_inventories as pi')
                ->join('products as p', 'pi.product_id', '=', 'p.id')
                ->select(DB::raw('SUM(pi.quantity * p.unit_cost) as total_value'))
                ->first()
                ->total_value ?? 0;
            
            // Get low stock count
            $lowStockCount = DB::table('product_inventories as pi')
                ->join('products as p', 'pi.product_id', '=', 'p.id')
                ->whereNotNull('p.reorder_point')
                ->whereRaw('pi.quantity <= p.reorder_point')
                ->count();
            
            // Get expiring soon count
            $expiryThresholdDays = config('inventory.expiry_threshold_days', 30);
            $expiryDate = now()->addDays($expiryThresholdDays);
            
            $expiringSoonCount = ProductInventory::whereNotNull('expiry_date')
                ->where('expiry_date', '<=', $expiryDate)
                ->where('expiry_date', '>', now())
                ->where('quantity', '>', 0)
                ->count();
            
            // Get inventory by location
            $inventoryByLocation = DB::table('product_inventories as pi')
                ->join('locations as l', 'pi.location_id', '=', 'l.id')
                ->select('l.name', DB::raw('SUM(pi.quantity) as total_quantity'))
                ->groupBy('l.id', 'l.name')
                ->orderBy('total_quantity', 'desc')
                ->limit(10)
                ->get();
            
            // Get inventory by product category
            $inventoryByCategory = DB::table('product_inventories as pi')
                ->join('products as p', 'pi.product_id', '=', 'p.id')
                ->join('categories as c', 'p.category_id', '=', 'c.id')
                ->select('c.name', DB::raw('SUM(pi.quantity) as total_quantity'))
                ->groupBy('c.id', 'c.name')
                ->orderBy('total_quantity', 'desc')
                ->limit(10)
                ->get();
            
            return [
                'total_count' => $totalCount,
                'total_value' => $totalValue,
                'low_stock_count' => $lowStockCount,
                'expiring_soon_count' => $expiringSoonCount,
                'inventory_by_location' => $inventoryByLocation,
                'inventory_by_category' => $inventoryByCategory,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get inventory metrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'total_count' => 0,
                'total_value' => 0,
                'low_stock_count' => 0,
                'expiring_soon_count' => 0,
                'inventory_by_location' => [],
                'inventory_by_category' => [],
                'error' => $e->getMessage(),
            ];
        }
    }
}