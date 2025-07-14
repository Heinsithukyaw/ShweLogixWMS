<?php

namespace App\Listeners\MasterData;

use App\Events\MasterData\ProductCreatedEvent;
use App\Events\MasterData\ProductUpdatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateProductCacheListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  ProductCreatedEvent|ProductUpdatedEvent  $event
     * @return void
     */
    public function handle($event)
    {
        try {
            // Update the product in cache
            $cacheKey = 'product_' . $event->product->id;
            Cache::put($cacheKey, $event->product, now()->addHours(24));

            // Update the products list cache
            $this->updateProductsListCache($event->product);

            Log::info('Product cache updated', [
                'product_id' => $event->product->id,
                'event' => $event->getName(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update product cache', [
                'product_id' => $event->product->id,
                'event' => $event->getName(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the products list cache.
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    private function updateProductsListCache($product)
    {
        $cacheKey = 'products_list';
        
        if (Cache::has($cacheKey)) {
            $products = Cache::get($cacheKey);
            
            // Find and update or add the product
            $found = false;
            foreach ($products as $key => $cachedProduct) {
                if ($cachedProduct->id === $product->id) {
                    $products[$key] = $product;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $products[] = $product;
            }
            
            Cache::put($cacheKey, $products, now()->addHours(24));
        }
    }
}