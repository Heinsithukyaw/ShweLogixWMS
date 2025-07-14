<?php

namespace App\Http\Controllers\Api\Metrics;

use App\Http\Controllers\Controller;
use App\Models\Metrics\DataCollectionPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DataCollectionPointController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dataCollectionPoints = DataCollectionPoint::all();
        return response()->json(['data' => $dataCollectionPoints]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:data_collection_points',
            'collection_method' => 'required|string|in:system,manual,sensor,calculated',
            'data_type' => 'required|string|in:numeric,text,boolean,timestamp',
            'source_table' => 'nullable|string|max:255',
            'source_column' => 'nullable|string|max:255',
            'aggregation_method' => 'nullable|string|in:sum,avg,min,max,count',
            'validation_rule' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dataCollectionPoint = DataCollectionPoint::create($request->all());
        return response()->json(['data' => $dataCollectionPoint, 'message' => 'Data collection point created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $dataCollectionPoint = DataCollectionPoint::findOrFail($id);
        return response()->json(['data' => $dataCollectionPoint]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $dataCollectionPoint = DataCollectionPoint::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:data_collection_points,code,' . $id,
            'collection_method' => 'string|in:system,manual,sensor,calculated',
            'data_type' => 'string|in:numeric,text,boolean,timestamp',
            'source_table' => 'nullable|string|max:255',
            'source_column' => 'nullable|string|max:255',
            'aggregation_method' => 'nullable|string|in:sum,avg,min,max,count',
            'validation_rule' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dataCollectionPoint->update($request->all());
        return response()->json(['data' => $dataCollectionPoint, 'message' => 'Data collection point updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $dataCollectionPoint = DataCollectionPoint::findOrFail($id);
        $dataCollectionPoint->delete();
        return response()->json(['message' => 'Data collection point deleted successfully']);
    }
}