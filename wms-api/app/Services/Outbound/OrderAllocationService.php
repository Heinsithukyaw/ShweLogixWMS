<?php

namespace App\Services\Outbound;

use App\Models\Outbound\OrderAllocation;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\ProductInventory;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderAllocationService
{
    /**
     * Allocate inventory for entire sales order
     */
    public function allocateOrder(SalesOrder $salesOrder, string $allocationType = 'fifo', array $rules = null, $expiresAt = null): array
    {
        $allocations = [];
        
        DB::transaction(function () use ($salesOrder, $allocationType, $rules, $expiresAt, &$allocations) {
            foreach ($salesOrder->items as $item) {
                $itemAllocations = $this->allocateOrderItem($item, $allocationType, $rules, $expiresAt);
                $allocations = array_merge($allocations, $itemAllocations);
            }
        });
        
        return $allocations;
    }

    /**
     * Allocate inventory for specific order item
     */
    public function allocateOrderItem(SalesOrderItem $orderItem, string $allocationType = 'fifo', array $rules = null, $expiresAt = null): array
    {
        $allocations = [];
        $remainingQuantity = $orderItem->quantity;
        
        // Get available inventory based on allocation type
        $availableInventory = $this->getAvailableInventory(
            $orderItem->product_id,
            $remainingQuantity,
            $allocationType
        );
        
        foreach ($availableInventory as $inventory) {
            if ($remainingQuantity <= 0) {
                break;
            }
            
            $allocationQuantity = min($remainingQuantity, $inventory->available_quantity);
            
            $allocation = $this->createAllocation(
                $orderItem->sales_order_id,
                $orderItem->id,
                $orderItem->product_id,
                $inventory->location_id,
                $allocationQuantity,
                $allocationType,
                $inventory->lot_number,
                $inventory->serial_number,
                $rules,
                $expiresAt
            );
            
            $allocations[] = $allocation;
            $remainingQuantity -= $allocationQuantity;
            
            // Update inventory
            $inventory->available_quantity -= $allocationQuantity;
            $inventory->allocated_quantity += $allocationQuantity;
            $inventory->save();
        }
        
        // Create backorder if not fully allocated
        if ($remainingQuantity > 0) {
            $this->createBackorder($orderItem, $remainingQuantity);
        }
        
        return $allocations;
    }

    /**
     * Allocate specific item to specific location
     */
    public function allocateItem(int $orderItemId, int $locationId, float $quantity, string $allocationType, $lotNumber = null, $serialNumber = null, $expiresAt = null): OrderAllocation
    {
        $orderItem = SalesOrderItem::findOrFail($orderItemId);
        
        // Check available inventory at location
        $inventory = ProductInventory::where('product_id', $orderItem->product_id)
            ->where('location_id', $locationId)
            ->where('available_quantity', '>=', $quantity)
            ->when($lotNumber, function ($query, $lotNumber) {
                return $query->where('lot_number', $lotNumber);
            })
            ->when($serialNumber, function ($query, $serialNumber) {
                return $query->where('serial_number', $serialNumber);
            })
            ->first();
            
        if (!$inventory) {
            throw new \Exception('Insufficient inventory at specified location');
        }
        
        return DB::transaction(function () use ($orderItem, $locationId, $quantity, $allocationType, $lotNumber, $serialNumber, $expiresAt, $inventory) {
            $allocation = $this->createAllocation(
                $orderItem->sales_order_id,
                $orderItem->id,
                $orderItem->product_id,
                $locationId,
                $quantity,
                $allocationType,
                $lotNumber,
                $serialNumber,
                null,
                $expiresAt
            );
            
            // Update inventory
            $inventory->available_quantity -= $quantity;
            $inventory->allocated_quantity += $quantity;
            $inventory->save();
            
            return $allocation;
        });
    }

    /**
     * Get available inventory for allocation
     */
    public function getAvailableInventory(int $productId, float $quantityNeeded, string $allocationType): \Illuminate\Database\Eloquent\Collection
    {
        $query = ProductInventory::where('product_id', $productId)
            ->where('available_quantity', '>', 0)
            ->with('location');
            
        // Apply allocation strategy
        switch ($allocationType) {
            case 'fifo':
                $query->orderBy('received_date', 'asc');
                break;
            case 'lifo':
                $query->orderBy('received_date', 'desc');
                break;
            case 'fefo':
                $query->orderBy('expiry_date', 'asc');
                break;
            case 'manual':
                // No specific ordering for manual allocation
                break;
        }
        
        return $query->get();
    }

    /**
     * Create allocation record
     */
    protected function createAllocation(int $salesOrderId, int $orderItemId, int $productId, int $locationId, float $quantity, string $allocationType, $lotNumber = null, $serialNumber = null, array $rules = null, $expiresAt = null): OrderAllocation
    {
        return OrderAllocation::create([
            'sales_order_id' => $salesOrderId,
            'sales_order_item_id' => $orderItemId,
            'product_id' => $productId,
            'location_id' => $locationId,
            'lot_number' => $lotNumber,
            'serial_number' => $serialNumber,
            'allocated_quantity' => $quantity,
            'allocation_status' => 'allocated',
            'allocation_type' => $allocationType,
            'allocated_at' => now(),
            'expires_at' => $expiresAt,
            'allocation_rules' => $rules,
            'allocated_by' => Auth::id(),
        ]);
    }

    /**
     * Create backorder for unallocated quantity
     */
    protected function createBackorder(SalesOrderItem $orderItem, float $quantity)
    {
        // This would integrate with BackOrderService
        // For now, just log the backorder need
        \Log::info("Backorder needed for order item {$orderItem->id}: {$quantity} units");
    }

    /**
     * Cancel allocation and return inventory
     */
    public function cancelAllocation(OrderAllocation $allocation): void
    {
        DB::transaction(function () use ($allocation) {
            // Return inventory
            $inventory = ProductInventory::where('product_id', $allocation->product_id)
                ->where('location_id', $allocation->location_id)
                ->when($allocation->lot_number, function ($query, $lotNumber) {
                    return $query->where('lot_number', $lotNumber);
                })
                ->when($allocation->serial_number, function ($query, $serialNumber) {
                    return $query->where('serial_number', $serialNumber);
                })
                ->first();
                
            if ($inventory) {
                $remainingQuantity = $allocation->allocated_quantity - $allocation->picked_quantity;
                $inventory->available_quantity += $remainingQuantity;
                $inventory->allocated_quantity -= $remainingQuantity;
                $inventory->save();
            }
            
            $allocation->allocation_status = 'cancelled';
            $allocation->save();
        });
    }

    /**
     * Reallocate expired allocations
     */
    public function reallocateExpired(): array
    {
        $expiredAllocations = OrderAllocation::expired()
            ->where('allocation_status', 'allocated')
            ->get();
            
        $reallocated = [];
        
        foreach ($expiredAllocations as $allocation) {
            try {
                // Cancel expired allocation
                $this->cancelAllocation($allocation);
                
                // Try to reallocate
                $orderItem = $allocation->salesOrderItem;
                $newAllocations = $this->allocateOrderItem($orderItem, $allocation->allocation_type);
                
                $reallocated[] = [
                    'original_allocation' => $allocation,
                    'new_allocations' => $newAllocations
                ];
                
            } catch (\Exception $e) {
                \Log::error("Failed to reallocate expired allocation {$allocation->id}: " . $e->getMessage());
            }
        }
        
        return $reallocated;
    }

    /**
     * Get allocation summary for order
     */
    public function getOrderAllocationSummary(int $orderId): array
    {
        $allocations = OrderAllocation::where('sales_order_id', $orderId)
            ->with(['product', 'location'])
            ->get();
            
        $summary = [
            'total_allocations' => $allocations->count(),
            'allocated_items' => $allocations->where('allocation_status', 'allocated')->count(),
            'picked_items' => $allocations->where('allocation_status', 'picked')->count(),
            'partially_picked_items' => $allocations->where('allocation_status', 'partially_picked')->count(),
            'cancelled_items' => $allocations->where('allocation_status', 'cancelled')->count(),
            'total_allocated_quantity' => $allocations->sum('allocated_quantity'),
            'total_picked_quantity' => $allocations->sum('picked_quantity'),
            'allocation_percentage' => 0,
            'pick_percentage' => 0,
            'allocations_by_status' => $allocations->groupBy('allocation_status'),
            'allocations_by_location' => $allocations->groupBy('location.location_code'),
        ];
        
        if ($summary['total_allocated_quantity'] > 0) {
            $summary['pick_percentage'] = round(
                ($summary['total_picked_quantity'] / $summary['total_allocated_quantity']) * 100, 
                2
            );
        }
        
        return $summary;
    }

    /**
     * Bulk allocate multiple orders
     */
    public function bulkAllocateOrders(array $orderIds, string $allocationType = 'fifo', array $rules = null): array
    {
        $results = [];
        
        foreach ($orderIds as $orderId) {
            try {
                $salesOrder = SalesOrder::findOrFail($orderId);
                $allocations = $this->allocateOrder($salesOrder, $allocationType, $rules);
                
                $results[] = [
                    'order_id' => $orderId,
                    'status' => 'success',
                    'allocations' => $allocations,
                    'allocated_count' => count($allocations)
                ];
                
            } catch (\Exception $e) {
                $results[] = [
                    'order_id' => $orderId,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Auto-allocate orders based on priority
     */
    public function autoAllocateByPriority(int $limit = 50): array
    {
        $orders = SalesOrder::whereHas('orderPriority', function ($query) {
                $query->whereIn('priority_level', ['urgent', 'critical', 'high']);
            })
            ->whereDoesntHave('orderAllocations')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
            
        $results = [];
        
        foreach ($orders as $order) {
            try {
                $allocations = $this->allocateOrder($order);
                $results[] = [
                    'order_id' => $order->id,
                    'status' => 'allocated',
                    'allocations_count' => count($allocations)
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'order_id' => $order->id,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}