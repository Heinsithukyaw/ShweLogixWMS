<?php

namespace App\Services;

use App\Events\Notification\ThresholdAlertEvent;
use App\Models\Product;
use App\Models\Location;
use App\Models\ThresholdConfiguration;
use Illuminate\Support\Facades\Log;

class ThresholdMonitoringService
{
    /**
     * Check inventory thresholds for all products.
     *
     * @return void
     */
    public function checkInventoryThresholds()
    {
        try {
            // Get all products with inventory thresholds
            $products = Product::with('inventoryThresholds')->get();
            
            foreach ($products as $product) {
                $this->checkProductInventoryThresholds($product);
            }
            
            Log::info('Inventory threshold check completed', [
                'products_checked' => $products->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check inventory thresholds', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check inventory thresholds for a specific product.
     *
     * @param Product $product
     * @return void
     */
    public function checkProductInventoryThresholds(Product $product)
    {
        try {
            // Get the current inventory level
            $currentQuantity = $product->available_quantity;
            
            // Check minimum threshold
            $minThreshold = $product->min_quantity;
            if ($minThreshold !== null && $currentQuantity < $minThreshold) {
                // Dispatch low inventory threshold event
                event(ThresholdAlertEvent::inventory(
                    $product->id,
                    $currentQuantity,
                    $minThreshold,
                    '<',
                    'warning',
                    [
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'warehouse_id' => $product->warehouse_id,
                    ]
                ));
            }
            
            // Check critical minimum threshold
            $criticalMinThreshold = $product->critical_min_quantity;
            if ($criticalMinThreshold !== null && $currentQuantity < $criticalMinThreshold) {
                // Dispatch critical low inventory threshold event
                event(ThresholdAlertEvent::inventory(
                    $product->id,
                    $currentQuantity,
                    $criticalMinThreshold,
                    '<',
                    'critical',
                    [
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'warehouse_id' => $product->warehouse_id,
                    ]
                ));
            }
            
            // Check maximum threshold
            $maxThreshold = $product->max_quantity;
            if ($maxThreshold !== null && $currentQuantity > $maxThreshold) {
                // Dispatch high inventory threshold event
                event(ThresholdAlertEvent::inventory(
                    $product->id,
                    $currentQuantity,
                    $maxThreshold,
                    '>',
                    'warning',
                    [
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'warehouse_id' => $product->warehouse_id,
                    ]
                ));
            }
        } catch (\Exception $e) {
            Log::error('Failed to check product inventory thresholds', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check capacity thresholds for all locations.
     *
     * @return void
     */
    public function checkCapacityThresholds()
    {
        try {
            // Get all locations with capacity thresholds
            $locations = Location::with('capacityThresholds')->get();
            
            foreach ($locations as $location) {
                $this->checkLocationCapacityThresholds($location);
            }
            
            Log::info('Capacity threshold check completed', [
                'locations_checked' => $locations->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check capacity thresholds', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check capacity thresholds for a specific location.
     *
     * @param Location $location
     * @return void
     */
    public function checkLocationCapacityThresholds(Location $location)
    {
        try {
            // Calculate current capacity utilization (percentage)
            $maxCapacity = $location->max_capacity;
            $currentCapacity = $location->current_capacity;
            $utilizationPercentage = ($maxCapacity > 0) ? ($currentCapacity / $maxCapacity) * 100 : 0;
            
            // Check high capacity threshold
            $highCapacityThreshold = $location->high_capacity_threshold ?? 80; // Default to 80% if not set
            if ($utilizationPercentage >= $highCapacityThreshold) {
                // Dispatch high capacity threshold event
                event(ThresholdAlertEvent::capacity(
                    $location->id,
                    $utilizationPercentage,
                    $highCapacityThreshold,
                    '>=',
                    'warning',
                    [
                        'location_name' => $location->name,
                        'location_code' => $location->code,
                        'warehouse_id' => $location->warehouse_id,
                        'current_capacity' => $currentCapacity,
                        'max_capacity' => $maxCapacity,
                    ]
                ));
            }
            
            // Check critical capacity threshold
            $criticalCapacityThreshold = $location->critical_capacity_threshold ?? 95; // Default to 95% if not set
            if ($utilizationPercentage >= $criticalCapacityThreshold) {
                // Dispatch critical capacity threshold event
                event(ThresholdAlertEvent::capacity(
                    $location->id,
                    $utilizationPercentage,
                    $criticalCapacityThreshold,
                    '>=',
                    'critical',
                    [
                        'location_name' => $location->name,
                        'location_code' => $location->code,
                        'warehouse_id' => $location->warehouse_id,
                        'current_capacity' => $currentCapacity,
                        'max_capacity' => $maxCapacity,
                    ]
                ));
            }
        } catch (\Exception $e) {
            Log::error('Failed to check location capacity thresholds', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check performance thresholds.
     *
     * @param string $metricType
     * @param string $entityType
     * @param string $entityId
     * @param float|int $currentValue
     * @return void
     */
    public function checkPerformanceThreshold($metricType, $entityType, $entityId, $currentValue)
    {
        try {
            // Get threshold configuration for this metric
            $thresholdConfig = ThresholdConfiguration::where('metric_type', $metricType)
                ->where('entity_type', $entityType)
                ->first();
            
            if (!$thresholdConfig) {
                return;
            }
            
            // Check warning threshold
            $warningThreshold = $thresholdConfig->warning_threshold;
            $warningOperator = $thresholdConfig->warning_operator ?? '<';
            
            if ($this->compareValues($currentValue, $warningThreshold, $warningOperator)) {
                // Dispatch performance threshold event
                event(ThresholdAlertEvent::performance(
                    $metricType,
                    $entityType,
                    $entityId,
                    $currentValue,
                    $warningThreshold,
                    $warningOperator,
                    'warning',
                    [
                        'metric_name' => $thresholdConfig->metric_name,
                        'metric_unit' => $thresholdConfig->metric_unit,
                    ]
                ));
            }
            
            // Check critical threshold
            $criticalThreshold = $thresholdConfig->critical_threshold;
            $criticalOperator = $thresholdConfig->critical_operator ?? '<';
            
            if ($this->compareValues($currentValue, $criticalThreshold, $criticalOperator)) {
                // Dispatch critical performance threshold event
                event(ThresholdAlertEvent::performance(
                    $metricType,
                    $entityType,
                    $entityId,
                    $currentValue,
                    $criticalThreshold,
                    $criticalOperator,
                    'critical',
                    [
                        'metric_name' => $thresholdConfig->metric_name,
                        'metric_unit' => $thresholdConfig->metric_unit,
                    ]
                ));
            }
        } catch (\Exception $e) {
            Log::error('Failed to check performance threshold', [
                'metric_type' => $metricType,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Compare values using the specified operator.
     *
     * @param float|int $value1
     * @param float|int $value2
     * @param string $operator
     * @return bool
     */
    protected function compareValues($value1, $value2, $operator)
    {
        switch ($operator) {
            case '<':
                return $value1 < $value2;
            case '<=':
                return $value1 <= $value2;
            case '>':
                return $value1 > $value2;
            case '>=':
                return $value1 >= $value2;
            case '=':
            case '==':
                return $value1 == $value2;
            case '!=':
                return $value1 != $value2;
            default:
                return false;
        }
    }
}