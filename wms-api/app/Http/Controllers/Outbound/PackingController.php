<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\PackOrder;
use App\Models\Outbound\PackedCarton;
use App\Models\Outbound\PackingStation;
use App\Models\Outbound\CartonType;
use App\Models\Outbound\PackingMaterial;
use App\Models\Outbound\PackingValidation;
use App\Models\Outbound\PackingQualityCheck;
use App\Models\Outbound\MultiCartonShipment;
use App\Models\SalesOrder;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PackingController extends Controller
{
    /**
     * Get all packing stations
     *
     * @return \Illuminate\Http\Response
     */
    public function getPackingStations()
    {
        $packingStations = PackingStation::with(['warehouse', 'zone', 'employee'])
            ->orderBy('station_name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $packingStations
        ]);
    }

    /**
     * Get a specific packing station
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getPackingStation($id)
    {
        $packingStation = PackingStation::with(['warehouse', 'zone', 'employee'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $packingStation
        ]);
    }

    /**
     * Create a new packing station
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createPackingStation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'station_name' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'zone_id' => 'nullable|exists:zones,id',
            'station_type' => 'required|in:standard,express,fragile,oversized,multi_order',
            'station_status' => 'required|in:active,inactive,maintenance',
            'capabilities' => 'nullable|json',
            'max_weight_kg' => 'nullable|numeric',
            'equipment_list' => 'nullable|json',
            'assigned_to' => 'nullable|exists:employees,id',
            'is_automated' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a unique station code
        $stationCode = 'PS-' . strtoupper(Str::random(6));
        
        $packingStation = PackingStation::create([
            'station_code' => $stationCode,
            'station_name' => $request->station_name,
            'warehouse_id' => $request->warehouse_id,
            'zone_id' => $request->zone_id,
            'station_type' => $request->station_type,
            'station_status' => $request->station_status,
            'capabilities' => $request->capabilities,
            'max_weight_kg' => $request->max_weight_kg,
            'equipment_list' => $request->equipment_list,
            'assigned_to' => $request->assigned_to,
            'is_automated' => $request->is_automated ?? false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Packing station created successfully',
            'data' => $packingStation
        ], 201);
    }

    /**
     * Update a packing station
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updatePackingStation(Request $request, $id)
    {
        $packingStation = PackingStation::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'station_name' => 'string|max:255',
            'warehouse_id' => 'exists:warehouses,id',
            'zone_id' => 'nullable|exists:zones,id',
            'station_type' => 'in:standard,express,fragile,oversized,multi_order',
            'station_status' => 'in:active,inactive,maintenance',
            'capabilities' => 'nullable|json',
            'max_weight_kg' => 'nullable|numeric',
            'equipment_list' => 'nullable|json',
            'assigned_to' => 'nullable|exists:employees,id',
            'is_automated' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $packingStation->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Packing station updated successfully',
            'data' => $packingStation
        ]);
    }

    /**
     * Get all carton types
     *
     * @return \Illuminate\Http\Response
     */
    public function getCartonTypes()
    {
        $cartonTypes = CartonType::where('is_active', true)
            ->orderBy('carton_name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $cartonTypes
        ]);
    }

    /**
     * Create a new carton type
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createCartonType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'carton_name' => 'required|string|max:255',
            'length_cm' => 'required|numeric',
            'width_cm' => 'required|numeric',
            'height_cm' => 'required|numeric',
            'max_weight_kg' => 'required|numeric',
            'tare_weight_kg' => 'required|numeric',
            'carton_material' => 'required|in:cardboard,plastic,wood,metal',
            'cost_per_unit' => 'required|numeric',
            'usage_rules' => 'nullable|json',
            'supplier' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a unique carton code
        $cartonCode = 'CT-' . strtoupper(Str::random(6));
        
        // Calculate volume
        $volume = $request->length_cm * $request->width_cm * $request->height_cm;
        
        $cartonType = CartonType::create([
            'carton_code' => $cartonCode,
            'carton_name' => $request->carton_name,
            'length_cm' => $request->length_cm,
            'width_cm' => $request->width_cm,
            'height_cm' => $request->height_cm,
            'max_weight_kg' => $request->max_weight_kg,
            'tare_weight_kg' => $request->tare_weight_kg,
            'volume_cm3' => $volume,
            'carton_material' => $request->carton_material,
            'cost_per_unit' => $request->cost_per_unit,
            'usage_rules' => $request->usage_rules,
            'supplier' => $request->supplier,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Carton type created successfully',
            'data' => $cartonType
        ], 201);
    }

    /**
     * Get pending pack orders
     *
     * @return \Illuminate\Http\Response
     */
    public function getPendingPackOrders()
    {
        $packOrders = PackOrder::with(['salesOrder', 'packingStation', 'employee'])
            ->whereIn('pack_status', ['pending', 'assigned'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $packOrders
        ]);
    }

    /**
     * Create a new pack order
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createPackOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sales_order_id' => 'required|exists:sales_orders,id',
            'packing_station_id' => 'required|exists:packing_stations,id',
            'assigned_to' => 'nullable|exists:employees,id',
            'pack_priority' => 'required|in:low,normal,high,urgent',
            'total_items' => 'required|integer|min:1',
            'estimated_time' => 'nullable|numeric',
            'packing_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a unique pack order number
        $packOrderNumber = 'PO-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        
        $packOrder = PackOrder::create([
            'pack_order_number' => $packOrderNumber,
            'sales_order_id' => $request->sales_order_id,
            'packing_station_id' => $request->packing_station_id,
            'assigned_to' => $request->assigned_to,
            'pack_status' => $request->assigned_to ? 'assigned' : 'pending',
            'pack_priority' => $request->pack_priority,
            'total_items' => $request->total_items,
            'packed_items' => 0,
            'estimated_time' => $request->estimated_time,
            'assigned_at' => $request->assigned_to ? now() : null,
            'packing_notes' => $request->packing_notes,
            'created_by' => auth()->id()
        ]);

        // If assigned, update the packing station
        if ($request->assigned_to) {
            $packingStation = PackingStation::find($request->packing_station_id);
            $packingStation->assigned_to = $request->assigned_to;
            $packingStation->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Pack order created successfully',
            'data' => $packOrder
        ], 201);
    }

    /**
     * Start packing process
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function startPacking(Request $request, $id)
    {
        $packOrder = PackOrder::findOrFail($id);
        
        if (!in_array($packOrder->pack_status, ['pending', 'assigned'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pack order cannot be started in its current status'
            ], 422);
        }
        
        $packOrder->pack_status = 'in_progress';
        $packOrder->started_at = now();
        $packOrder->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Packing process started',
            'data' => $packOrder
        ]);
    }

    /**
     * Create a packed carton
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createPackedCarton(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pack_order_id' => 'required|exists:pack_orders,id',
            'sales_order_id' => 'required|exists:sales_orders,id',
            'carton_type_id' => 'required|exists:carton_types,id',
            'packing_station_id' => 'required|exists:packing_stations,id',
            'packed_by' => 'required|exists:employees,id',
            'carton_sequence' => 'required|integer|min:1',
            'gross_weight_kg' => 'required|numeric',
            'net_weight_kg' => 'required|numeric',
            'actual_length_cm' => 'nullable|numeric',
            'actual_width_cm' => 'nullable|numeric',
            'actual_height_cm' => 'nullable|numeric',
            'packed_items' => 'required|json',
            'materials_used' => 'nullable|json',
            'packing_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a unique carton number
        $cartonNumber = 'CN-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        
        DB::beginTransaction();
        
        try {
            $packedCarton = PackedCarton::create([
                'carton_number' => $cartonNumber,
                'pack_order_id' => $request->pack_order_id,
                'sales_order_id' => $request->sales_order_id,
                'carton_type_id' => $request->carton_type_id,
                'packing_station_id' => $request->packing_station_id,
                'packed_by' => $request->packed_by,
                'carton_sequence' => $request->carton_sequence,
                'gross_weight_kg' => $request->gross_weight_kg,
                'net_weight_kg' => $request->net_weight_kg,
                'actual_length_cm' => $request->actual_length_cm,
                'actual_width_cm' => $request->actual_width_cm,
                'actual_height_cm' => $request->actual_height_cm,
                'packed_items' => $request->packed_items,
                'materials_used' => $request->materials_used,
                'carton_status' => 'packed',
                'packed_at' => now(),
                'packing_notes' => $request->packing_notes
            ]);
            
            // Update pack order
            $packOrder = PackOrder::find($request->pack_order_id);
            $packedItems = count(json_decode($request->packed_items));
            $packOrder->packed_items += $packedItems;
            
            // If all items are packed, update status
            if ($packOrder->packed_items >= $packOrder->total_items) {
                $packOrder->pack_status = 'packed';
                $packOrder->completed_at = now();
                $packOrder->actual_time = $packOrder->started_at 
                    ? round((now()->timestamp - $packOrder->started_at->timestamp) / 3600, 2) 
                    : null;
            }
            
            $packOrder->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Carton packed successfully',
                'data' => $packedCarton
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create packed carton',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate a packed carton
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function validatePackedCarton(Request $request, $id)
    {
        $packedCarton = PackedCarton::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'validation_type' => 'required|in:weight,dimension,content,quality',
            'validation_status' => 'required|in:passed,failed,warning',
            'expected_value' => 'nullable|numeric',
            'actual_value' => 'nullable|numeric',
            'tolerance_percentage' => 'nullable|numeric',
            'validation_notes' => 'nullable|string',
            'validation_data' => 'nullable|json',
            'validated_by' => 'required|exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $validation = PackingValidation::create([
            'packed_carton_id' => $packedCarton->id,
            'validation_type' => $request->validation_type,
            'validation_status' => $request->validation_status,
            'expected_value' => $request->expected_value,
            'actual_value' => $request->actual_value,
            'tolerance_percentage' => $request->tolerance_percentage ?? 5.00,
            'validation_notes' => $request->validation_notes,
            'validation_data' => $request->validation_data,
            'validated_by' => $request->validated_by,
            'validated_at' => now()
        ]);
        
        // If validation passed, update carton status
        if ($request->validation_status === 'passed') {
            $packedCarton->carton_status = 'verified';
            $packedCarton->verified_by = $request->validated_by;
            $packedCarton->verified_at = now();
            $packedCarton->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Carton validation completed',
            'data' => $validation
        ]);
    }

    /**
     * Perform quality check on a packed carton
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function qualityCheckCarton(Request $request, $id)
    {
        $packedCarton = PackedCarton::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'quality_checker_id' => 'required|exists:employees,id',
            'quality_criteria' => 'required|json',
            'check_results' => 'required|json',
            'overall_result' => 'required|in:passed,failed,conditional',
            'quality_score' => 'nullable|numeric|min:0|max:100',
            'defects_found' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
            'requires_repack' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $qualityCheck = PackingQualityCheck::create([
            'packed_carton_id' => $packedCarton->id,
            'quality_checker_id' => $request->quality_checker_id,
            'quality_criteria' => $request->quality_criteria,
            'check_results' => $request->check_results,
            'overall_result' => $request->overall_result,
            'quality_score' => $request->quality_score,
            'defects_found' => $request->defects_found,
            'corrective_actions' => $request->corrective_actions,
            'requires_repack' => $request->requires_repack ?? false,
            'checked_at' => now()
        ]);
        
        // If quality check failed and requires repack, update carton status
        if ($request->overall_result === 'failed' && $request->requires_repack) {
            $packedCarton->carton_status = 'damaged';
            $packedCarton->save();
            
            // Create a new pack order for repacking if needed
            // This would be implemented based on business rules
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Quality check completed',
            'data' => $qualityCheck
        ]);
    }

    /**
     * Create a multi-carton shipment
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createMultiCartonShipment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sales_order_id' => 'required|exists:sales_orders,id',
            'carton_ids' => 'required|json',
            'total_cartons' => 'required|integer|min:1',
            'total_weight_kg' => 'required|numeric',
            'total_volume_cm3' => 'required|numeric',
            'master_tracking_number' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $multiCartonShipment = MultiCartonShipment::create([
            'sales_order_id' => $request->sales_order_id,
            'master_tracking_number' => $request->master_tracking_number,
            'total_cartons' => $request->total_cartons,
            'carton_ids' => $request->carton_ids,
            'total_weight_kg' => $request->total_weight_kg,
            'total_volume_cm3' => $request->total_volume_cm3,
            'shipment_status' => 'pending',
            'created_by' => auth()->id()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Multi-carton shipment created',
            'data' => $multiCartonShipment
        ], 201);
    }

    /**
     * Get optimal carton recommendation
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getCartonRecommendation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|json',
            'total_weight_kg' => 'required|numeric',
            'max_dimensions' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $items = json_decode($request->items, true);
        $totalWeight = $request->total_weight_kg;
        $maxDimensions = json_decode($request->max_dimensions ?? '{}', true);
        
        // Get all active carton types
        $cartonTypes = CartonType::where('is_active', true)
            ->where('max_weight_kg', '>=', $totalWeight)
            ->get();
        
        // Filter by max dimensions if provided
        if (!empty($maxDimensions)) {
            $cartonTypes = $cartonTypes->filter(function($carton) use ($maxDimensions) {
                return (!isset($maxDimensions['length']) || $carton->length_cm <= $maxDimensions['length']) &&
                       (!isset($maxDimensions['width']) || $carton->width_cm <= $maxDimensions['width']) &&
                       (!isset($maxDimensions['height']) || $carton->height_cm <= $maxDimensions['height']);
            });
        }
        
        // Sort by volume (smallest first)
        $cartonTypes = $cartonTypes->sortBy('volume_cm3')->values();
        
        // Return the top 3 recommendations
        $recommendations = $cartonTypes->take(3);
        
        return response()->json([
            'success' => true,
            'data' => [
                'recommendations' => $recommendations,
                'total_items' => count($items),
                'total_weight_kg' => $totalWeight
            ]
        ]);
    }

    /**
     * Get packing materials inventory
     *
     * @return \Illuminate\Http\Response
     */
    public function getPackingMaterials()
    {
        $packingMaterials = PackingMaterial::where('is_active', true)
            ->orderBy('material_name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $packingMaterials
        ]);
    }

    /**
     * Update packing materials inventory
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updatePackingMaterialInventory(Request $request, $id)
    {
        $packingMaterial = PackingMaterial::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'quantity_change' => 'required|integer',
            'reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $packingMaterial->current_stock += $request->quantity_change;
        $packingMaterial->save();
        
        // Log the inventory change
        // This would be implemented based on inventory tracking requirements
        
        return response()->json([
            'success' => true,
            'message' => 'Packing material inventory updated',
            'data' => $packingMaterial
        ]);
    }
}