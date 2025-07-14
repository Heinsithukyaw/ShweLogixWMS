<?php

namespace App\Services\Outbound;

use App\Models\Outbound\PackOrder;
use App\Models\Outbound\PackedCarton;
use App\Models\Outbound\CartonType;
use App\Models\Outbound\PackingStation;
use App\Models\Outbound\PackingMaterial;
use App\Models\InventoryItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Exception;
use DB;

class PackingService
{
    /**
     * Recommend the optimal carton type for a set of items
     *
     * @param array $items
     * @return array
     */
    public function recommendCarton(array $items)
    {
        // Get all active carton types
        $cartonTypes = CartonType::where('is_active', true)->get();
        
        // Calculate total volume and weight of items
        $totalVolume = 0;
        $totalWeight = 0;
        $itemDetails = [];
        
        foreach ($items as $item) {
            $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);
            $quantity = $item['quantity'];
            
            $itemVolume = $inventoryItem->length_cm * $inventoryItem->width_cm * $inventoryItem->height_cm * $quantity;
            $itemWeight = $inventoryItem->weight_kg * $quantity;
            
            $totalVolume += $itemVolume;
            $totalWeight += $itemWeight;
            
            $itemDetails[] = [
                'inventory_item' => $inventoryItem,
                'quantity' => $quantity,
                'volume' => $itemVolume,
                'weight' => $itemWeight
            ];
        }
        
        // Add 10% buffer for packing materials
        $requiredVolume = $totalVolume * 1.1;
        $requiredWeight = $totalWeight * 1.05;
        
        // Find suitable cartons
        $suitableCartons = [];
        foreach ($cartonTypes as $cartonType) {
            if ($cartonType->volume_cm3 >= $requiredVolume && $cartonType->max_weight_capacity_kg >= $requiredWeight) {
                // Calculate utilization percentage
                $volumeUtilization = ($requiredVolume / $cartonType->volume_cm3) * 100;
                $weightUtilization = ($requiredWeight / $cartonType->max_weight_capacity_kg) * 100;
                
                $suitableCartons[] = [
                    'carton_type' => $cartonType,
                    'volume_utilization' => $volumeUtilization,
                    'weight_utilization' => $weightUtilization,
                    'overall_utilization' => ($volumeUtilization + $weightUtilization) / 2
                ];
            }
        }
        
        // Sort by overall utilization (higher is better)
        usort($suitableCartons, function($a, $b) {
            return $b['overall_utilization'] <=> $a['overall_utilization'];
        });
        
        // Return the best carton or suggest multiple cartons if no single carton is suitable
        if (count($suitableCartons) > 0) {
            return [
                'recommended_carton' => $suitableCartons[0]['carton_type'],
                'volume_utilization' => $suitableCartons[0]['volume_utilization'],
                'weight_utilization' => $suitableCartons[0]['weight_utilization'],
                'overall_utilization' => $suitableCartons[0]['overall_utilization'],
                'total_items' => count($items),
                'total_volume_cm3' => $totalVolume,
                'total_weight_kg' => $totalWeight,
                'required_volume_cm3' => $requiredVolume,
                'required_weight_kg' => $requiredWeight,
                'alternative_cartons' => array_slice($suitableCartons, 1, 2),
                'multiple_cartons_needed' => false
            ];
        } else {
            // No single carton is suitable, suggest multiple cartons
            return $this->recommendMultipleCartons($itemDetails, $cartonTypes);
        }
    }
    
    /**
     * Recommend multiple cartons when a single carton is not suitable
     *
     * @param array $itemDetails
     * @param \Illuminate\Database\Eloquent\Collection $cartonTypes
     * @return array
     */
    private function recommendMultipleCartons(array $itemDetails, $cartonTypes)
    {
        // Sort carton types by volume (largest first)
        $sortedCartonTypes = $cartonTypes->sortByDesc('volume_cm3')->values();
        
        // Sort items by volume (largest first)
        usort($itemDetails, function($a, $b) {
            return $b['volume'] <=> $a['volume'];
        });
        
        $cartonAssignments = [];
        $remainingItems = $itemDetails;
        
        while (count($remainingItems) > 0) {
            $largestCarton = $sortedCartonTypes[0];
            $currentCartonItems = [];
            $currentVolume = 0;
            $currentWeight = 0;
            
            // Try to fill the carton with items
            foreach ($remainingItems as $key => $item) {
                $newVolume = $currentVolume + $item['volume'];
                $newWeight = $currentWeight + $item['weight'];
                
                if ($newVolume <= $largestCarton->volume_cm3 * 0.9 && $newWeight <= $largestCarton->max_weight_capacity_kg * 0.95) {
                    $currentCartonItems[] = $item;
                    $currentVolume = $newVolume;
                    $currentWeight = $newWeight;
                    unset($remainingItems[$key]);
                }
            }
            
            // Reindex the array
            $remainingItems = array_values($remainingItems);
            
            if (count($currentCartonItems) > 0) {
                $cartonAssignments[] = [
                    'carton_type' => $largestCarton,
                    'items' => $currentCartonItems,
                    'volume_utilization' => ($currentVolume / $largestCarton->volume_cm3) * 100,
                    'weight_utilization' => ($currentWeight / $largestCarton->max_weight_capacity_kg) * 100
                ];
            } else {
                // If we couldn't fit any items, try the next largest carton
                array_shift($sortedCartonTypes);
                
                if (count($sortedCartonTypes) === 0) {
                    // No suitable cartons found
                    break;
                }
            }
        }
        
        return [
            'recommended_carton' => null,
            'multiple_cartons_needed' => true,
            'carton_assignments' => $cartonAssignments,
            'total_cartons_needed' => count($cartonAssignments),
            'remaining_items' => $remainingItems
        ];
    }
    
    /**
     * Check if a pack order is complete
     *
     * @param int $packOrderId
     * @return bool
     */
    public function checkPackOrderCompletion($packOrderId)
    {
        $packOrder = PackOrder::findOrFail($packOrderId);
        $salesOrder = SalesOrder::findOrFail($packOrder->sales_order_id);
        $salesOrderItems = SalesOrderItem::where('sales_order_id', $salesOrder->id)->get();
        
        // Get all packed items for this pack order
        $packedCartons = PackedCarton::where('pack_order_id', $packOrderId)->get();
        $packedItems = [];
        
        foreach ($packedCartons as $carton) {
            $cartonItems = $carton->items;
            foreach ($cartonItems as $item) {
                $inventoryItemId = $item->inventory_item_id;
                $quantity = $item->quantity;
                
                if (!isset($packedItems[$inventoryItemId])) {
                    $packedItems[$inventoryItemId] = 0;
                }
                
                $packedItems[$inventoryItemId] += $quantity;
            }
        }
        
        // Check if all sales order items have been packed
        $allItemsPacked = true;
        foreach ($salesOrderItems as $orderItem) {
            $inventoryItemId = $orderItem->inventory_item_id;
            $requiredQuantity = $orderItem->quantity;
            
            if (!isset($packedItems[$inventoryItemId]) || $packedItems[$inventoryItemId] < $requiredQuantity) {
                $allItemsPacked = false;
                break;
            }
        }
        
        // Update pack order status if all items are packed
        if ($allItemsPacked) {
            $packOrder->update([
                'pack_status' => 'completed',
                'completed_at' => now()
            ]);
            
            // Update sales order status
            $salesOrder->update([
                'order_status' => 'packed'
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Assign an employee to a packing station
     *
     * @param int $stationId
     * @param int $employeeId
     * @return \App\Models\Outbound\PackingStation
     */
    public function assignEmployeeToStation($stationId, $employeeId)
    {
        $packingStation = PackingStation::findOrFail($stationId);
        
        // Check if station is active
        if ($packingStation->station_status !== 'active') {
            throw new Exception('Cannot assign employee to inactive or maintenance station');
        }
        
        // Update the station
        $packingStation->update([
            'assigned_to' => $employeeId
        ]);
        
        return $packingStation;
    }
    
    /**
     * Calculate packing performance metrics
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function calculatePackingPerformance($employeeId = null, $startDate = null, $endDate = null)
    {
        $query = PackedCarton::query();
        
        if ($employeeId) {
            $query->where('packed_by', $employeeId);
        }
        
        if ($startDate) {
            $query->whereDate('packed_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('packed_at', '<=', $endDate);
        }
        
        $packedCartons = $query->get();
        
        // Calculate metrics
        $totalCartons = $packedCartons->count();
        $totalItems = $packedCartons->sum('total_items');
        $totalWeight = $packedCartons->sum('actual_weight_kg');
        
        $packingTimes = [];
        foreach ($packedCartons as $carton) {
            $packOrder = PackOrder::find($carton->pack_order_id);
            if ($packOrder && $packOrder->started_at && $carton->packed_at) {
                $packingTime = $carton->packed_at->diffInMinutes($packOrder->started_at);
                $packingTimes[] = $packingTime;
            }
        }
        
        $avgPackingTime = count($packingTimes) > 0 ? array_sum($packingTimes) / count($packingTimes) : 0;
        $cartonsPerHour = $avgPackingTime > 0 ? 60 / $avgPackingTime : 0;
        
        // Quality metrics
        $qualityChecks = DB::table('packing_quality_checks')
            ->whereIn('packed_carton_id', $packedCartons->pluck('id'))
            ->get();
        
        $totalChecks = $qualityChecks->count();
        $passedChecks = $qualityChecks->where('overall_result', 'passed')->count();
        $failedChecks = $qualityChecks->where('overall_result', 'failed')->count();
        $qualityRate = $totalChecks > 0 ? ($passedChecks / $totalChecks) * 100 : 0;
        
        return [
            'total_cartons' => $totalCartons,
            'total_items' => $totalItems,
            'total_weight_kg' => $totalWeight,
            'avg_packing_time_minutes' => $avgPackingTime,
            'cartons_per_hour' => $cartonsPerHour,
            'quality_checks' => $totalChecks,
            'passed_checks' => $passedChecks,
            'failed_checks' => $failedChecks,
            'quality_rate' => $qualityRate
        ];
    }
    
    /**
     * Track packing material usage
     *
     * @param int $materialId
     * @param int $quantity
     * @return \App\Models\Outbound\PackingMaterial
     */
    public function trackPackingMaterialUsage($materialId, $quantity)
    {
        $packingMaterial = PackingMaterial::findOrFail($materialId);
        
        // Update quantity on hand
        $newQuantity = $packingMaterial->quantity_on_hand - $quantity;
        if ($newQuantity < 0) {
            throw new Exception('Insufficient packing material quantity');
        }
        
        $packingMaterial->update([
            'quantity_on_hand' => $newQuantity,
            'last_used_at' => now()
        ]);
        
        // Check if reorder is needed
        if ($newQuantity <= $packingMaterial->reorder_point) {
            // Trigger reorder notification or process
            // This could be implemented as an event or direct notification
        }
        
        return $packingMaterial;
    }
}