<?php

namespace App\Services\Outbound;

use App\Models\Outbound\Shipment;
use App\Models\Outbound\ShippingRate;
use App\Models\Outbound\RateShoppingResult;
use App\Models\Outbound\ShippingLabel;
use App\Models\Outbound\ShippingDocument;
use App\Models\Outbound\ShippingManifest;
use App\Models\Outbound\DeliveryConfirmation;
use App\Models\Outbound\PackedCarton;
use App\Models\Outbound\MultiCartonShipment;
use App\Models\ShippingCarrier;
use App\Models\SalesOrder;
use App\Models\BusinessParty;
use App\Models\Address;
use Exception;
use DB;

class ShippingService
{
    /**
     * Create a new shipment
     *
     * @param array $data
     * @return \App\Models\Outbound\Shipment
     */
    public function createShipment(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Generate shipment number if not provided
            if (!isset($data['shipment_number'])) {
                $data['shipment_number'] = $this->generateShipmentNumber();
            }
            
            // Create the shipment
            $shipment = Shipment::create($data);
            
            // If this is a multi-carton shipment, update the related multi-carton shipment record
            if (isset($data['multi_carton_shipment_id'])) {
                $multiCartonShipment = MultiCartonShipment::findOrFail($data['multi_carton_shipment_id']);
                $multiCartonShipment->update([
                    'shipment_status' => 'assigned',
                    'master_tracking_number' => $data['tracking_number'] ?? null
                ]);
                
                // Update the packed cartons
                $cartonIds = json_decode($multiCartonShipment->carton_ids, true);
                PackedCarton::whereIn('id', $cartonIds)->update([
                    'carton_status' => 'assigned_to_shipment'
                ]);
            }
            
            DB::commit();
            return $shipment;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Generate a unique shipment number
     *
     * @return string
     */
    private function generateShipmentNumber()
    {
        $prefix = 'SHP';
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        
        return $prefix . $timestamp . $random;
    }
    
    /**
     * Perform rate shopping across carriers
     *
     * @param array $data
     * @return array
     */
    public function performRateShopping(array $data)
    {
        // Get all active carriers
        $carriers = ShippingCarrier::where('is_active', true)->get();
        
        $originZip = $data['origin_zip'];
        $destinationZip = $data['destination_zip'];
        $weight = $data['total_weight_kg'];
        $declaredValue = $data['declared_value'] ?? 0;
        
        $rateQuotes = [];
        
        foreach ($carriers as $carrier) {
            $carrierRates = $this->getCarrierRates($carrier->id, $originZip, $destinationZip, $weight);
            
            if (!empty($carrierRates)) {
                $rateQuotes[] = [
                    'carrier_id' => $carrier->id,
                    'carrier_name' => $carrier->carrier_name,
                    'services' => $carrierRates
                ];
            }
        }
        
        // Save the rate shopping result
        $rateShoppingResult = RateShoppingResult::create([
            'sales_order_id' => $data['sales_order_id'] ?? null,
            'origin_zip' => $originZip,
            'destination_zip' => $destinationZip,
            'total_weight_kg' => $weight,
            'total_volume_cm3' => $data['total_volume_cm3'] ?? null,
            'rate_quotes' => json_encode($rateQuotes),
            'quoted_at' => now(),
            'expires_at' => now()->addHours(24),
            'requested_by' => auth()->id()
        ]);
        
        // Find the best rate based on cost
        $bestRate = $this->findBestRate($rateQuotes, 'cost');
        
        if ($bestRate) {
            $rateShoppingResult->update([
                'selected_carrier_id' => $bestRate['carrier_id'],
                'selected_service_code' => $bestRate['service_code'],
                'selected_rate' => $bestRate['cost'],
                'selection_criteria' => json_encode(['criteria' => 'cost'])
            ]);
        }
        
        return [
            'rate_quotes' => $rateQuotes,
            'best_rate' => $bestRate,
            'rate_shopping_result_id' => $rateShoppingResult->id
        ];
    }
    
    /**
     * Get rates for a specific carrier
     *
     * @param int $carrierId
     * @param string $originZip
     * @param string $destinationZip
     * @param float $weight
     * @return array
     */
    private function getCarrierRates($carrierId, $originZip, $destinationZip, $weight)
    {
        $rates = ShippingRate::where('shipping_carrier_id', $carrierId)
            ->where('is_active', true)
            ->where('origin_zip', $originZip)
            ->where('destination_zip', $destinationZip)
            ->where('weight_from_kg', '<=', $weight)
            ->where('weight_to_kg', '>=', $weight)
            ->get();
        
        $carrierRates = [];
        
        foreach ($rates as $rate) {
            $totalRate = $rate->calculateTotalRate();
            
            $carrierRates[$rate->service_code] = [
                'service_code' => $rate->service_code,
                'service_name' => $rate->service_name,
                'cost' => $totalRate,
                'transit_days' => $rate->transit_days,
                'fuel_surcharge_rate' => $rate->fuel_surcharge_rate,
                'additional_charges' => json_decode($rate->additional_charges, true)
            ];
        }
        
        return $carrierRates;
    }
    
    /**
     * Find the best rate based on criteria
     *
     * @param array $rateQuotes
     * @param string $criteria
     * @return array|null
     */
    private function findBestRate($rateQuotes, $criteria = 'cost')
    {
        $bestRate = null;
        $bestValue = PHP_FLOAT_MAX;
        
        foreach ($rateQuotes as $quote) {
            foreach ($quote['services'] as $serviceCode => $service) {
                if ($criteria === 'cost' && $service['cost'] < $bestValue) {
                    $bestValue = $service['cost'];
                    $bestRate = [
                        'carrier_id' => $quote['carrier_id'],
                        'carrier_name' => $quote['carrier_name'],
                        'service_code' => $serviceCode,
                        'service_name' => $service['service_name'],
                        'cost' => $service['cost'],
                        'transit_days' => $service['transit_days']
                    ];
                } else if ($criteria === 'transit_days' && $service['transit_days'] < $bestValue) {
                    $bestValue = $service['transit_days'];
                    $bestRate = [
                        'carrier_id' => $quote['carrier_id'],
                        'carrier_name' => $quote['carrier_name'],
                        'service_code' => $serviceCode,
                        'service_name' => $service['service_name'],
                        'cost' => $service['cost'],
                        'transit_days' => $service['transit_days']
                    ];
                }
            }
        }
        
        return $bestRate;
    }
    
    /**
     * Generate a shipping label
     *
     * @param array $data
     * @return \App\Models\Outbound\ShippingLabel
     */
    public function generateShippingLabel(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Create the shipping label
            $shippingLabel = ShippingLabel::create($data);
            
            // Update the packed carton status if applicable
            if (isset($data['packed_carton_id'])) {
                $packedCarton = PackedCarton::findOrFail($data['packed_carton_id']);
                $packedCarton->update([
                    'carton_status' => 'labeled'
                ]);
            }
            
            DB::commit();
            return $shippingLabel;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Generate a shipping document
     *
     * @param array $data
     * @return \App\Models\Outbound\ShippingDocument
     */
    public function generateShippingDocument(array $data)
    {
        // Create the shipping document
        $shippingDocument = ShippingDocument::create($data);
        
        return $shippingDocument;
    }
    
    /**
     * Create a shipping manifest
     *
     * @param array $data
     * @return \App\Models\Outbound\ShippingManifest
     */
    public function createShippingManifest(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Generate manifest number if not provided
            if (!isset($data['manifest_number'])) {
                $data['manifest_number'] = $this->generateManifestNumber();
            }
            
            // Calculate totals if not provided
            if (!isset($data['total_shipments']) && isset($data['shipment_ids'])) {
                $shipmentIds = json_decode($data['shipment_ids'], true);
                $data['total_shipments'] = count($shipmentIds);
                
                $shipments = Shipment::whereIn('id', $shipmentIds)->get();
                $data['total_pieces'] = $shipments->sum('total_cartons');
                $data['total_weight_kg'] = $shipments->sum('total_weight_kg');
            }
            
            // Create the manifest
            $shippingManifest = ShippingManifest::create($data);
            
            // Update the shipment statuses
            if (isset($data['shipment_ids'])) {
                $shipmentIds = json_decode($data['shipment_ids'], true);
                Shipment::whereIn('id', $shipmentIds)->update([
                    'shipment_status' => 'manifested'
                ]);
            }
            
            DB::commit();
            return $shippingManifest;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Generate a unique manifest number
     *
     * @return string
     */
    private function generateManifestNumber()
    {
        $prefix = 'MAN';
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        
        return $prefix . $timestamp . $random;
    }
    
    /**
     * Close a shipping manifest
     *
     * @param int $manifestId
     * @return \App\Models\Outbound\ShippingManifest
     */
    public function closeShippingManifest($manifestId)
    {
        $manifest = ShippingManifest::findOrFail($manifestId);
        
        if ($manifest->manifest_status !== 'open') {
            throw new Exception('Manifest is not in open status');
        }
        
        $manifest->update([
            'manifest_status' => 'closed',
            'closed_at' => now()
        ]);
        
        return $manifest;
    }
    
    /**
     * Transmit a shipping manifest to carrier
     *
     * @param int $manifestId
     * @return \App\Models\Outbound\ShippingManifest
     */
    public function transmitShippingManifest($manifestId)
    {
        $manifest = ShippingManifest::findOrFail($manifestId);
        
        if ($manifest->manifest_status !== 'closed') {
            throw new Exception('Manifest must be closed before transmission');
        }
        
        // Here you would implement the actual carrier API integration
        // For now, we'll just update the status
        
        $manifest->update([
            'manifest_status' => 'transmitted',
            'transmitted_at' => now()
        ]);
        
        return $manifest;
    }
    
    /**
     * Record a delivery confirmation
     *
     * @param array $data
     * @return \App\Models\Outbound\DeliveryConfirmation
     */
    public function recordDeliveryConfirmation(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Create the delivery confirmation
            $deliveryConfirmation = DeliveryConfirmation::create($data);
            
            // Update the shipment status
            $shipment = Shipment::findOrFail($data['shipment_id']);
            $shipment->update([
                'shipment_status' => $data['delivery_status'],
                'actual_delivery_date' => $data['delivery_timestamp']
            ]);
            
            DB::commit();
            return $deliveryConfirmation;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Calculate shipping performance metrics
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function calculateShippingPerformance($startDate = null, $endDate = null)
    {
        $query = Shipment::query();
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        $shipments = $query->get();
        
        // Calculate metrics
        $totalShipments = $shipments->count();
        $deliveredShipments = $shipments->where('shipment_status', 'delivered')->count();
        $inTransitShipments = $shipments->where('shipment_status', 'in_transit')->count();
        $exceptionShipments = $shipments->where('shipment_status', 'exception')->count();
        
        $onTimeDeliveries = 0;
        $totalTransitDays = 0;
        $deliveryConfirmations = 0;
        
        foreach ($shipments as $shipment) {
            if ($shipment->shipment_status === 'delivered' && $shipment->ship_date && $shipment->actual_delivery_date) {
                $deliveryConfirmations++;
                $transitDays = $shipment->ship_date->diffInDays($shipment->actual_delivery_date);
                $totalTransitDays += $transitDays;
                
                if ($shipment->expected_delivery_date && $shipment->actual_delivery_date <= $shipment->expected_delivery_date) {
                    $onTimeDeliveries++;
                }
            }
        }
        
        $onTimeDeliveryRate = $deliveryConfirmations > 0 ? ($onTimeDeliveries / $deliveryConfirmations) * 100 : 0;
        $avgTransitDays = $deliveryConfirmations > 0 ? $totalTransitDays / $deliveryConfirmations : 0;
        
        return [
            'total_shipments' => $totalShipments,
            'delivered_shipments' => $deliveredShipments,
            'in_transit_shipments' => $inTransitShipments,
            'exception_shipments' => $exceptionShipments,
            'on_time_deliveries' => $onTimeDeliveries,
            'on_time_delivery_rate' => $onTimeDeliveryRate,
            'avg_transit_days' => $avgTransitDays
        ];
    }
}