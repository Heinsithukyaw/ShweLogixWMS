<?php

namespace App\Http\Controllers\ECommerce;

use App\Http\Controllers\Controller;
use App\Models\ECommerce\InventorySync;
use App\Services\ECommerce\InventorySyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventorySyncController extends Controller
{
    protected $syncService;

    public function __construct(InventorySyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Display a listing of inventory syncs
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventorySync::with(['product', 'productInventory']);

        // Apply filters
        if ($request->has('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->has('sync_status')) {
            $query->where('sync_status', $request->sync_status);
        }

        if ($request->has('needs_sync')) {
            $query->where(function ($q) {
                $q->where('sync_status', 'pending')
                  ->orWhereRaw('wms_quantity != platform_quantity')
                  ->orWhere('next_sync_at', '<=', now());
            });
        }

        $syncs = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $syncs
        ]);
    }

    /**
     * Store a newly created inventory sync configuration
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'platform' => 'required|string',
            'platform_product_id' => 'required|string',
            'sync_frequency' => 'required|in:real_time,every_15_minutes,hourly,daily',
            'sync_rules' => 'nullable|array'
        ]);

        try {
            $sync = InventorySync::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Inventory sync configuration created successfully',
                'data' => $sync->load(['product'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inventory sync configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified inventory sync
     */
    public function show($id): JsonResponse
    {
        $sync = InventorySync::with(['product', 'productInventory'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $sync
        ]);
    }

    /**
     * Update the specified inventory sync
     */
    public function update(Request $request, $id): JsonResponse
    {
        $sync = InventorySync::findOrFail($id);

        $request->validate([
            'sync_frequency' => 'sometimes|in:real_time,every_15_minutes,hourly,daily',
            'sync_rules' => 'sometimes|array',
            'platform_product_id' => 'sometimes|string'
        ]);

        try {
            $sync->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Inventory sync configuration updated successfully',
                'data' => $sync->fresh(['product'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory sync configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete the specified inventory sync
     */
    public function destroy($id): JsonResponse
    {
        try {
            $sync = InventorySync::findOrFail($id);
            $sync->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inventory sync configuration deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inventory sync configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync inventory for specific product
     */
    public function syncProduct($id): JsonResponse
    {
        try {
            $sync = InventorySync::findOrFail($id);
            $result = $this->syncService->syncProductInventory($sync);

            return response()->json([
                'success' => true,
                'message' => 'Product inventory synced successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync product inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync all pending inventory
     */
    public function syncAll(Request $request): JsonResponse
    {
        try {
            $platform = $request->get('platform');
            $result = $this->syncService->syncAllPendingInventory($platform);

            return response()->json([
                'success' => true,
                'message' => 'All pending inventory synced successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync all pending inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $platform = $request->get('platform');
            $stats = $this->syncService->getSyncStatistics($platform);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sync statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get failed syncs
     */
    public function failedSyncs(Request $request): JsonResponse
    {
        $query = InventorySync::with(['product'])
            ->where('sync_status', 'failed');

        if ($request->has('platform')) {
            $query->where('platform', $request->platform);
        }

        $failedSyncs = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $failedSyncs
        ]);
    }

    /**
     * Retry failed sync
     */
    public function retrySync($id): JsonResponse
    {
        try {
            $sync = InventorySync::findOrFail($id);
            
            if (!$sync->shouldRetry()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync cannot be retried (max retries reached or not in failed state)'
                ], 400);
            }

            $result = $this->syncService->syncProductInventory($sync);

            return response()->json([
                'success' => true,
                'message' => 'Sync retry completed',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry sync',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}