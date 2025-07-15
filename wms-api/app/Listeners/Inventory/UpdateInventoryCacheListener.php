<?php

namespace App\Listeners\Inventory;

use App\Events\Inventory\InventoryChangedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateInventoryCacheListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  InventoryChangedEvent  $event
     * @return void
     */
    public function handle(InventoryChangedEvent $event)
    {
        try {
            // Update the inventory in cache
            $cacheKey = "inventory_product_{$event->inventory->product_id}_location_{$event->inventory->location_id}";
            Cache::put($cacheKey, $event->inventory, now()->addHours(24));

            // Update the product's total inventory cache
            $this->updateProductTotalInventoryCache($event->inventory->product_id);

            Log::info('Inventory cache updated', [
                'product_id' => $event->inventory->product_id,
                'location_id' => $event->inventory->location_id,
                'change_type' => $event->changeType,
                'previous_quantity' => $event->previousQuantity,
                'current_quantity' => $event->inventory->quantity,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update inventory cache', [
                'product_id' => $event->inventory->product_id,
                'location_id' => $event->inventory->location_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the product's total inventory cache.
     *
     * @param  int  $productId
     * @return void
     */
    private function updateProductTotalInventoryCache($productId)
    {
        $cacheKey = "product_{$productId}_total_inventory";
        
        // Get all inventory records for this product
        $inventories = \App\Models\ProductInventory::where('product_id', $productId)->get();
        
        // Calculate total quantity
        $totalQuantity = $inventories->sum('quantity');
        
        // Update cache
        Cache::put($cacheKey, $totalQuantity, now()->addHours(24));
    }
}