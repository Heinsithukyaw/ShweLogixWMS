<?php

namespace App\Http\Controllers\LayoutSimulation;

use App\Http\Controllers\Controller;
use App\Models\LayoutSimulation\LayoutSimulation;
use App\Models\LayoutSimulation\LayoutElement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LayoutSimulationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LayoutSimulation::with(['warehouse', 'createdBy']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $simulations = $query->orderBy('created_at', 'desc')
                            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $simulations
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'base_layout_id' => 'nullable|exists:layout_simulations,id',
            'layout_data' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $simulation = LayoutSimulation::create([
                'name' => $request->name,
                'description' => $request->description,
                'warehouse_id' => $request->warehouse_id,
                'base_layout_id' => $request->base_layout_id,
                'layout_data' => $request->layout_data,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Layout simulation created successfully',
                'data' => $simulation
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create simulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $simulation = LayoutSimulation::with(['warehouse', 'elements', 'scenarios'])
                                        ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $simulation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Simulation not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'layout_data' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $simulation = LayoutSimulation::findOrFail($id);
            $simulation->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Simulation updated successfully',
                'data' => $simulation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update simulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $simulation = LayoutSimulation::findOrFail($id);
            $simulation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Simulation deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete simulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addElement(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'element_type' => 'required|string',
            'element_name' => 'required|string',
            'position_x' => 'required|numeric',
            'position_y' => 'required|numeric',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $simulation = LayoutSimulation::findOrFail($id);

            $element = LayoutElement::create([
                'layout_simulation_id' => $id,
                'element_type' => $request->element_type,
                'element_name' => $request->element_name,
                'position_x' => $request->position_x,
                'position_y' => $request->position_y,
                'width' => $request->width,
                'height' => $request->height,
                'rotation' => $request->get('rotation', 0),
                'properties' => $request->get('properties', []),
                'constraints' => $request->get('constraints', [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Element added successfully',
                'data' => $element
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add element',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateElement(Request $request, $id, $elementId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'element_name' => 'string',
            'position_x' => 'numeric',
            'position_y' => 'numeric',
            'width' => 'numeric|min:1',
            'height' => 'numeric|min:1',
            'rotation' => 'numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $element = LayoutElement::where('layout_simulation_id', $id)
                                   ->findOrFail($elementId);

            $element->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Element updated successfully',
                'data' => $element
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update element',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function removeElement($id, $elementId): JsonResponse
    {
        try {
            $element = LayoutElement::where('layout_simulation_id', $id)
                                   ->findOrFail($elementId);

            $element->delete();

            return response()->json([
                'success' => true,
                'message' => 'Element removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove element',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function moveElement(Request $request, $id, $elementId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'position_x' => 'required|numeric',
            'position_y' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $element = LayoutElement::where('layout_simulation_id', $id)
                                   ->findOrFail($elementId);

            $element->move($request->position_x, $request->position_y);

            return response()->json([
                'success' => true,
                'message' => 'Element moved successfully',
                'data' => $element
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to move element',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resizeElement(Request $request, $id, $elementId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $element = LayoutElement::where('layout_simulation_id', $id)
                                   ->findOrFail($elementId);

            $element->resize($request->width, $request->height);

            return response()->json([
                'success' => true,
                'message' => 'Element resized successfully',
                'data' => $element
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resize element',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function runSimulation(Request $request, $id): JsonResponse
    {
        try {
            $simulation = LayoutSimulation::findOrFail($id);
            
            $parameters = $request->get('parameters', []);
            $results = $simulation->runSimulation($parameters);

            return response()->json([
                'success' => true,
                'message' => 'Simulation completed successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Simulation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getKPIPredictions($id): JsonResponse
    {
        try {
            $simulation = LayoutSimulation::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $simulation->kpi_predictions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get KPI predictions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPerformanceMetrics($id): JsonResponse
    {
        try {
            $simulation = LayoutSimulation::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $simulation->performance_metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function compareLayouts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'base_layout_id' => 'required|exists:layout_simulations,id',
            'comparison_layout_id' => 'required|exists:layout_simulations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $baseLayout = LayoutSimulation::findOrFail($request->base_layout_id);
            $comparisonLayout = LayoutSimulation::findOrFail($request->comparison_layout_id);

            $comparison = $baseLayout->compareWithScenario($comparisonLayout);

            return response()->json([
                'success' => true,
                'data' => $comparison
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to compare layouts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveLayout($id): JsonResponse
    {
        try {
            $simulation = LayoutSimulation::findOrFail($id);
            $simulation->save();

            return response()->json([
                'success' => true,
                'message' => 'Layout saved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save layout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportLayout($id): JsonResponse
    {
        try {
            $simulation = LayoutSimulation::findOrFail($id);
            $exportData = $simulation->exportLayout();

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'filename' => 'layout_' . $simulation->name . '_' . now()->format('Y-m-d') . '.json'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export layout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTemplates(): JsonResponse
    {
        try {
            $templates = [
                [
                    'id' => 1,
                    'name' => 'Standard Warehouse',
                    'description' => 'Basic warehouse layout with receiving, storage, and shipping areas',
                    'category' => 'standard'
                ],
                [
                    'id' => 2,
                    'name' => 'Cross-Dock Facility',
                    'description' => 'Optimized for cross-docking operations',
                    'category' => 'cross_dock'
                ],
                [
                    'id' => 3,
                    'name' => 'E-Commerce Fulfillment',
                    'description' => 'Designed for high-volume e-commerce operations',
                    'category' => 'ecommerce'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}