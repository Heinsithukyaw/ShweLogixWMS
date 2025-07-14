<?php

namespace App\Http\Controllers\Admin\api\v1\warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseLayout;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseLayoutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $query = WarehouseLayout::query();
        
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        
        $layouts = $query->with('warehouse')->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $layouts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'layout_data' => 'required|json',
            'is_active' => 'boolean',
            'is_simulation' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $layout = WarehouseLayout::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse layout created successfully',
            'data' => $layout
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $layout = WarehouseLayout::with('warehouse', 'optimizationMetrics')->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $layout
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $layout = WarehouseLayout::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'exists:warehouses,id',
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'layout_data' => 'json',
            'is_active' => 'boolean',
            'is_simulation' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $layout->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse layout updated successfully',
            'data' => $layout
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $layout = WarehouseLayout::findOrFail($id);
        $layout->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse layout deleted successfully'
        ]);
    }
    
    /**
     * Activate a layout and deactivate others for the same warehouse.
     */
    public function activate(string $id)
    {
        $layout = WarehouseLayout::findOrFail($id);
        
        // Deactivate all layouts for this warehouse
        WarehouseLayout::where('warehouse_id', $layout->warehouse_id)
            ->where('id', '!=', $id)
            ->update(['is_active' => false]);
        
        // Activate this layout
        $layout->is_active = true;
        $layout->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse layout activated successfully',
            'data' => $layout
        ]);
    }
    
    /**
     * Clone a layout for simulation purposes.
     */
    public function clone(string $id)
    {
        $originalLayout = WarehouseLayout::findOrFail($id);
        
        $newLayout = $originalLayout->replicate();
        $newLayout->name = $originalLayout->name . ' (Simulation)';
        $newLayout->is_active = false;
        $newLayout->is_simulation = true;
        $newLayout->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse layout cloned for simulation',
            'data' => $newLayout
        ]);
    }
}