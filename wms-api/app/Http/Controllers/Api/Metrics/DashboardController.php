<?php

namespace App\Http\Controllers\Api\Metrics;

use App\Http\Controllers\Controller;
use App\Models\Metrics\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dashboards = Dashboard::all();
        return response()->json(['data' => $dashboards]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:operational,financial,executive,custom',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate slug from name
        $slug = Str::slug($request->name);
        
        // Check if slug exists
        $count = Dashboard::where('slug', 'like', $slug . '%')->count();
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }

        $dashboard = Dashboard::create(array_merge(
            $request->all(),
            ['slug' => $slug]
        ));
        
        return response()->json(['data' => $dashboard, 'message' => 'Dashboard created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $dashboard = Dashboard::with('widgets.metricVisualization.metricDefinition')->findOrFail($id);
        return response()->json(['data' => $dashboard]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $dashboard = Dashboard::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'category' => 'string|in:operational,financial,executive,custom',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update slug if name is changed
        if ($request->has('name') && $request->name !== $dashboard->name) {
            $slug = Str::slug($request->name);
            
            // Check if slug exists
            $count = Dashboard::where('slug', 'like', $slug . '%')
                ->where('id', '!=', $id)
                ->count();
                
            if ($count > 0) {
                $slug = $slug . '-' . ($count + 1);
            }
            
            $request->merge(['slug' => $slug]);
        }

        $dashboard->update($request->all());
        return response()->json(['data' => $dashboard, 'message' => 'Dashboard updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $dashboard = Dashboard::findOrFail($id);
        
        // Delete associated widgets
        $dashboard->widgets()->delete();
        
        $dashboard->delete();
        return response()->json(['message' => 'Dashboard deleted successfully']);
    }
}