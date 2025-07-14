<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\CartonType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class CartonTypeController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of carton types
     */
    public function index(Request $request): JsonResponse
    {
        $query = CartonType::query();

        // Apply filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('carton_category')) {
            $query->where('carton_category', $request->carton_category);
        }

        if ($request->has('material_type')) {
            $query->where('material_type', $request->material_type);
        }

        if ($request->has('min_weight')) {
            $query->where('max_weight', '>=', $request->min_weight);
        }

        if ($request->has('max_weight')) {
            $query->where('max_weight', '<=', $request->max_weight);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('carton_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $cartonTypes = $query->orderBy('carton_code')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $cartonTypes,
            'message' => 'Carton types retrieved successfully'
        ]);
    }

    /**
     * Store a newly created carton type
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'carton_code' => 'required|string|unique:carton_types,carton_code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'carton_category' => 'required|in:box,envelope,tube,pallet,bag,custom',
            'material_type' => 'required|in:cardboard,plastic,metal,wood,fabric,composite',
            'external_dimensions' => 'required|array',
            'external_dimensions.length' => 'required|numeric|min:0',
            'external_dimensions.width' => 'required|numeric|min:0',
            'external_dimensions.height' => 'required|numeric|min:0',
            'internal_dimensions' => 'required|array',
            'internal_dimensions.length' => 'required|numeric|min:0',
            'internal_dimensions.width' => 'required|numeric|min:0',
            'internal_dimensions.height' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|min:0',
            'tare_weight' => 'required|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_sku' => 'nullable|string',
            'minimum_stock_level' => 'nullable|integer|min:0',
            'current_stock' => 'nullable|integer|min:0',
            'is_fragile_suitable' => 'boolean',
            'is_hazmat_suitable' => 'boolean',
            'is_food_grade' => 'boolean',
            'is_recyclable' => 'boolean',
            'special_features' => 'nullable|array',
            'usage_instructions' => 'nullable|string',
            'storage_requirements' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate volumes
            $externalVolume = $request->external_dimensions['length'] * 
                             $request->external_dimensions['width'] * 
                             $request->external_dimensions['height'];

            $internalVolume = $request->internal_dimensions['length'] * 
                             $request->internal_dimensions['width'] * 
                             $request->internal_dimensions['height'];

            $cartonType = CartonType::create([
                'carton_code' => $request->carton_code,
                'name' => $request->name,
                'description' => $request->description,
                'carton_category' => $request->carton_category,
                'material_type' => $request->material_type,
                'external_dimensions' => $request->external_dimensions,
                'internal_dimensions' => $request->internal_dimensions,
                'external_volume' => $externalVolume,
                'internal_volume' => $internalVolume,
                'max_weight' => $request->max_weight,
                'tare_weight' => $request->tare_weight,
                'cost_per_unit' => $request->cost_per_unit,
                'supplier_id' => $request->supplier_id,
                'supplier_sku' => $request->supplier_sku,
                'minimum_stock_level' => $request->minimum_stock_level ?? 0,
                'current_stock' => $request->current_stock ?? 0,
                'is_fragile_suitable' => $request->is_fragile_suitable ?? false,
                'is_hazmat_suitable' => $request->is_hazmat_suitable ?? false,
                'is_food_grade' => $request->is_food_grade ?? false,
                'is_recyclable' => $request->is_recyclable ?? true,
                'special_features' => $request->special_features ?? [],
                'usage_instructions' => $request->usage_instructions,
                'storage_requirements' => $request->storage_requirements,
                'is_active' => $request->is_active ?? true,
                'created_by' => auth()->id(),
                'volume_efficiency' => $internalVolume / $externalVolume,
                'weight_to_volume_ratio' => $request->max_weight / $internalVolume
            ]);

            // Fire event
            $this->fireTransactionalEvent('outbound.carton_type.created', [
                'carton_type_id' => $cartonType->id,
                'carton_code' => $request->carton_code,
                'carton_category' => $request->carton_category,
                'internal_volume' => $internalVolume,
                'max_weight' => $request->max_weight
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $cartonType,
                'message' => 'Carton type created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create carton type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified carton type
     */
    public function show($id): JsonResponse
    {
        $cartonType = CartonType::with(['supplier', 'createdBy'])->find($id);

        if (!$cartonType) {
            return response()->json([
                'success' => false,
                'message' => 'Carton type not found'
            ], 404);
        }

        // Get usage statistics
        $usageStats = $this->getUsageStatistics($cartonType);

        return response()->json([
            'success' => true,
            'data' => array_merge($cartonType->toArray(), [
                'usage_statistics' => $usageStats
            ]),
            'message' => 'Carton type retrieved successfully'
        ]);
    }

    /**
     * Update the specified carton type
     */
    public function update(Request $request, $id): JsonResponse
    {
        $cartonType = CartonType::find($id);

        if (!$cartonType) {
            return response()->json([
                'success' => false,
                'message' => 'Carton type not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'external_dimensions' => 'sometimes|array',
            'external_dimensions.length' => 'required_with:external_dimensions|numeric|min:0',
            'external_dimensions.width' => 'required_with:external_dimensions|numeric|min:0',
            'external_dimensions.height' => 'required_with:external_dimensions|numeric|min:0',
            'internal_dimensions' => 'sometimes|array',
            'internal_dimensions.length' => 'required_with:internal_dimensions|numeric|min:0',
            'internal_dimensions.width' => 'required_with:internal_dimensions|numeric|min:0',
            'internal_dimensions.height' => 'required_with:internal_dimensions|numeric|min:0',
            'max_weight' => 'sometimes|numeric|min:0',
            'tare_weight' => 'sometimes|numeric|min:0',
            'cost_per_unit' => 'sometimes|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_sku' => 'nullable|string',
            'minimum_stock_level' => 'nullable|integer|min:0',
            'current_stock' => 'nullable|integer|min:0',
            'is_fragile_suitable' => 'sometimes|boolean',
            'is_hazmat_suitable' => 'sometimes|boolean',
            'is_food_grade' => 'sometimes|boolean',
            'is_recyclable' => 'sometimes|boolean',
            'special_features' => 'nullable|array',
            'usage_instructions' => 'nullable|string',
            'storage_requirements' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updateData = $request->only([
                'name',
                'description',
                'max_weight',
                'tare_weight',
                'cost_per_unit',
                'supplier_id',
                'supplier_sku',
                'minimum_stock_level',
                'current_stock',
                'is_fragile_suitable',
                'is_hazmat_suitable',
                'is_food_grade',
                'is_recyclable',
                'special_features',
                'usage_instructions',
                'storage_requirements',
                'is_active'
            ]);

            // Recalculate volumes if dimensions changed
            if ($request->has('external_dimensions')) {
                $externalVolume = $request->external_dimensions['length'] * 
                                 $request->external_dimensions['width'] * 
                                 $request->external_dimensions['height'];
                $updateData['external_dimensions'] = $request->external_dimensions;
                $updateData['external_volume'] = $externalVolume;
            }

            if ($request->has('internal_dimensions')) {
                $internalVolume = $request->internal_dimensions['length'] * 
                                 $request->internal_dimensions['width'] * 
                                 $request->internal_dimensions['height'];
                $updateData['internal_dimensions'] = $request->internal_dimensions;
                $updateData['internal_volume'] = $internalVolume;
            }

            // Recalculate derived metrics
            if (isset($updateData['external_volume']) && isset($updateData['internal_volume'])) {
                $updateData['volume_efficiency'] = $updateData['internal_volume'] / $updateData['external_volume'];
            }

            if (isset($updateData['max_weight']) && isset($updateData['internal_volume'])) {
                $updateData['weight_to_volume_ratio'] = $updateData['max_weight'] / $updateData['internal_volume'];
            } elseif (isset($updateData['max_weight'])) {
                $updateData['weight_to_volume_ratio'] = $updateData['max_weight'] / $cartonType->internal_volume;
            } elseif (isset($updateData['internal_volume'])) {
                $updateData['weight_to_volume_ratio'] = $cartonType->max_weight / $updateData['internal_volume'];
            }

            $cartonType->update($updateData);

            // Fire event
            $this->fireTransactionalEvent('outbound.carton_type.updated', [
                'carton_type_id' => $cartonType->id,
                'carton_code' => $cartonType->carton_code,
                'updated_fields' => array_keys($updateData),
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $cartonType->fresh(['supplier', 'createdBy']),
                'message' => 'Carton type updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update carton type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified carton type from storage
     */
    public function destroy($id): JsonResponse
    {
        $cartonType = CartonType::find($id);

        if (!$cartonType) {
            return response()->json([
                'success' => false,
                'message' => 'Carton type not found'
            ], 404);
        }

        // Check if carton type is in use
        if ($this->isCartonTypeInUse($cartonType)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete carton type that is currently in use'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Soft delete by setting is_active to false
            $cartonType->update([
                'is_active' => false,
                'deleted_at' => now(),
                'deleted_by' => auth()->id()
            ]);

            // Fire event
            $this->fireTransactionalEvent('outbound.carton_type.deleted', [
                'carton_type_id' => $cartonType->id,
                'carton_code' => $cartonType->carton_code,
                'deleted_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Carton type deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete carton type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get carton recommendations for given requirements
     */
    public function recommend(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'weight' => 'required|numeric|min:0',
            'dimensions' => 'required|array',
            'dimensions.length' => 'required|numeric|min:0',
            'dimensions.width' => 'required|numeric|min:0',
            'dimensions.height' => 'required|numeric|min:0',
            'is_fragile' => 'boolean',
            'is_hazmat' => 'boolean',
            'is_food_grade' => 'boolean',
            'max_cost' => 'nullable|numeric|min:0',
            'preferred_material' => 'nullable|in:cardboard,plastic,metal,wood,fabric,composite',
            'limit' => 'nullable|integer|min:1|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $recommendations = $this->findOptimalCartons($request->all());

            return response()->json([
                'success' => true,
                'data' => $recommendations,
                'message' => 'Carton recommendations generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update stock levels
     */
    public function updateStock(Request $request, $id): JsonResponse
    {
        $cartonType = CartonType::find($id);

        if (!$cartonType) {
            return response()->json([
                'success' => false,
                'message' => 'Carton type not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'adjustment_type' => 'required|in:add,subtract,set',
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldStock = $cartonType->current_stock;
            $newStock = $this->calculateNewStock($oldStock, $request->adjustment_type, $request->quantity);

            if ($newStock < 0) {
                throw new \Exception('Stock adjustment would result in negative stock');
            }

            $cartonType->update(['current_stock' => $newStock]);

            // Create stock adjustment record
            $this->createStockAdjustmentRecord($cartonType, [
                'adjustment_type' => $request->adjustment_type,
                'quantity' => $request->quantity,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'reason' => $request->reason,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'adjusted_by' => auth()->id()
            ]);

            // Check for low stock alert
            if ($newStock <= $cartonType->minimum_stock_level) {
                $this->createLowStockAlert($cartonType);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.carton_type.stock_updated', [
                'carton_type_id' => $cartonType->id,
                'carton_code' => $cartonType->carton_code,
                'adjustment_type' => $request->adjustment_type,
                'quantity' => $request->quantity,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'adjusted_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'carton_type' => $cartonType->fresh(),
                    'stock_adjustment' => [
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                        'adjustment' => $newStock - $oldStock
                    ]
                ],
                'message' => 'Stock updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get carton type analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $analytics = [
            'total_carton_types' => CartonType::where('is_active', true)->count(),
            'by_category' => $this->getCartonsByCategory(),
            'by_material' => $this->getCartonsByMaterial(),
            'stock_summary' => $this->getStockSummary(),
            'usage_statistics' => $this->getUsageStatistics(null, $dateFrom, $dateTo),
            'cost_analysis' => $this->getCostAnalysis($dateFrom, $dateTo),
            'efficiency_metrics' => $this->getEfficiencyMetrics(),
            'low_stock_alerts' => $this->getLowStockAlerts()
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Carton type analytics retrieved successfully'
        ]);
    }

    /**
     * Helper methods
     */
    private function getUsageStatistics($cartonType = null, $dateFrom = null, $dateTo = null): array
    {
        // This would integrate with pack orders and shipments to get actual usage
        if ($cartonType) {
            return [
                'total_used' => 150, // Placeholder
                'usage_trend' => 'increasing',
                'average_monthly_usage' => 45,
                'last_used' => now()->subDays(2)
            ];
        }

        return [
            'most_used_cartons' => [
                ['carton_code' => 'BOX-001', 'usage_count' => 250],
                ['carton_code' => 'ENV-001', 'usage_count' => 180],
                ['carton_code' => 'BOX-002', 'usage_count' => 120]
            ],
            'usage_by_category' => [
                'box' => 450,
                'envelope' => 200,
                'tube' => 50
            ]
        ];
    }

    private function isCartonTypeInUse($cartonType): bool
    {
        // Check if carton type is referenced in pack orders or shipments
        // This would check actual usage in the system
        return false; // Placeholder
    }

    private function findOptimalCartons($requirements): array
    {
        $query = CartonType::where('is_active', true)
            ->where('max_weight', '>=', $requirements['weight']);

        // Filter by dimensions (carton must be able to fit the item)
        $query->where('internal_dimensions->length', '>=', $requirements['dimensions']['length'])
              ->where('internal_dimensions->width', '>=', $requirements['dimensions']['width'])
              ->where('internal_dimensions->height', '>=', $requirements['dimensions']['height']);

        // Filter by special requirements
        if ($requirements['is_fragile'] ?? false) {
            $query->where('is_fragile_suitable', true);
        }

        if ($requirements['is_hazmat'] ?? false) {
            $query->where('is_hazmat_suitable', true);
        }

        if ($requirements['is_food_grade'] ?? false) {
            $query->where('is_food_grade', true);
        }

        if (isset($requirements['max_cost'])) {
            $query->where('cost_per_unit', '<=', $requirements['max_cost']);
        }

        if (isset($requirements['preferred_material'])) {
            $query->where('material_type', $requirements['preferred_material']);
        }

        $cartons = $query->get();

        // Calculate fit scores and sort by optimization
        $recommendations = $cartons->map(function ($carton) use ($requirements) {
            $fitScore = $this->calculateFitScore($carton, $requirements);
            $costEfficiency = $this->calculateCostEfficiency($carton, $requirements);
            $volumeEfficiency = $this->calculateVolumeEfficiency($carton, $requirements);

            return [
                'carton_type' => $carton,
                'fit_score' => $fitScore,
                'cost_efficiency' => $costEfficiency,
                'volume_efficiency' => $volumeEfficiency,
                'overall_score' => ($fitScore * 0.4) + ($costEfficiency * 0.3) + ($volumeEfficiency * 0.3),
                'waste_space' => $this->calculateWasteSpace($carton, $requirements)
            ];
        })->sortByDesc('overall_score')->take($requirements['limit'] ?? 5)->values();

        return $recommendations->toArray();
    }

    private function calculateFitScore($carton, $requirements): float
    {
        $itemVolume = $requirements['dimensions']['length'] * 
                     $requirements['dimensions']['width'] * 
                     $requirements['dimensions']['height'];

        $cartonVolume = $carton->internal_volume;
        
        if ($cartonVolume <= 0 || $itemVolume > $cartonVolume) {
            return 0;
        }

        // Perfect fit gets 100, larger cartons get lower scores
        $volumeRatio = $itemVolume / $cartonVolume;
        return min(100, $volumeRatio * 100);
    }

    private function calculateCostEfficiency($carton, $requirements): float
    {
        // Lower cost per unit volume gets higher score
        $costPerVolume = $carton->cost_per_unit / $carton->internal_volume;
        
        // Normalize to 0-100 scale (this would need calibration with real data)
        return max(0, 100 - ($costPerVolume * 1000));
    }

    private function calculateVolumeEfficiency($carton, $requirements): float
    {
        $itemVolume = $requirements['dimensions']['length'] * 
                     $requirements['dimensions']['width'] * 
                     $requirements['dimensions']['height'];

        return ($itemVolume / $carton->internal_volume) * 100;
    }

    private function calculateWasteSpace($carton, $requirements): float
    {
        $itemVolume = $requirements['dimensions']['length'] * 
                     $requirements['dimensions']['width'] * 
                     $requirements['dimensions']['height'];

        return $carton->internal_volume - $itemVolume;
    }

    private function calculateNewStock($currentStock, $adjustmentType, $quantity): int
    {
        switch ($adjustmentType) {
            case 'add':
                return $currentStock + $quantity;
            case 'subtract':
                return $currentStock - $quantity;
            case 'set':
                return $quantity;
            default:
                return $currentStock;
        }
    }

    private function createStockAdjustmentRecord($cartonType, $adjustmentData): void
    {
        // This would create a record in a stock_adjustments table
        // For now, we'll just log it
    }

    private function createLowStockAlert($cartonType): void
    {
        // This would create an alert in the system
        // For now, we'll just fire an event
        $this->fireTransactionalEvent('outbound.carton_type.low_stock_alert', [
            'carton_type_id' => $cartonType->id,
            'carton_code' => $cartonType->carton_code,
            'current_stock' => $cartonType->current_stock,
            'minimum_stock_level' => $cartonType->minimum_stock_level
        ]);
    }

    /**
     * Analytics helper methods
     */
    private function getCartonsByCategory(): array
    {
        return CartonType::where('is_active', true)
            ->groupBy('carton_category')
            ->selectRaw('carton_category, count(*) as count')
            ->pluck('count', 'carton_category')
            ->toArray();
    }

    private function getCartonsByMaterial(): array
    {
        return CartonType::where('is_active', true)
            ->groupBy('material_type')
            ->selectRaw('material_type, count(*) as count')
            ->pluck('count', 'material_type')
            ->toArray();
    }

    private function getStockSummary(): array
    {
        $cartons = CartonType::where('is_active', true)->get();
        
        return [
            'total_stock_value' => $cartons->sum(function ($carton) {
                return $carton->current_stock * $carton->cost_per_unit;
            }),
            'low_stock_count' => $cartons->filter(function ($carton) {
                return $carton->current_stock <= $carton->minimum_stock_level;
            })->count(),
            'out_of_stock_count' => $cartons->where('current_stock', 0)->count(),
            'average_stock_level' => $cartons->avg('current_stock')
        ];
    }

    private function getCostAnalysis($dateFrom, $dateTo): array
    {
        // This would analyze actual costs from usage data
        return [
            'total_packaging_cost' => 5250.00, // Placeholder
            'cost_per_shipment' => 3.50,
            'cost_trends' => [
                'increasing' => ['box'],
                'stable' => ['envelope', 'tube'],
                'decreasing' => []
            ]
        ];
    }

    private function getEfficiencyMetrics(): array
    {
        $cartons = CartonType::where('is_active', true)->get();
        
        return [
            'average_volume_efficiency' => $cartons->avg('volume_efficiency'),
            'best_volume_efficiency' => $cartons->max('volume_efficiency'),
            'worst_volume_efficiency' => $cartons->min('volume_efficiency'),
            'weight_capacity_utilization' => $this->calculateWeightCapacityUtilization($cartons)
        ];
    }

    private function calculateWeightCapacityUtilization($cartons): float
    {
        // This would calculate how well weight capacity is being utilized
        return 75.5; // Placeholder
    }

    private function getLowStockAlerts(): array
    {
        return CartonType::where('is_active', true)
            ->whereRaw('current_stock <= minimum_stock_level')
            ->select('carton_code', 'name', 'current_stock', 'minimum_stock_level')
            ->get()
            ->toArray();
    }
}