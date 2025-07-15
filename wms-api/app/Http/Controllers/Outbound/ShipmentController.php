<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\Shipment;
use App\Models\Outbound\ShipmentItem;
use App\Models\Outbound\ShipmentTracking;
use App\Models\SalesOrder;
use App\Models\ShippingCarrier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class ShipmentController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of shipments
     */
    public function index(Request $request): JsonResponse
    {
        $query = Shipment::with(['carrier', 'warehouse', 'orders.customer']);

        // Apply filters
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('carrier_id')) {
            $query->where('carrier_id', $request->carrier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('shipment_type')) {
            $query->where('shipment_type', $request->shipment_type);
        }

        if ($request->has('tracking_number')) {
            $query->where('tracking_number', 'like', '%' . $request->tracking_number . '%');
        }

        if ($request->has('date_from')) {
            $query->where('ship_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('ship_date', '<=', $request->date_to);
        }

        $shipments = $query->orderBy('ship_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $shipments,
            'message' => 'Shipments retrieved successfully'
        ]);
    }

    /**
     * Store a newly created shipment
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'carrier_id' => 'required|exists:shipping_carriers,id',
            'shipment_type' => 'required|in:standard,express,overnight,ground,freight',
            'service_level' => 'required|string',
            'ship_date' => 'required|date',
            'delivery_date' => 'nullable|date|after:ship_date',
            'ship_from_address' => 'required|array',
            'ship_to_address' => 'required|array',
            'orders' => 'required|array|min:1',
            'orders.*' => 'exists:sales_orders,id',
            'package_type' => 'required|in:box,envelope,pallet,tube,pak',
            'dimensions' => 'required|array',
            'dimensions.length' => 'required|numeric|min:0',
            'dimensions.width' => 'required|numeric|min:0',
            'dimensions.height' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'declared_value' => 'nullable|numeric|min:0',
            'insurance_required' => 'boolean',
            'signature_required' => 'boolean',
            'special_instructions' => 'nullable|string',
            'reference_numbers' => 'nullable|array'
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

            // Generate shipment number
            $shipmentNumber = $this->generateShipmentNumber();

            // Validate orders can be shipped
            $orders = SalesOrder::whereIn('id', $request->orders)
                ->where('warehouse_id', $request->warehouse_id)
                ->whereIn('status', ['picked', 'packed'])
                ->get();

            if ($orders->count() !== count($request->orders)) {
                throw new \Exception('Some orders are not ready for shipment');
            }

            // Calculate shipping cost
            $shippingCost = $this->calculateShippingCost($request->all());

            // Create shipment
            $shipment = Shipment::create([
                'shipment_number' => $shipmentNumber,
                'warehouse_id' => $request->warehouse_id,
                'carrier_id' => $request->carrier_id,
                'shipment_type' => $request->shipment_type,
                'service_level' => $request->service_level,
                'status' => 'created',
                'ship_date' => $request->ship_date,
                'delivery_date' => $request->delivery_date,
                'ship_from_address' => $request->ship_from_address,
                'ship_to_address' => $request->ship_to_address,
                'package_type' => $request->package_type,
                'dimensions' => $request->dimensions,
                'weight' => $request->weight,
                'declared_value' => $request->declared_value,
                'insurance_required' => $request->insurance_required ?? false,
                'signature_required' => $request->signature_required ?? false,
                'special_instructions' => $request->special_instructions,
                'reference_numbers' => $request->reference_numbers ?? [],
                'shipping_cost' => $shippingCost,
                'created_by' => auth()->id()
            ]);

            // Add orders to shipment
            foreach ($orders as $order) {
                $shipment->orders()->attach($order->id, [
                    'order_value' => $order->total_amount,
                    'order_weight' => $order->total_weight ?? 0,
                    'package_count' => 1 // This could be calculated based on order items
                ]);

                // Update order status
                $order->update(['status' => 'shipped']);
            }

            // Create shipment items from order items
            $this->createShipmentItems($shipment, $orders);

            // Generate shipping label if carrier supports it
            if ($this->carrierSupportsLabels($request->carrier_id)) {
                $this->generateShippingLabel($shipment);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.shipment.created', [
                'shipment_id' => $shipment->id,
                'shipment_number' => $shipmentNumber,
                'warehouse_id' => $request->warehouse_id,
                'carrier_id' => $request->carrier_id,
                'order_count' => $orders->count(),
                'total_weight' => $request->weight,
                'shipping_cost' => $shippingCost
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $shipment->load(['carrier', 'warehouse', 'orders.customer']),
                'message' => 'Shipment created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified shipment
     */
    public function show($id): JsonResponse
    {
        $shipment = Shipment::with([
            'carrier',
            'warehouse',
            'orders.customer',
            'items.product',
            'tracking',
            'createdBy'
        ])->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        // Get latest tracking information
        $trackingInfo = $this->getLatestTrackingInfo($shipment);

        return response()->json([
            'success' => true,
            'data' => array_merge($shipment->toArray(), [
                'latest_tracking' => $trackingInfo
            ]),
            'message' => 'Shipment retrieved successfully'
        ]);
    }

    /**
     * Update the specified shipment
     */
    public function update(Request $request, $id): JsonResponse
    {
        $shipment = Shipment::find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        if (!in_array($shipment->status, ['created', 'labeled', 'ready_to_ship'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update shipment in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'ship_date' => 'sometimes|date',
            'delivery_date' => 'nullable|date|after:ship_date',
            'ship_to_address' => 'sometimes|array',
            'special_instructions' => 'nullable|string',
            'declared_value' => 'nullable|numeric|min:0',
            'insurance_required' => 'sometimes|boolean',
            'signature_required' => 'sometimes|boolean'
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

            $shipment->update($request->only([
                'ship_date',
                'delivery_date',
                'ship_to_address',
                'special_instructions',
                'declared_value',
                'insurance_required',
                'signature_required'
            ]));

            // Recalculate shipping cost if relevant fields changed
            if ($request->hasAny(['ship_date', 'ship_to_address', 'declared_value'])) {
                $newCost = $this->calculateShippingCost(array_merge(
                    $shipment->toArray(),
                    $request->all()
                ));
                $shipment->update(['shipping_cost' => $newCost]);
            }

            // Regenerate label if address changed
            if ($request->has('ship_to_address') && $shipment->tracking_number) {
                $this->regenerateShippingLabel($shipment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $shipment->load(['carrier', 'warehouse', 'orders.customer']),
                'message' => 'Shipment updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update shipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ship the shipment (mark as shipped)
     */
    public function ship(Request $request, $id): JsonResponse
    {
        $shipment = Shipment::find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        if ($shipment->status !== 'ready_to_ship') {
            return response()->json([
                'success' => false,
                'message' => 'Shipment is not ready to ship'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'actual_ship_date' => 'nullable|date',
            'shipped_by' => 'nullable|exists:users,id',
            'shipping_notes' => 'nullable|string'
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

            $shipment->update([
                'status' => 'shipped',
                'actual_ship_date' => $request->actual_ship_date ?? now(),
                'shipped_by' => $request->shipped_by ?? auth()->id(),
                'shipping_notes' => $request->shipping_notes
            ]);

            // Create initial tracking record
            $this->createInitialTracking($shipment);

            // Update order statuses
            foreach ($shipment->orders as $order) {
                $order->update([
                    'status' => 'shipped',
                    'shipped_date' => $shipment->actual_ship_date
                ]);
            }

            // Send tracking notifications
            $this->sendTrackingNotifications($shipment);

            // Fire event
            $this->fireTransactionalEvent('outbound.shipment.shipped', [
                'shipment_id' => $shipment->id,
                'shipment_number' => $shipment->shipment_number,
                'tracking_number' => $shipment->tracking_number,
                'actual_ship_date' => $shipment->actual_ship_date,
                'shipped_by' => $shipment->shipped_by
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $shipment->load(['carrier', 'warehouse', 'orders.customer']),
                'message' => 'Shipment marked as shipped successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to ship shipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track shipment
     */
    public function track($id): JsonResponse
    {
        $shipment = Shipment::with(['carrier', 'tracking'])->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        if (!$shipment->tracking_number) {
            return response()->json([
                'success' => false,
                'message' => 'No tracking number available'
            ], 400);
        }

        try {
            // Get latest tracking information from carrier
            $trackingData = $this->getCarrierTrackingData($shipment);

            // Update local tracking records
            $this->updateTrackingRecords($shipment, $trackingData);

            return response()->json([
                'success' => true,
                'data' => [
                    'shipment' => $shipment,
                    'tracking_events' => $trackingData['events'] ?? [],
                    'current_status' => $trackingData['current_status'] ?? 'unknown',
                    'estimated_delivery' => $trackingData['estimated_delivery'] ?? null,
                    'last_updated' => now()
                ],
                'message' => 'Tracking information retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel shipment
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $shipment = Shipment::find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        if (!in_array($shipment->status, ['created', 'labeled', 'ready_to_ship'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel shipment in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string',
            'refund_shipping' => 'boolean'
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

            // Cancel with carrier if tracking number exists
            if ($shipment->tracking_number) {
                $this->cancelWithCarrier($shipment);
            }

            $shipment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $request->cancellation_reason,
                'refund_shipping' => $request->refund_shipping ?? false
            ]);

            // Revert order statuses
            foreach ($shipment->orders as $order) {
                $order->update(['status' => 'packed']); // Back to packed status
            }

            // Process refund if requested
            if ($request->refund_shipping) {
                $this->processShippingRefund($shipment);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.shipment.cancelled', [
                'shipment_id' => $shipment->id,
                'shipment_number' => $shipment->shipment_number,
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_by' => auth()->id(),
                'refund_amount' => $request->refund_shipping ? $shipment->shipping_cost : 0
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $shipment->load(['carrier', 'warehouse', 'orders.customer']),
                'message' => 'Shipment cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel shipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shipment analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = Shipment::whereBetween('ship_date', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $analytics = [
            'total_shipments' => $query->count(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_carrier' => $this->getShipmentsByCarrier($query),
            'by_service_level' => $query->groupBy('service_level')->selectRaw('service_level, count(*) as count')->pluck('count', 'service_level'),
            'total_shipping_cost' => $query->sum('shipping_cost'),
            'average_shipping_cost' => $query->avg('shipping_cost'),
            'total_weight' => $query->sum('weight'),
            'on_time_delivery_rate' => $this->calculateOnTimeDeliveryRate($query),
            'carrier_performance' => $this->getCarrierPerformance($query),
            'shipping_trends' => $this->getShippingTrends($dateFrom, $dateTo, $warehouseId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Shipment analytics retrieved successfully'
        ]);
    }

    /**
     * Generate shipment number
     */
    private function generateShipmentNumber(): string
    {
        $year = date('Y');
        $sequence = Shipment::whereYear('created_at', $year)->count() + 1;
        
        return 'SH-' . $year . '-' . str_pad($sequence, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate shipping cost
     */
    private function calculateShippingCost(array $shipmentData): float
    {
        // This would integrate with carrier APIs for real-time rates
        $baseRate = 10.00;
        $weightRate = $shipmentData['weight'] * 0.50;
        $serviceMultiplier = $this->getServiceMultiplier($shipmentData['service_level']);
        
        return ($baseRate + $weightRate) * $serviceMultiplier;
    }

    private function getServiceMultiplier(string $serviceLevel): float
    {
        $multipliers = [
            'ground' => 1.0,
            'standard' => 1.2,
            'express' => 1.8,
            'overnight' => 2.5
        ];

        return $multipliers[$serviceLevel] ?? 1.0;
    }

    /**
     * Create shipment items from orders
     */
    private function createShipmentItems($shipment, $orders): void
    {
        foreach ($orders as $order) {
            foreach ($order->items as $orderItem) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'sales_order_id' => $order->id,
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'total_value' => $orderItem->total_amount,
                    'weight' => $orderItem->weight ?? 0,
                    'serial_numbers' => $orderItem->serial_numbers ?? [],
                    'lot_numbers' => $orderItem->lot_numbers ?? []
                ]);
            }
        }
    }

    /**
     * Carrier integration methods
     */
    private function carrierSupportsLabels($carrierId): bool
    {
        $carrier = ShippingCarrier::find($carrierId);
        return $carrier && $carrier->supports_labels;
    }

    private function generateShippingLabel($shipment): void
    {
        // Integration with carrier API to generate shipping label
        $trackingNumber = $this->generateTrackingNumber($shipment);
        $labelUrl = $this->getCarrierLabelUrl($shipment, $trackingNumber);
        
        $shipment->update([
            'tracking_number' => $trackingNumber,
            'label_url' => $labelUrl,
            'status' => 'labeled'
        ]);
    }

    private function generateTrackingNumber($shipment): string
    {
        // This would call carrier API to get real tracking number
        return 'TRK' . time() . rand(1000, 9999);
    }

    private function getCarrierLabelUrl($shipment, $trackingNumber): string
    {
        // This would return the URL to the shipping label from carrier
        return "https://labels.carrier.com/{$trackingNumber}.pdf";
    }

    private function regenerateShippingLabel($shipment): void
    {
        if ($shipment->tracking_number) {
            $this->generateShippingLabel($shipment);
        }
    }

    private function createInitialTracking($shipment): void
    {
        ShipmentTracking::create([
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'status' => 'shipped',
            'location' => $shipment->ship_from_address['city'] ?? 'Warehouse',
            'event_date' => $shipment->actual_ship_date,
            'description' => 'Package shipped from warehouse',
            'carrier_status' => 'IN_TRANSIT'
        ]);
    }

    private function getLatestTrackingInfo($shipment): ?array
    {
        $latestTracking = $shipment->tracking()
            ->orderBy('event_date', 'desc')
            ->first();

        return $latestTracking ? $latestTracking->toArray() : null;
    }

    private function getCarrierTrackingData($shipment): array
    {
        // This would integrate with carrier tracking APIs
        return [
            'current_status' => 'in_transit',
            'estimated_delivery' => now()->addDays(2),
            'events' => [
                [
                    'date' => now()->subHours(2),
                    'status' => 'in_transit',
                    'location' => 'Distribution Center',
                    'description' => 'Package is in transit'
                ]
            ]
        ];
    }

    private function updateTrackingRecords($shipment, $trackingData): void
    {
        foreach ($trackingData['events'] ?? [] as $event) {
            ShipmentTracking::updateOrCreate([
                'shipment_id' => $shipment->id,
                'event_date' => $event['date'],
                'status' => $event['status']
            ], [
                'tracking_number' => $shipment->tracking_number,
                'location' => $event['location'],
                'description' => $event['description'],
                'carrier_status' => $event['status']
            ]);
        }
    }

    private function cancelWithCarrier($shipment): void
    {
        // Integration with carrier API to cancel shipment
        // This would call the carrier's cancellation endpoint
    }

    private function processShippingRefund($shipment): void
    {
        // Process refund for shipping costs
        // This would integrate with payment processing system
    }

    private function sendTrackingNotifications($shipment): void
    {
        // Send tracking notifications to customers
        // This would integrate with notification system
    }

    /**
     * Analytics helper methods
     */
    private function getShipmentsByCarrier($query): array
    {
        return $query->with('carrier')
            ->selectRaw('carrier_id, count(*) as count, sum(shipping_cost) as total_cost')
            ->groupBy('carrier_id')
            ->get()
            ->map(function ($item) {
                return [
                    'carrier' => $item->carrier,
                    'shipment_count' => $item->count,
                    'total_cost' => $item->total_cost
                ];
            })
            ->toArray();
    }

    private function calculateOnTimeDeliveryRate($query): float
    {
        $delivered = $query->where('status', 'delivered');
        $total = $delivered->count();
        $onTime = $delivered->whereRaw('actual_delivery_date <= delivery_date')->count();
        
        return $total > 0 ? ($onTime / $total) * 100 : 0;
    }

    private function getCarrierPerformance($query): array
    {
        return $query->with('carrier')
            ->where('status', 'delivered')
            ->selectRaw('carrier_id, AVG(DATEDIFF(actual_delivery_date, ship_date)) as avg_transit_time, COUNT(*) as shipment_count')
            ->groupBy('carrier_id')
            ->get()
            ->map(function ($item) {
                return [
                    'carrier' => $item->carrier,
                    'avg_transit_time' => $item->avg_transit_time,
                    'shipment_count' => $item->shipment_count
                ];
            })
            ->toArray();
    }

    private function getShippingTrends($dateFrom, $dateTo, $warehouseId): array
    {
        $query = Shipment::whereBetween('ship_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE(ship_date) as date, COUNT(*) as shipment_count, SUM(shipping_cost) as total_cost, SUM(weight) as total_weight')
            ->groupBy('date')
            ->orderBy('date');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->toArray();
    }
}