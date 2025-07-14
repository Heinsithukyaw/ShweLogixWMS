<?php

namespace App\Services\ECommerce;

use App\Models\ECommerce\OrderFulfillment;
use App\Models\ECommerce\OrderFulfillmentItem;
use App\Models\SalesOrder;
use App\Models\ProductInventory;
use App\Events\OrderFulfillmentCreated;
use App\Events\OrderFulfillmentStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderFulfillmentService
{
    /**
     * Create a new order fulfillment
     */
    public function createFulfillment(array $data): OrderFulfillment
    {
        return DB::transaction(function () use ($data) {
            // Create the main fulfillment record
            $fulfillment = OrderFulfillment::create([
                'sales_order_id' => $data['sales_order_id'],
                'fulfillment_status' => 'pending',
                'fulfillment_type' => $data['fulfillment_type'],
                'priority_level' => $data['priority_level'],
                'shipping_carrier_id' => $data['shipping_carrier_id'] ?? null,
                'automation_rules' => $data['automation_rules'] ?? null,
                'created_by' => auth()->id()
            ]);

            // Create fulfillment items
            foreach ($data['items'] as $itemData) {
                $fulfillmentItem = OrderFulfillmentItem::create([
                    'order_fulfillment_id' => $fulfillment->id,
                    'sales_order_item_id' => $itemData['sales_order_item_id'],
                    'product_id' => $itemData['product_id'] ?? null,
                    'quantity_ordered' => $itemData['quantity_ordered'],
                    'quantity_fulfilled' => 0,
                    'quantity_remaining' => $itemData['quantity_ordered'],
                    'fulfillment_status' => 'pending'
                ]);

                // Calculate weight and volume if product data is available
                if ($fulfillmentItem->product) {
                    $fulfillmentItem->weight = $fulfillmentItem->product->weight * $itemData['quantity_ordered'];
                    $fulfillmentItem->volume = $fulfillmentItem->product->volume * $itemData['quantity_ordered'];
                    $fulfillmentItem->save();
                }
            }

            // Set estimated ship date based on priority
            $this->setEstimatedShipDate($fulfillment);

            // Fire event
            event(new OrderFulfillmentCreated($fulfillment));

            return $fulfillment;
        });
    }

    /**
     * Process automated fulfillment
     */
    public function processAutomatedFulfillment(OrderFulfillment $fulfillment): array
    {
        if (!$fulfillment->canAutomate()) {
            throw new \Exception('Fulfillment cannot be automated');
        }

        $results = [];
        $automationRules = $fulfillment->automation_rules;

        try {
            DB::beginTransaction();

            // Check inventory availability
            $inventoryCheck = $this->checkInventoryAvailability($fulfillment);
            if (!$inventoryCheck['available']) {
                throw new \Exception('Insufficient inventory for automated fulfillment');
            }

            // Auto-assign pick locations
            if ($automationRules['auto_pick_location'] ?? false) {
                $this->assignPickLocations($fulfillment);
                $results['pick_locations_assigned'] = true;
            }

            // Auto-select shipping carrier
            if ($automationRules['auto_carrier_selection'] ?? false) {
                $carrier = $this->selectOptimalCarrier($fulfillment);
                $fulfillment->shipping_carrier_id = $carrier->id;
                $fulfillment->save();
                $results['carrier_selected'] = $carrier->name;
            }

            // Calculate shipping cost
            if ($fulfillment->shippingCarrier) {
                $shippingCost = $fulfillment->calculateShippingCost();
                $fulfillment->shipping_cost = $shippingCost;
                $fulfillment->save();
                $results['shipping_cost_calculated'] = $shippingCost;
            }

            // Update status to in_progress
            $fulfillment->updateStatus('in_progress', 'Automated processing initiated');
            $results['status_updated'] = 'in_progress';

            DB::commit();

            Log::info('Automated fulfillment processed successfully', [
                'fulfillment_id' => $fulfillment->id,
                'results' => $results
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Automated fulfillment processing failed', [
                'fulfillment_id' => $fulfillment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get fulfillment analytics
     */
    public function getFulfillmentAnalytics(array $filters = []): array
    {
        $query = OrderFulfillment::query();

        // Apply date filters
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $analytics = [
            'total_fulfillments' => $query->count(),
            'status_breakdown' => $query->groupBy('fulfillment_status')
                ->selectRaw('fulfillment_status, count(*) as count')
                ->pluck('count', 'fulfillment_status'),
            'priority_breakdown' => $query->groupBy('priority_level')
                ->selectRaw('priority_level, count(*) as count')
                ->pluck('count', 'priority_level'),
            'average_fulfillment_time' => $this->calculateAverageFulfillmentTime($filters),
            'automation_rate' => $this->calculateAutomationRate($filters),
            'shipping_cost_analysis' => $this->getShippingCostAnalysis($filters)
        ];

        return $analytics;
    }

    /**
     * Check inventory availability for fulfillment
     */
    private function checkInventoryAvailability(OrderFulfillment $fulfillment): array
    {
        $available = true;
        $details = [];

        foreach ($fulfillment->fulfillmentItems as $item) {
            $inventory = ProductInventory::where('product_id', $item->product_id)
                ->where('available_quantity', '>=', $item->quantity_remaining)
                ->first();

            $itemAvailable = $inventory !== null;
            $available = $available && $itemAvailable;

            $details[] = [
                'product_id' => $item->product_id,
                'required_quantity' => $item->quantity_remaining,
                'available_quantity' => $inventory->available_quantity ?? 0,
                'available' => $itemAvailable
            ];
        }

        return [
            'available' => $available,
            'details' => $details
        ];
    }

    /**
     * Assign pick locations for fulfillment items
     */
    private function assignPickLocations(OrderFulfillment $fulfillment): void
    {
        foreach ($fulfillment->fulfillmentItems as $item) {
            // Find optimal pick location based on inventory and location efficiency
            $pickLocation = ProductInventory::where('product_id', $item->product_id)
                ->where('available_quantity', '>=', $item->quantity_remaining)
                ->join('locations', 'product_inventories.location_id', '=', 'locations.id')
                ->orderBy('locations.pick_sequence')
                ->first();

            if ($pickLocation) {
                $item->pick_location = $pickLocation->location_code;
                $item->save();
            }
        }
    }

    /**
     * Select optimal shipping carrier
     */
    private function selectOptimalCarrier(OrderFulfillment $fulfillment)
    {
        // This would integrate with carrier APIs to get real-time rates
        // For now, we'll use a simple selection based on cost and service level
        
        $totalWeight = $fulfillment->fulfillmentItems->sum('weight');
        $totalVolume = $fulfillment->fulfillmentItems->sum('volume');
        
        // Logic to select carrier based on weight, volume, destination, and priority
        return $fulfillment->salesOrder->shippingCarrier ?? 
               \App\Models\ShippingCarrier::where('is_active', true)->first();
    }

    /**
     * Set estimated ship date based on priority and processing time
     */
    private function setEstimatedShipDate(OrderFulfillment $fulfillment): void
    {
        $processingDays = match ($fulfillment->priority_level) {
            'urgent' => 0, // Same day
            'high' => 1,   // Next day
            'medium' => 2, // 2 days
            'low' => 3     // 3 days
        };

        $fulfillment->estimated_ship_date = now()->addDays($processingDays);
        $fulfillment->save();
    }

    /**
     * Calculate average fulfillment time
     */
    private function calculateAverageFulfillmentTime(array $filters): ?float
    {
        $query = OrderFulfillment::whereNotNull('actual_ship_date');

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, actual_ship_date)) as avg_hours')
            ->value('avg_hours');
    }

    /**
     * Calculate automation rate
     */
    private function calculateAutomationRate(array $filters): float
    {
        $totalQuery = OrderFulfillment::query();
        $automatedQuery = OrderFulfillment::whereNotNull('automation_rules');

        if (isset($filters['date_from'])) {
            $totalQuery->whereDate('created_at', '>=', $filters['date_from']);
            $automatedQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $totalQuery->whereDate('created_at', '<=', $filters['date_to']);
            $automatedQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        $total = $totalQuery->count();
        $automated = $automatedQuery->count();

        return $total > 0 ? ($automated / $total) * 100 : 0;
    }

    /**
     * Get shipping cost analysis
     */
    private function getShippingCostAnalysis(array $filters): array
    {
        $query = OrderFulfillment::whereNotNull('shipping_cost');

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_shipping_cost' => $query->sum('shipping_cost'),
            'average_shipping_cost' => $query->avg('shipping_cost'),
            'min_shipping_cost' => $query->min('shipping_cost'),
            'max_shipping_cost' => $query->max('shipping_cost')
        ];
    }
}