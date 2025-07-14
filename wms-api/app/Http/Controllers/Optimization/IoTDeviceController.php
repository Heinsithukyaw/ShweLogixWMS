<?php

namespace App\Http\Controllers\Optimization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IoTDevice;
use App\Models\IoTDeviceData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class IoTDeviceController extends Controller
{
    /**
     * Display a listing of IoT devices.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = IoTDevice::query();
            
            // Apply filters
            if ($request->has('device_type')) {
                $query->where('device_type', $request->device_type);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }
            
            if ($request->has('location_id')) {
                $query->where('location_id', $request->location_id);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('device_name', 'like', "%{$search}%")
                      ->orWhere('device_id', 'like', "%{$search}%")
                      ->orWhere('serial_number', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $devices = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $devices,
                'message' => 'IoT devices retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving IoT devices: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve IoT devices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created IoT device.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'device_name' => 'required|string|max:100',
                'device_id' => 'required|string|max:50|unique:iot_devices',
                'device_type' => 'required|string|in:temperature_sensor,humidity_sensor,motion_sensor,proximity_sensor,rfid_reader,barcode_scanner,camera,weight_sensor,light_sensor,pressure_sensor',
                'serial_number' => 'required|string|max:50|unique:iot_devices',
                'manufacturer' => 'required|string|max:100',
                'model' => 'required|string|max:100',
                'firmware_version' => 'required|string|max:50',
                'warehouse_id' => 'required|exists:warehouses,id',
                'location_id' => 'nullable|exists:locations,id',
                'zone_id' => 'nullable|exists:zones,id',
                'installation_date' => 'required|date',
                'last_maintenance_date' => 'nullable|date',
                'next_maintenance_date' => 'nullable|date',
                'configuration' => 'required|json',
                'status' => 'required|string|in:active,inactive,maintenance,error',
                'notes' => 'nullable|string',
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
                'data' => $device,
                'message' => 'IoT device created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating IoT device: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create IoT device',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified IoT device.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $device = IoTDevice::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $device,
                'message' => 'IoT device retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving IoT device: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'IoT device not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified IoT device.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $device = IoTDevice::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'device_name' => 'string|max:100',
                'device_id' => 'string|max:50|unique:iot_devices,device_id,' . $id,
                'device_type' => 'string|in:temperature_sensor,humidity_sensor,motion_sensor,proximity_sensor,rfid_reader,barcode_scanner,camera,weight_sensor,light_sensor,pressure_sensor',
                'serial_number' => 'string|max:50|unique:iot_devices,serial_number,' . $id,
                'manufacturer' => 'string|max:100',
                'model' => 'string|max:100',
                'firmware_version' => 'string|max:50',
                'warehouse_id' => 'exists:warehouses,id',
                'location_id' => 'nullable|exists:locations,id',
                'zone_id' => 'nullable|exists:zones,id',
                'installation_date' => 'date',
                'last_maintenance_date' => 'nullable|date',
                'next_maintenance_date' => 'nullable|date',
                'configuration' => 'json',
                'status' => 'string|in:active,inactive,maintenance,error',
                'notes' => 'nullable|string',
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
                'data' => $device,
                'message' => 'IoT device updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating IoT device: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update IoT device',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified IoT device.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $device = IoTDevice::findOrFail($id);
            
            // Check if there is any data associated with this device
            $dataCount = IoTDeviceData::where('device_id', $id)->count();
            
            if ($dataCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete device with associated data. Please delete the data first or deactivate the device instead.'
                ], 400);
            }
            
            $device->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'IoT device deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting IoT device: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete IoT device',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record data from an IoT device.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function recordData(Request $request, $id)
    {
        try {
            $device = IoTDevice::findOrFail($id);
            
            // Check if device is active
            if ($device->status !== 'active') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Device is not active'
                ], 400);
            }
            
            $validator = Validator::make($request->all(), [
                'data_value' => 'required|numeric',
                'data_unit' => 'required|string|max:20',
                'data_type' => 'required|string|max:50',
                'recorded_at' => 'nullable|date',
                'metadata' => 'nullable|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $data = IoTDeviceData::create([
                'device_id' => $id,
                'data_value' => $request->data_value,
                'data_unit' => $request->data_unit,
                'data_type' => $request->data_type,
                'recorded_at' => $request->recorded_at ?? now(),
                'metadata' => $request->metadata,
            ]);
            
            // Update device last_data_received_at
            $device->update([
                'last_data_received_at' => now()
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $data,
                'message' => 'IoT device data recorded successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error recording IoT device data: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to record IoT device data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data from an IoT device.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getData(Request $request, $id)
    {
        try {
            $device = IoTDevice::findOrFail($id);
            
            $query = IoTDeviceData::where('device_id', $id);
            
            // Apply filters
            if ($request->has('data_type')) {
                $query->where('data_type', $request->data_type);
            }
            
            if ($request->has('date_from')) {
                $query->whereDate('recorded_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('recorded_at', '<=', $request->date_to);
            }
            
            if ($request->has('min_value')) {
                $query->where('data_value', '>=', $request->min_value);
            }
            
            if ($request->has('max_value')) {
                $query->where('data_value', '<=', $request->max_value);
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'recorded_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 100);
            $data = $query->paginate($perPage);
            
            // Calculate statistics
            $stats = [
                'count' => $data->total(),
                'min' => $query->min('data_value'),
                'max' => $query->max('data_value'),
                'avg' => $query->avg('data_value'),
                'first_record' => $query->orderBy('recorded_at', 'asc')->first()?->recorded_at,
                'last_record' => $query->orderBy('recorded_at', 'desc')->first()?->recorded_at,
            ];
            
            return response()->json([
                'status' => 'success',
                'device' => [
                    'id' => $device->id,
                    'device_name' => $device->device_name,
                    'device_type' => $device->device_type,
                    'status' => $device->status
                ],
                'data' => $data,
                'statistics' => $stats,
                'message' => 'IoT device data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving IoT device data: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve IoT device data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the status of an IoT device.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getStatus($id)
    {
        try {
            $device = IoTDevice::findOrFail($id);
            
            // Get the latest data point
            $latestData = IoTDeviceData::where('device_id', $id)
                ->orderBy('recorded_at', 'desc')
                ->first();
            
            // Check if device is online based on last data received
            $isOnline = false;
            $lastDataAge = null;
            
            if ($device->last_data_received_at) {
                $lastDataAge = now()->diffInMinutes($device->last_data_received_at);
                $isOnline = $lastDataAge < 15; // Consider online if data received in last 15 minutes
            }
            
            // Check if maintenance is due
            $maintenanceDue = false;
            $daysToMaintenance = null;
            
            if ($device->next_maintenance_date) {
                $daysToMaintenance = now()->diffInDays($device->next_maintenance_date, false);
                $maintenanceDue = $daysToMaintenance <= 0;
            }
            
            $status = [
                'device_id' => $device->id,
                'device_name' => $device->device_name,
                'status' => $device->status,
                'is_online' => $isOnline,
                'last_data_received_at' => $device->last_data_received_at,
                'last_data_age_minutes' => $lastDataAge,
                'latest_data' => $latestData,
                'firmware_version' => $device->firmware_version,
                'maintenance_due' => $maintenanceDue,
                'days_to_maintenance' => $daysToMaintenance,
                'last_maintenance_date' => $device->last_maintenance_date,
                'next_maintenance_date' => $device->next_maintenance_date,
                'installation_date' => $device->installation_date,
                'location' => [
                    'warehouse_id' => $device->warehouse_id,
                    'location_id' => $device->location_id,
                    'zone_id' => $device->zone_id
                ]
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $status,
                'message' => 'IoT device status retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving IoT device status: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve IoT device status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}