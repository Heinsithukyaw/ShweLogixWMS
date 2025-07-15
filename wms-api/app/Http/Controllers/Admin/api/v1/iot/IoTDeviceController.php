<?php

namespace App\Http\Controllers\Admin\api\v1\iot;

use App\Http\Controllers\Controller;
use App\Models\IoTDevice;
use App\Models\IoTData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class IoTDeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $deviceType = $request->query('device_type');
        $isActive = $request->query('is_active');
        
        $query = IoTDevice::query();
        
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        
        if ($deviceType) {
            $query->where('device_type', $deviceType);
        }
        
        if ($isActive !== null) {
            $query->where('is_active', $isActive === 'true' || $isActive === '1');
        }
        
        $devices = $query->with(['warehouse', 'location'])->paginate(20);
        
        return response()->json([
            'status' => 'success',
            'data' => $devices
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|unique:iot_devices,device_id',
            'name' => 'required|string|max:255',
            'device_type' => 'required|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'location_id' => 'nullable|exists:locations,id',
            'configuration' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $device = IoTDevice::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'IoT device created successfully',
            'data' => $device
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $device = IoTDevice::with(['warehouse', 'location'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $device
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $device = IoTDevice::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'device_id' => 'string|unique:iot_devices,device_id,' . $id,
            'name' => 'string|max:255',
            'device_type' => 'string',
            'warehouse_id' => 'exists:warehouses,id',
            'location_id' => 'nullable|exists:locations,id',
            'configuration' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $device->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'IoT device updated successfully',
            'data' => $device
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $device = IoTDevice::findOrFail($id);
        $device->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'IoT device deleted successfully'
        ]);
    }
    
    /**
     * Get the latest data for a device.
     */
    public function latestData(string $id)
    {
        $device = IoTDevice::findOrFail($id);
        
        $latestData = IoTData::where('iot_device_id', $id)
            ->orderBy('recorded_at', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'device' => $device,
                'latest_data' => $latestData
            ]
        ]);
    }
    
    /**
     * Record data from an IoT device.
     */
    public function recordData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|exists:iot_devices,device_id',
            'data_type' => 'required|string',
            'data_value' => 'required|json',
            'recorded_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $device = IoTDevice::where('device_id', $request->input('device_id'))->first();
        
        $data = [
            'iot_device_id' => $device->id,
            'data_type' => $request->input('data_type'),
            'data_value' => $request->input('data_value'),
            'recorded_at' => $request->input('recorded_at', Carbon::now()),
        ];
        
        $iotData = IoTData::create($data);
        
        // Update last communication timestamp
        $device->last_communication = Carbon::now();
        $device->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'IoT data recorded successfully',
            'data' => $iotData
        ], 201);
    }
    
    /**
     * Get historical data for a device.
     */
    public function historicalData(string $id, Request $request)
    {
        $device = IoTDevice::findOrFail($id);
        
        $dataType = $request->query('data_type');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $query = IoTData::where('iot_device_id', $id);
        
        if ($dataType) {
            $query->where('data_type', $dataType);
        }
        
        if ($startDate) {
            $query->where('recorded_at', '>=', Carbon::parse($startDate));
        }
        
        if ($endDate) {
            $query->where('recorded_at', '<=', Carbon::parse($endDate));
        }
        
        $data = $query->orderBy('recorded_at')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'device' => $device,
                'historical_data' => $data
            ]
        ]);
    }
}