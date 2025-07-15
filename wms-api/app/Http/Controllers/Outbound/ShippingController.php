<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\Shipment;
use App\Models\Outbound\ShippingRate;
use App\Models\Outbound\RateShoppingResult;
use App\Models\Outbound\ShippingLabel;
use App\Models\Outbound\ShippingDocument;
use App\Models\Outbound\LoadPlan;
use App\Models\Outbound\LoadingDock;
use App\Models\Outbound\DockSchedule;
use App\Models\Outbound\LoadingConfirmation;
use App\Models\Outbound\DeliveryConfirmation;
use App\Models\Outbound\ShippingManifest;
use App\Models\Outbound\CustomerAnalytics;
use App\Models\Outbound\CarrierPerformance;
use App\Models\Outbound\PredictiveForecast;
use App\Models\Outbound\PackedCarton;
use App\Models\ShippingCarrier;
use App\Models\SalesOrder;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ShippingController extends Controller
{
    /**
     * Get all shipments
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getShipments(Request $request)
    {
        $query = Shipment::with(['carrier', 'customer']);
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('shipment_status', $request->status);
        }
        
        // Filter by date range if provided
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('ship_date', [$request->from_date, $request->to_date]);
        }
        
        $shipments = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $shipments
        ]);
    }

    /**
     * Get a specific shipment
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getShipment($id)
    {
        $shipment = Shipment::with([
            'carrier', 
            'customer', 
            'documents', 
            'labels',
            'loadPlan',
            'deliveryConfirmation'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $shipment
        ]);
    }

    /**
     * Create a new shipment
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createShipment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sales_order_ids' => 'required|json',
            'customer_id' => 'required|exists:business_parties,id',
            'shipping_carrier_id' => 'required|exists:shipping_carriers,id',
            'service_level' => 'required|string',
            'shipment_type' => 'required|in:standard,express,freight,ltl,parcel',
            'tracking_number' => 'nullable|string',
            'shipping_address' => 'required|json',
            'billing_address' => 'nullable|json',
            'total_weight_kg' => 'required|numeric',
            'total_volume_cm3' => 'required|numeric',
            'total_cartons' => 'required|integer|min:1',
            'shipping_cost' => 'nullable|numeric',
            'insurance_cost' => 'nullable|numeric',
            'special_services' => 'nullable|json',
            'ship_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'shipping_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a unique shipment number
        $shipmentNumber = 'SH-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        
        $shipment = Shipment::create([
            'shipment_number' => $shipmentNumber,
            'sales_order_ids' => $request->sales_order_ids,
            'customer_id' => $request->customer_id,
            'shipping_carrier_id' => $request->shipping_carrier_id,
            'service_level' => $request->service_level,
            'shipment_status' => 'planned',
            'shipment_type' => $request->shipment_type,
            'tracking_number' => $request->tracking_number,
            'shipping_address' => $request->shipping_address,
            'billing_address' => $request->billing_address,
            'total_weight_kg' => $request->total_weight_kg,
            'total_volume_cm3' => $request->total_volume_cm3,
            'total_cartons' => $request->total_cartons,
            'shipping_cost' => $request->shipping_cost,
            'insurance_cost' => $request->insurance_cost,
            'special_services' => $request->special_services,
            'ship_date' => $request->ship_date,
            'expected_delivery_date' => $request->expected_delivery_date,
            'shipping_notes' => $request->shipping_notes,
            'created_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully',
            'data' => $shipment
        ], 201);
    }

    /**
     * Update a shipment
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateShipment(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'shipping_carrier_id' => 'exists:shipping_carriers,id',
            'service_level' => 'string',
            'shipment_status' => 'in:planned,ready,picked_up,in_transit,delivered,exception',
            'tracking_number' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric',
            'insurance_cost' => 'nullable|numeric',
            'special_services' => 'nullable|json',
            'ship_date' => 'date',
            'expected_delivery_date' => 'nullable|date',
            'actual_delivery_date' => 'nullable|date',
            'shipping_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $shipment->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Shipment updated successfully',
            'data' => $shipment
        ]);
    }

    /**
     * Get shipping rates
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getShippingRates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_zip' => 'required|string',
            'destination_zip' => 'required|string',
            'weight_kg' => 'required|numeric',
            'shipping_carrier_id' => 'nullable|exists:shipping_carriers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $query = ShippingRate::where('is_active', true)
            ->where('origin_zip', $request->origin_zip)
            ->where('destination_zip', $request->destination_zip)
            ->where('weight_from_kg', '<=', $request->weight_kg)
            ->where('weight_to_kg', '>=', $request->weight_kg);
        
        // Filter by carrier if provided
        if ($request->has('shipping_carrier_id')) {
            $query->where('shipping_carrier_id', $request->shipping_carrier_id);
        }
        
        $rates = $query->with('carrier')->get();
        
        // Calculate total rate including fuel surcharge
        $rates->each(function($rate) {
            $rate->total_rate = $rate->base_rate * (1 + $rate->fuel_surcharge_rate);
        });
        
        // Sort by total rate
        $rates = $rates->sortBy('total_rate')->values();
        
        return response()->json([
            'success' => true,
            'data' => $rates
        ]);
    }

    /**
     * Perform rate shopping
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function performRateShopping(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sales_order_id' => 'required|exists:sales_orders,id',
            'origin_zip' => 'required|string',
            'destination_zip' => 'required|string',
            'total_weight_kg' => 'required|numeric',
            'total_volume_cm3' => 'required|numeric',
            'selection_criteria' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get rates from all carriers
        $carriers = ShippingCarrier::where('is_active', true)->get();
        $rateQuotes = [];
        
        foreach ($carriers as $carrier) {
            // In a real implementation, this would call carrier APIs
            // For this example, we'll simulate rate quotes
            $services = [
                'ground' => [
                    'cost' => rand(1000, 2000) / 100,
                    'transit_days' => rand(3, 7)
                ],
                'express' => [
                    'cost' => rand(2000, 3500) / 100,
                    'transit_days' => rand(1, 3)
                ],
                'overnight' => [
                    'cost' => rand(3500, 5000) / 100,
                    'transit_days' => 1
                ]
            ];
            
            $rateQuotes[] = [
                'carrier_id' => $carrier->id,
                'carrier_name' => $carrier->carrier_name,
                'services' => $services
            ];
        }
        
        // Determine best rate based on selection criteria
        $selectionCriteria = json_decode($request->selection_criteria ?? '{"priority": "cost"}', true);
        $priority = $selectionCriteria['priority'] ?? 'cost';
        
        $selectedCarrier = null;
        $selectedService = null;
        $selectedRate = null;
        
        if ($priority === 'cost') {
            // Find cheapest rate
            $lowestCost = PHP_FLOAT_MAX;
            
            foreach ($rateQuotes as $quote) {
                foreach ($quote['services'] as $service => $details) {
                    if ($details['cost'] < $lowestCost) {
                        $lowestCost = $details['cost'];
                        $selectedCarrier = $quote['carrier_id'];
                        $selectedService = $service;
                        $selectedRate = $details['cost'];
                    }
                }
            }
        } elseif ($priority === 'speed') {
            // Find fastest delivery
            $fastestDays = PHP_INT_MAX;
            
            foreach ($rateQuotes as $quote) {
                foreach ($quote['services'] as $service => $details) {
                    if ($details['transit_days'] < $fastestDays) {
                        $fastestDays = $details['transit_days'];
                        $selectedCarrier = $quote['carrier_id'];
                        $selectedService = $service;
                        $selectedRate = $details['cost'];
                    }
                }
            }
        }
        
        // Save rate shopping result
        $rateShoppingResult = RateShoppingResult::create([
            'sales_order_id' => $request->sales_order_id,
            'origin_zip' => $request->origin_zip,
            'destination_zip' => $request->destination_zip,
            'total_weight_kg' => $request->total_weight_kg,
            'total_volume_cm3' => $request->total_volume_cm3,
            'rate_quotes' => json_encode($rateQuotes),
            'selected_carrier_id' => $selectedCarrier,
            'selected_service_code' => $selectedService,
            'selected_rate' => $selectedRate,
            'selection_criteria' => $request->selection_criteria,
            'quoted_at' => now(),
            'expires_at' => now()->addHours(24),
            'requested_by' => auth()->id()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Rate shopping completed',
            'data' => [
                'rate_shopping_result' => $rateShoppingResult,
                'rate_quotes' => $rateQuotes,
                'selected' => [
                    'carrier_id' => $selectedCarrier,
                    'service' => $selectedService,
                    'rate' => $selectedRate
                ]
            ]
        ]);
    }

    /**
     * Generate shipping label
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateShippingLabel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
            'packed_carton_id' => 'nullable|exists:packed_cartons,id',
            'label_type' => 'required|string',
            'tracking_number' => 'required|string',
            'label_format' => 'required|in:PDF,PNG,ZPL'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // In a real implementation, this would call carrier APIs to generate labels
        // For this example, we'll simulate label generation
        
        // Generate a base64 encoded dummy label
        $labelData = base64_encode('DUMMY SHIPPING LABEL DATA');
        
        $shippingLabel = ShippingLabel::create([
            'shipment_id' => $request->shipment_id,
            'packed_carton_id' => $request->packed_carton_id,
            'label_type' => $request->label_type,
            'tracking_number' => $request->tracking_number,
            'label_data' => $labelData,
            'label_format' => $request->label_format,
            'label_metadata' => json_encode([
                'dimensions' => '4x6',
                'dpi' => '300'
            ]),
            'is_printed' => false
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Shipping label generated',
            'data' => $shippingLabel
        ]);
    }

    /**
     * Generate shipping document
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateShippingDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
            'document_type' => 'required|in:bill_of_lading,packing_slip,commercial_invoice,customs_form,hazmat_form',
            'document_format' => 'required|in:PDF,XML'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // In a real implementation, this would generate actual documents
        // For this example, we'll simulate document generation
        
        // Generate a document number
        $documentNumber = strtoupper($request->document_type[0]) . '-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        
        // Generate a base64 encoded dummy document
        $documentData = base64_encode('DUMMY SHIPPING DOCUMENT DATA');
        
        $shippingDocument = ShippingDocument::create([
            'shipment_id' => $request->shipment_id,
            'document_type' => $request->document_type,
            'document_number' => $documentNumber,
            'document_data' => $documentData,
            'document_format' => $request->document_format,
            'document_metadata' => json_encode([
                'pages' => 1,
                'created_at' => now()->toIso8601String()
            ]),
            'is_required' => true,
            'is_generated' => true,
            'generated_at' => now(),
            'generated_by' => auth()->id()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Shipping document generated',
            'data' => $shippingDocument
        ]);
    }

    /**
     * Create a load plan
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createLoadPlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_carrier_id' => 'required|exists:shipping_carriers,id',
            'vehicle_type' => 'required|string',
            'vehicle_id' => 'nullable|string',
            'shipment_ids' => 'required|json',
            'total_weight_kg' => 'required|numeric',
            'total_volume_cm3' => 'required|numeric',
            'vehicle_capacity_weight_kg' => 'required|numeric',
            'vehicle_capacity_volume_cm3' => 'required|numeric',
            'loading_sequence' => 'nullable|json',
            'planned_departure_date' => 'required|date',
            'planned_departure_time' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Generate a unique load plan number
        $loadPlanNumber = 'LP-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        
        // Calculate utilization percentages
        $utilizationWeight = ($request->total_weight_kg / $request->vehicle_capacity_weight_kg) * 100;
        $utilizationVolume = ($request->total_volume_cm3 / $request->vehicle_capacity_volume_cm3) * 100;
        
        $loadPlan = LoadPlan::create([
            'load_plan_number' => $loadPlanNumber,
            'shipping_carrier_id' => $request->shipping_carrier_id,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_id' => $request->vehicle_id,
            'shipment_ids' => $request->shipment_ids,
            'load_status' => 'planned',
            'total_weight_kg' => $request->total_weight_kg,
            'total_volume_cm3' => $request->total_volume_cm3,
            'vehicle_capacity_weight_kg' => $request->vehicle_capacity_weight_kg,
            'vehicle_capacity_volume_cm3' => $request->vehicle_capacity_volume_cm3,
            'utilization_weight_pct' => round($utilizationWeight, 2),
            'utilization_volume_pct' => round($utilizationVolume, 2),
            'loading_sequence' => $request->loading_sequence,
            'planned_departure_date' => $request->planned_departure_date,
            'planned_departure_time' => $request->planned_departure_time,
            'loading_notes' => $request->loading_notes ?? null,
            'created_by' => auth()->id()
        ]);
        
        // Update shipment statuses
        $shipmentIds = json_decode($request->shipment_ids, true);
        Shipment::whereIn('id', $shipmentIds)->update(['shipment_status' => 'ready']);
        
        return response()->json([
            'success' => true,
            'message' => 'Load plan created successfully',
            'data' => $loadPlan
        ]);
    }

    /**
     * Get loading docks
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getLoadingDocks(Request $request)
    {
        $query = LoadingDock::with('warehouse');
        
        // Filter by warehouse if provided
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        // Filter by status if provided
        if ($request->has('dock_status')) {
            $query->where('dock_status', $request->dock_status);
        }
        
        $loadingDocks = $query->orderBy('dock_name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $loadingDocks
        ]);
    }

    /**
     * Schedule dock appointment
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function scheduleDockAppointment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loading_dock_id' => 'required|exists:loading_docks,id',
            'load_plan_id' => 'nullable|exists:load_plans,id',
            'shipping_carrier_id' => 'required|exists:shipping_carriers,id',
            'scheduled_date' => 'required|date',
            'scheduled_start_time' => 'required',
            'scheduled_end_time' => 'required',
            'driver_name' => 'nullable|string',
            'vehicle_license' => 'nullable|string',
            'trailer_number' => 'nullable|string',
            'special_instructions' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if dock is available for the requested time
        $conflictingAppointments = DockSchedule::where('loading_dock_id', $request->loading_dock_id)
            ->where('scheduled_date', $request->scheduled_date)
            ->where(function($query) use ($request) {
                $query->whereBetween('scheduled_start_time', [$request->scheduled_start_time, $request->scheduled_end_time])
                    ->orWhereBetween('scheduled_end_time', [$request->scheduled_start_time, $request->scheduled_end_time]);
            })
            ->where('appointment_status', '!=', 'cancelled')
            ->count();
        
        if ($conflictingAppointments > 0) {
            return response()->json([
                'success' => false,
                'message' => 'The selected dock is not available for the requested time'
            ], 422);
        }
        
        $dockSchedule = DockSchedule::create([
            'loading_dock_id' => $request->loading_dock_id,
            'load_plan_id' => $request->load_plan_id,
            'shipping_carrier_id' => $request->shipping_carrier_id,
            'scheduled_date' => $request->scheduled_date,
            'scheduled_start_time' => $request->scheduled_start_time,
            'scheduled_end_time' => $request->scheduled_end_time,
            'appointment_status' => 'scheduled',
            'driver_name' => $request->driver_name,
            'vehicle_license' => $request->vehicle_license,
            'trailer_number' => $request->trailer_number,
            'special_instructions' => $request->special_instructions,
            'scheduled_by' => auth()->id()
        ]);
        
        // If load plan is provided, update its status
        if ($request->load_plan_id) {
            $loadPlan = LoadPlan::find($request->load_plan_id);
            $loadPlan->load_status = 'planned';
            $loadPlan->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Dock appointment scheduled successfully',
            'data' => $dockSchedule
        ]);
    }

    /**
     * Confirm loading
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function confirmLoading(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'load_plan_id' => 'required|exists:load_plans,id',
            'dock_schedule_id' => 'required|exists:dock_schedules,id',
            'loaded_shipments' => 'required|json',
            'actual_weight_kg' => 'nullable|numeric',
            'total_pieces' => 'required|integer',
            'loading_method' => 'required|in:manual,forklift,conveyor,automated',
            'loading_supervisor_id' => 'required|exists:employees,id',
            'driver_signature' => 'nullable|string',
            'loading_photos' => 'nullable|json',
            'loading_notes' => 'nullable|string',
            'loading_started_at' => 'required|date',
            'loading_completed_at' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $loadingConfirmation = LoadingConfirmation::create([
            'load_plan_id' => $request->load_plan_id,
            'dock_schedule_id' => $request->dock_schedule_id,
            'loaded_shipments' => $request->loaded_shipments,
            'actual_weight_kg' => $request->actual_weight_kg,
            'total_pieces' => $request->total_pieces,
            'loading_method' => $request->loading_method,
            'loading_supervisor_id' => $request->loading_supervisor_id,
            'driver_signature' => $request->driver_signature,
            'loading_photos' => $request->loading_photos,
            'loading_notes' => $request->loading_notes,
            'loading_started_at' => $request->loading_started_at,
            'loading_completed_at' => $request->loading_completed_at
        ]);
        
        // Update load plan status
        $loadPlan = LoadPlan::find($request->load_plan_id);
        $loadPlan->load_status = 'loaded';
        $loadPlan->actual_departure_time = now();
        $loadPlan->save();
        
        // Update dock schedule status
        $dockSchedule = DockSchedule::find($request->dock_schedule_id);
        $dockSchedule->appointment_status = 'completed';
        $dockSchedule->actual_start_time = $request->loading_started_at;
        $dockSchedule->actual_end_time = $request->loading_completed_at;
        $dockSchedule->save();
        
        // Update shipment statuses
        $shipmentIds = json_decode($request->loaded_shipments, true);
        Shipment::whereIn('id', $shipmentIds)->update([
            'shipment_status' => 'picked_up'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Loading confirmed successfully',
            'data' => $loadingConfirmation
        ]);
    }

    /**
     * Record delivery confirmation
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function recordDeliveryConfirmation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
            'tracking_number' => 'required|string',
            'delivery_status' => 'required|in:delivered,attempted,exception,returned',
            'delivery_timestamp' => 'required|date',
            'delivered_to' => 'nullable|string',
            'delivery_location' => 'nullable|string',
            'signature_data' => 'nullable|string',
            'delivery_photos' => 'nullable|json',
            'delivery_notes' => 'nullable|string',
            'exception_details' => 'nullable|json',
            'carrier_reference' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $deliveryConfirmation = DeliveryConfirmation::create([
            'shipment_id' => $request->shipment_id,
            'tracking_number' => $request->tracking_number,
            'delivery_status' => $request->delivery_status,
            'delivery_timestamp' => $request->delivery_timestamp,
            'delivered_to' => $request->delivered_to,
            'delivery_location' => $request->delivery_location,
            'signature_data' => $request->signature_data,
            'delivery_photos' => $request->delivery_photos,
            'delivery_notes' => $request->delivery_notes,
            'exception_details' => $request->exception_details,
            'carrier_reference' => $request->carrier_reference,
            'updated_at_carrier' => now()
        ]);
        
        // Update shipment status
        $shipment = Shipment::find($request->shipment_id);
        
        if ($request->delivery_status === 'delivered') {
            $shipment->shipment_status = 'delivered';
            $shipment->actual_delivery_date = date('Y-m-d', strtotime($request->delivery_timestamp));
        } elseif ($request->delivery_status === 'exception') {
            $shipment->shipment_status = 'exception';
        }
        
        $shipment->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Delivery confirmation recorded',
            'data' => $deliveryConfirmation
        ]);
    }

    /**
     * Create shipping manifest
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createShippingManifest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_carrier_id' => 'required|exists:shipping_carriers,id',
            'manifest_date' => 'required|date',
            'shipment_ids' => 'required|json',
            'total_shipments' => 'required|integer',
            'total_pieces' => 'required|integer',
            'total_weight_kg' => 'required|numeric',
            'total_declared_value' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Generate a unique manifest number
        $manifestNumber = 'MF-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        
        // In a real implementation, this would generate actual manifest data
        // For this example, we'll simulate manifest generation
        $manifestData = base64_encode('DUMMY MANIFEST DATA');
        
        $shippingManifest = ShippingManifest::create([
            'manifest_number' => $manifestNumber,
            'shipping_carrier_id' => $request->shipping_carrier_id,
            'manifest_date' => $request->manifest_date,
            'shipment_ids' => $request->shipment_ids,
            'total_shipments' => $request->total_shipments,
            'total_pieces' => $request->total_pieces,
            'total_weight_kg' => $request->total_weight_kg,
            'total_declared_value' => $request->total_declared_value,
            'manifest_status' => 'open',
            'manifest_data' => $manifestData,
            'created_by' => auth()->id()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Shipping manifest created',
            'data' => $shippingManifest
        ]);
    }

    /**
     * Close shipping manifest
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function closeShippingManifest(Request $request, $id)
    {
        $shippingManifest = ShippingManifest::findOrFail($id);
        
        if ($shippingManifest->manifest_status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Manifest cannot be closed in its current status'
            ], 422);
        }
        
        $shippingManifest->manifest_status = 'closed';
        $shippingManifest->closed_at = now();
        $shippingManifest->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Shipping manifest closed',
            'data' => $shippingManifest
        ]);
    }

    /**
     * Transmit shipping manifest to carrier
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function transmitShippingManifest(Request $request, $id)
    {
        $shippingManifest = ShippingManifest::findOrFail($id);
        
        if ($shippingManifest->manifest_status !== 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Manifest must be closed before transmission'
            ], 422);
        }
        
        // In a real implementation, this would transmit data to carrier API
        // For this example, we'll simulate transmission
        
        $shippingManifest->manifest_status = 'transmitted';
        $shippingManifest->transmitted_at = now();
        $shippingManifest->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Shipping manifest transmitted to carrier',
            'data' => $shippingManifest
        ]);
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(Request $request)
    {
        try {
            $customerId = $request->input('customer_id');
            $startDate = $request->input('start_date', now()->subDays(30));
            $endDate = $request->input('end_date', now());

            // Get or generate customer analytics
            $analytics = CustomerAnalytics::where('customer_id', $customerId)
                ->where('analysis_period_start', '>=', $startDate)
                ->where('analysis_period_end', '<=', $endDate)
                ->latest()
                ->first();

            if (!$analytics) {
                // Generate new analytics
                $analytics = $this->generateCustomerAnalytics($customerId, $startDate, $endDate);
            }

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get customer analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get carrier performance analytics
     */
    public function getCarrierPerformance(Request $request)
    {
        try {
            $carrierId = $request->input('carrier_id');
            $startDate = $request->input('start_date', now()->subDays(30));
            $endDate = $request->input('end_date', now());

            // Get or generate carrier performance
            $performance = CarrierPerformance::where('shipping_carrier_id', $carrierId)
                ->where('analysis_period_start', '>=', $startDate)
                ->where('analysis_period_end', '<=', $endDate)
                ->latest()
                ->first();

            if (!$performance) {
                // Generate new performance data
                $performance = $this->generateCarrierPerformance($carrierId, $startDate, $endDate);
            }

            return response()->json([
                'success' => true,
                'data' => $performance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get carrier performance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get predictive forecasts
     */
    public function getPredictiveForecast(Request $request)
    {
        try {
            $forecastType = $request->input('forecast_type', 'demand');
            $warehouseId = $request->input('warehouse_id');
            $forecastDays = $request->input('forecast_days', 30);

            // Get or generate forecast
            $forecast = PredictiveForecast::where('forecast_type', $forecastType)
                ->where('warehouse_id', $warehouseId)
                ->where('forecast_period_end', '>=', now())
                ->latest()
                ->first();

            if (!$forecast || !$forecast->isValid()) {
                // Generate new forecast
                $forecast = $this->generatePredictiveForecast($forecastType, $warehouseId, $forecastDays);
            }

            return response()->json([
                'success' => true,
                'data' => $forecast
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get predictive forecast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate customer analytics
     */
    private function generateCustomerAnalytics($customerId, $startDate, $endDate)
    {
        // Mock implementation - replace with actual analytics calculation
        return CustomerAnalytics::create([
            'customer_id' => $customerId,
            'analysis_period_start' => $startDate,
            'analysis_period_end' => $endDate,
            'total_orders' => 25,
            'total_shipments' => 28,
            'total_revenue' => 15000.00,
            'average_order_value' => 600.00,
            'total_weight_shipped_kg' => 1250.5,
            'total_volume_shipped_cm3' => 850000,
            'on_time_delivery_rate' => 94.5,
            'average_processing_time_hours' => 18.5,
            'return_rate_percentage' => 2.1,
            'preferred_carriers' => json_encode(['FedEx' => 15, 'UPS' => 10, 'DHL' => 3]),
            'shipping_cost_percentage' => 8.5,
            'order_frequency_days' => 12.5,
            'seasonal_patterns' => json_encode(['Q1' => 20, 'Q2' => 25, 'Q3' => 30, 'Q4' => 35]),
            'geographic_distribution' => json_encode(['North' => 40, 'South' => 30, 'East' => 20, 'West' => 10]),
            'service_level_preferences' => json_encode(['Standard' => 60, 'Express' => 30, 'Overnight' => 10]),
            'generated_by' => auth()->id(),
            'generated_at' => now()
        ]);
    }

    /**
     * Generate carrier performance data
     */
    private function generateCarrierPerformance($carrierId, $startDate, $endDate)
    {
        // Mock implementation - replace with actual performance calculation
        return CarrierPerformance::create([
            'shipping_carrier_id' => $carrierId,
            'analysis_period_start' => $startDate,
            'analysis_period_end' => $endDate,
            'total_shipments' => 150,
            'total_weight_kg' => 5500.0,
            'total_cost' => 12500.00,
            'on_time_deliveries' => 142,
            'late_deliveries' => 8,
            'damaged_shipments' => 2,
            'lost_shipments' => 0,
            'on_time_percentage' => 94.67,
            'damage_rate_percentage' => 1.33,
            'loss_rate_percentage' => 0.00,
            'average_transit_days' => 2.5,
            'average_cost_per_kg' => 2.27,
            'service_level_performance' => json_encode([
                'standard' => ['on_time_percentage' => 96.0, 'cost_per_kg' => 2.10],
                'express' => ['on_time_percentage' => 98.5, 'cost_per_kg' => 3.50],
                'overnight' => ['on_time_percentage' => 99.2, 'cost_per_kg' => 8.75]
            ]),
            'geographic_coverage' => json_encode(['domestic' => 100, 'international' => 85]),
            'pickup_reliability_percentage' => 97.5,
            'customer_satisfaction_score' => 4.2,
            'claims_total_amount' => 850.00,
            'claims_resolution_time_days' => 5.5,
            'generated_by' => auth()->id(),
            'generated_at' => now()
        ]);
    }

    /**
     * Generate predictive forecast
     */
    private function generatePredictiveForecast($forecastType, $warehouseId, $forecastDays)
    {
        // Mock implementation - replace with actual ML forecasting
        $forecastData = [];
        $baseValue = 100;
        
        for ($i = 0; $i < $forecastDays; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $value = $baseValue + (sin($i * 0.1) * 20) + (rand(-10, 10));
            
            $forecastData[$date] = [
                'value' => round($value, 2),
                'lower_bound' => round($value * 0.9, 2),
                'upper_bound' => round($value * 1.1, 2),
                'confidence_interval' => 90
            ];
        }

        return PredictiveForecast::create([
            'warehouse_id' => $warehouseId,
            'forecast_type' => $forecastType,
            'forecast_period_start' => now(),
            'forecast_period_end' => now()->addDays($forecastDays),
            'historical_data_period_days' => 90,
            'forecast_data' => json_encode($forecastData),
            'confidence_level' => 85.5,
            'model_accuracy_percentage' => 87.2,
            'key_factors' => json_encode(['seasonality', 'trend', 'promotions', 'weather']),
            'seasonal_adjustments' => json_encode(['winter' => 1.2, 'spring' => 1.0, 'summer' => 0.8, 'fall' => 1.1]),
            'trend_analysis' => json_encode(['direction' => 'increasing', 'strength' => 0.65]),
            'anomaly_detection' => json_encode([]),
            'recommendations' => json_encode([
                'Increase capacity by 15% for peak periods',
                'Consider additional staffing for high-demand days',
                'Optimize inventory levels based on forecast'
            ]),
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'last_updated_at' => now()
        ]);
    }
}