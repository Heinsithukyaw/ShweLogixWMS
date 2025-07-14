<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Location;
use App\Events\Inventory\InventoryThresholdEvent;
use App\Traits\UsesTransactionalEvents;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Update inventory levels with transaction protection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateInventoryLevels(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'updates' => 'required|array|min:1',
            'updates.*.product_id' => 'required|exists:products,id',
            'updates.*.location_id' => 'required|exists:locations,id',
            'updates.*.quantity_change' => 'required|integer',
            'updates.*.reason' => 'required|string|max:255',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updates = $request->input('updates');
        $idempotencyKey = $request->input('idempotency_key');

        try {
            $result = $this->executeInventoryOperation(
                'bulk_update_levels',
                ['updates' => $updates],
                function ($payload) {
                    return $this->processBulkInventoryUpdate($payload['updates']);
                },
                $idempotencyKey
            );

            if ($result['was_duplicate']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory update already processed',
                    'data' => $result['result'],
                    'was_duplicate' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Inventory levels updated successfully',
                'data' => $result['result'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update inventory levels', [
                'updates_count' => count($updates),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory levels',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process bulk inventory update within transaction.
     *
     * @param  array  $updates
     * @return array
     */
    protected function processBulkInventoryUpdate(array $updates): array
    {
        $results = [];
        $thresholdEvents = [];

        foreach ($updates as $update) {
            $product = Product::findOrFail($update['product_id']);
            $location = Location::findOrFail($update['location_id']);

            // Update inventory level (this would typically involve inventory models)
            $newQuantity = $this->updateProductLocationQuantity(
                $product,
                $location,
                $update['quantity_change'],
                $update['reason']
            );

            $results[] = [
                'product_id' => $product->id,
                'location_id' => $location->id,
                'previous_quantity' => $newQuantity - $update['quantity_change'],
                'new_quantity' => $newQuantity,
                'change' => $update['quantity_change'],
            ];

            // Check for threshold violations
            $thresholdEvent = $this->checkInventoryThresholds($product, $location, $newQuantity);
            if ($thresholdEvent) {
                $thresholdEvents[] = $thresholdEvent;
            }
        }

        // Dispatch threshold events after successful inventory updates
        foreach ($thresholdEvents as $event) {
            event($event);
        }

        return [
            'updated_count' => count($results),
            'updates' => $results,
            'threshold_alerts' => count($thresholdEvents),
        ];
    }

    /**
     * Update product location quantity.
     *
     * @param  \App\Models\Product  $product
     * @param  \App\Models\Location  $location
     * @param  int  $quantityChange
     * @param  string  $reason
     * @return int New quantity
     */
    protected function updateProductLocationQuantity(
        Product $product,
        Location $location,
        int $quantityChange,
        string $reason
    ): int {
        // This is a simplified example - in reality, you'd have inventory models
        // and more complex logic for tracking inventory movements
        
        // For demonstration, we'll assume there's an inventory table/model
        // that tracks product quantities at specific locations
        
        // Simulate current quantity (in real implementation, fetch from inventory model)
        $currentQuantity = 100; // This would be fetched from database
        $newQuantity = $currentQuantity + $quantityChange;

        // Ensure quantity doesn't go negative
        if ($newQuantity < 0) {
            throw new \InvalidArgumentException("Insufficient inventory. Current: {$currentQuantity}, Requested change: {$quantityChange}");
        }

        // Log the inventory movement
        Log::info('Inventory quantity updated', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'previous_quantity' => $currentQuantity,
            'quantity_change' => $quantityChange,
            'new_quantity' => $newQuantity,
            'reason' => $reason,
        ]);

        return $newQuantity;
    }

    /**
     * Check inventory thresholds and return event if violated.
     *
     * @param  \App\Models\Product  $product
     * @param  \App\Models\Location  $location
     * @param  int  $currentQuantity
     * @return \App\Events\Inventory\InventoryThresholdEvent|null
     */
    protected function checkInventoryThresholds(
        Product $product,
        Location $location,
        int $currentQuantity
    ): ?InventoryThresholdEvent {
        // Example threshold values (in reality, these would be configurable per product/location)
        $lowStockThreshold = 10;
        $highStockThreshold = 1000;

        if ($currentQuantity <= $lowStockThreshold) {
            $severity = $currentQuantity <= 5 ? 'critical' : 'warning';
            
            return InventoryThresholdEvent::lowStock(
                $product,
                $location,
                $lowStockThreshold,
                $currentQuantity,
                $severity
            );
        }

        if ($currentQuantity >= $highStockThreshold) {
            return InventoryThresholdEvent::highStock(
                $product,
                $location,
                $highStockThreshold,
                $currentQuantity,
                'warning'
            );
        }

        return null;
    }

    /**
     * Transfer inventory between locations with transaction protection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferInventory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $request->only([
            'product_id',
            'from_location_id',
            'to_location_id',
            'quantity',
            'reason'
        ]);

        $idempotencyKey = $request->input('idempotency_key');

        try {
            $result = $this->executeInventoryOperation(
                'transfer_inventory',
                $payload,
                function ($payload) {
                    return $this->processInventoryTransfer($payload);
                },
                $idempotencyKey
            );

            if ($result['was_duplicate']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory transfer already processed',
                    'data' => $result['result'],
                    'was_duplicate' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Inventory transferred successfully',
                'data' => $result['result'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to transfer inventory', [
                'payload' => $payload,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer inventory',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process inventory transfer within transaction.
     *
     * @param  array  $payload
     * @return array
     */
    protected function processInventoryTransfer(array $payload): array
    {
        $product = Product::findOrFail($payload['product_id']);
        $fromLocation = Location::findOrFail($payload['from_location_id']);
        $toLocation = Location::findOrFail($payload['to_location_id']);
        $quantity = $payload['quantity'];
        $reason = $payload['reason'];

        // Decrease quantity at source location
        $fromNewQuantity = $this->updateProductLocationQuantity(
            $product,
            $fromLocation,
            -$quantity,
            "Transfer out: {$reason}"
        );

        // Increase quantity at destination location
        $toNewQuantity = $this->updateProductLocationQuantity(
            $product,
            $toLocation,
            $quantity,
            "Transfer in: {$reason}"
        );

        // Check thresholds at both locations
        $thresholdEvents = [];
        
        $fromThresholdEvent = $this->checkInventoryThresholds($product, $fromLocation, $fromNewQuantity);
        if ($fromThresholdEvent) {
            $thresholdEvents[] = $fromThresholdEvent;
        }

        $toThresholdEvent = $this->checkInventoryThresholds($product, $toLocation, $toNewQuantity);
        if ($toThresholdEvent) {
            $thresholdEvents[] = $toThresholdEvent;
        }

        // Dispatch threshold events
        foreach ($thresholdEvents as $event) {
            event($event);
        }

        return [
            'transfer_id' => uniqid('transfer_'),
            'product_id' => $product->id,
            'from_location' => [
                'id' => $fromLocation->id,
                'name' => $fromLocation->location_name,
                'new_quantity' => $fromNewQuantity,
            ],
            'to_location' => [
                'id' => $toLocation->id,
                'name' => $toLocation->location_name,
                'new_quantity' => $toNewQuantity,
            ],
            'transferred_quantity' => $quantity,
            'threshold_alerts' => count($thresholdEvents),
        ];
    }
}