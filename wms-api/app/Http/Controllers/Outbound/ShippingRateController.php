<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\ShippingRate;
use App\Models\ShippingCarrier;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class ShippingRateController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of shipping rates
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShippingRate::with(['carrier', 'originZone', 'destinationZone']);

        // Apply filters
        if ($request->has('carrier_id')) {
            $query->where('carrier_id', $request->carrier_id);
        }

        if ($request->has('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->has('origin_zone_id')) {
            $query->where('origin_zone_id', $request->origin_zone_id);
        }

        if ($request->has('destination_zone_id')) {
            $query->where('destination_zone_id', $request->destination_zone_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('effective_date')) {
            $query->where('effective_from', '<=', $request->effective_date)
                  ->where(function ($q) use ($request) {
                      $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $request->effective_date);
                  });
        }

        $shippingRates = $query->orderBy('carrier_id')
            ->orderBy('service_type')
            ->orderBy('origin_zone_id')
            ->orderBy('destination_zone_id')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $shippingRates,
            'message' => 'Shipping rates retrieved successfully'
        ]);
    }

    /**
     * Store a newly created shipping rate
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'carrier_id' => 'required|exists:shipping_carriers,id',
            'service_type' => 'required|in:standard,express,overnight,ground,freight,same_day',
            'origin_zone_id' => 'required|exists:zones,id',
            'destination_zone_id' => 'required|exists:zones,id',
            'rate_structure' => 'required|in:flat,weight_based,distance_based,zone_based,dimensional',
            'base_rate' => 'required|numeric|min:0',
            'weight_tiers' => 'nullable|array',
            'weight_tiers.*.min_weight' => 'required_with:weight_tiers|numeric|min:0',
            'weight_tiers.*.max_weight' => 'required_with:weight_tiers|numeric|min:0',
            'weight_tiers.*.rate_per_unit' => 'required_with:weight_tiers|numeric|min:0',
            'distance_tiers' => 'nullable|array',
            'distance_tiers.*.min_distance' => 'required_with:distance_tiers|numeric|min:0',
            'distance_tiers.*.max_distance' => 'required_with:distance_tiers|numeric|min:0',
            'distance_tiers.*.rate_per_unit' => 'required_with:distance_tiers|numeric|min:0',
            'dimensional_factor' => 'nullable|numeric|min:0',
            'fuel_surcharge_rate' => 'nullable|numeric|min:0|max:100',
            'residential_surcharge' => 'nullable|numeric|min:0',
            'signature_surcharge' => 'nullable|numeric|min:0',
            'insurance_rate' => 'nullable|numeric|min:0|max:100',
            'minimum_charge' => 'nullable|numeric|min:0',
            'maximum_charge' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'currency' => 'required|string|size:3',
            'special_conditions' => 'nullable|array',
            'is_active' => 'boolean'
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

            // Check for overlapping rates
            $this->checkForOverlappingRates($request->all());

            $shippingRate = ShippingRate::create([
                'carrier_id' => $request->carrier_id,
                'service_type' => $request->service_type,
                'origin_zone_id' => $request->origin_zone_id,
                'destination_zone_id' => $request->destination_zone_id,
                'rate_structure' => $request->rate_structure,
                'base_rate' => $request->base_rate,
                'weight_tiers' => $request->weight_tiers ?? [],
                'distance_tiers' => $request->distance_tiers ?? [],
                'dimensional_factor' => $request->dimensional_factor ?? 0,
                'fuel_surcharge_rate' => $request->fuel_surcharge_rate ?? 0,
                'residential_surcharge' => $request->residential_surcharge ?? 0,
                'signature_surcharge' => $request->signature_surcharge ?? 0,
                'insurance_rate' => $request->insurance_rate ?? 0,
                'minimum_charge' => $request->minimum_charge,
                'maximum_charge' => $request->maximum_charge,
                'effective_from' => $request->effective_from,
                'effective_to' => $request->effective_to,
                'currency' => $request->currency,
                'special_conditions' => $request->special_conditions ?? [],
                'is_active' => $request->is_active ?? true,
                'created_by' => auth()->id(),
                'rate_calculation_rules' => $this->generateCalculationRules($request->all())
            ]);

            // Fire event
            $this->fireTransactionalEvent('outbound.shipping_rate.created', [
                'shipping_rate_id' => $shippingRate->id,
                'carrier_id' => $request->carrier_id,
                'service_type' => $request->service_type,
                'origin_zone_id' => $request->origin_zone_id,
                'destination_zone_id' => $request->destination_zone_id,
                'rate_structure' => $request->rate_structure,
                'base_rate' => $request->base_rate
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $shippingRate->load(['carrier', 'originZone', 'destinationZone']),
                'message' => 'Shipping rate created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipping rate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified shipping rate
     */
    public function show($id): JsonResponse
    {
        $shippingRate = ShippingRate::with([
            'carrier',
            'originZone',
            'destinationZone',
            'createdBy'
        ])->find($id);

        if (!$shippingRate) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping rate not found'
            ], 404);
        }

        // Get rate examples
        $rateExamples = $this->generateRateExamples($shippingRate);

        return response()->json([
            'success' => true,
            'data' => array_merge($shippingRate->toArray(), [
                'rate_examples' => $rateExamples
            ]),
            'message' => 'Shipping rate retrieved successfully'
        ]);
    }

    /**
     * Update the specified shipping rate
     */
    public function update(Request $request, $id): JsonResponse
    {
        $shippingRate = ShippingRate::find($id);

        if (!$shippingRate) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping rate not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'base_rate' => 'sometimes|numeric|min:0',
            'weight_tiers' => 'nullable|array',
            'weight_tiers.*.min_weight' => 'required_with:weight_tiers|numeric|min:0',
            'weight_tiers.*.max_weight' => 'required_with:weight_tiers|numeric|min:0',
            'weight_tiers.*.rate_per_unit' => 'required_with:weight_tiers|numeric|min:0',
            'distance_tiers' => 'nullable|array',
            'distance_tiers.*.min_distance' => 'required_with:distance_tiers|numeric|min:0',
            'distance_tiers.*.max_distance' => 'required_with:distance_tiers|numeric|min:0',
            'distance_tiers.*.rate_per_unit' => 'required_with:distance_tiers|numeric|min:0',
            'dimensional_factor' => 'nullable|numeric|min:0',
            'fuel_surcharge_rate' => 'nullable|numeric|min:0|max:100',
            'residential_surcharge' => 'nullable|numeric|min:0',
            'signature_surcharge' => 'nullable|numeric|min:0',
            'insurance_rate' => 'nullable|numeric|min:0|max:100',
            'minimum_charge' => 'nullable|numeric|min:0',
            'maximum_charge' => 'nullable|numeric|min:0',
            'effective_from' => 'sometimes|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'special_conditions' => 'nullable|array',
            'is_active' => 'sometimes|boolean'
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

            $updateData = $request->only([
                'base_rate',
                'weight_tiers',
                'distance_tiers',
                'dimensional_factor',
                'fuel_surcharge_rate',
                'residential_surcharge',
                'signature_surcharge',
                'insurance_rate',
                'minimum_charge',
                'maximum_charge',
                'effective_from',
                'effective_to',
                'special_conditions',
                'is_active'
            ]);

            // Regenerate calculation rules if rate structure changed
            if ($request->hasAny(['base_rate', 'weight_tiers', 'distance_tiers', 'dimensional_factor'])) {
                $updateData['rate_calculation_rules'] = $this->generateCalculationRules(array_merge(
                    $shippingRate->toArray(),
                    $request->all()
                ));
            }

            $shippingRate->update($updateData);

            // Fire event
            $this->fireTransactionalEvent('outbound.shipping_rate.updated', [
                'shipping_rate_id' => $shippingRate->id,
                'updated_fields' => array_keys($updateData),
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $shippingRate->load(['carrier', 'originZone', 'destinationZone']),
                'message' => 'Shipping rate updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update shipping rate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified shipping rate from storage
     */
    public function destroy($id): JsonResponse
    {
        $shippingRate = ShippingRate::find($id);

        if (!$shippingRate) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping rate not found'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Soft delete by setting is_active to false and effective_to to now
            $shippingRate->update([
                'is_active' => false,
                'effective_to' => now(),
                'deleted_at' => now(),
                'deleted_by' => auth()->id()
            ]);

            // Fire event
            $this->fireTransactionalEvent('outbound.shipping_rate.deleted', [
                'shipping_rate_id' => $shippingRate->id,
                'carrier_id' => $shippingRate->carrier_id,
                'service_type' => $shippingRate->service_type,
                'deleted_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Shipping rate deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete shipping rate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate shipping rate for given parameters
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'carrier_id' => 'nullable|exists:shipping_carriers,id',
            'service_type' => 'nullable|in:standard,express,overnight,ground,freight,same_day',
            'origin_zone_id' => 'required|exists:zones,id',
            'destination_zone_id' => 'required|exists:zones,id',
            'weight' => 'required|numeric|min:0',
            'dimensions' => 'required|array',
            'dimensions.length' => 'required|numeric|min:0',
            'dimensions.width' => 'required|numeric|min:0',
            'dimensions.height' => 'required|numeric|min:0',
            'declared_value' => 'nullable|numeric|min:0',
            'is_residential' => 'boolean',
            'requires_signature' => 'boolean',
            'distance' => 'nullable|numeric|min:0',
            'calculation_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $calculationDate = $request->calculation_date ?? now();
            $rates = $this->calculateShippingRates($request->all(), $calculationDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'calculation_parameters' => $request->all(),
                    'calculation_date' => $calculationDate,
                    'rates' => $rates
                ],
                'message' => 'Shipping rates calculated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate shipping rates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk import shipping rates
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rates' => 'required|array|min:1',
            'rates.*.carrier_id' => 'required|exists:shipping_carriers,id',
            'rates.*.service_type' => 'required|in:standard,express,overnight,ground,freight,same_day',
            'rates.*.origin_zone_id' => 'required|exists:zones,id',
            'rates.*.destination_zone_id' => 'required|exists:zones,id',
            'rates.*.rate_structure' => 'required|in:flat,weight_based,distance_based,zone_based,dimensional',
            'rates.*.base_rate' => 'required|numeric|min:0',
            'rates.*.effective_from' => 'required|date',
            'rates.*.currency' => 'required|string|size:3',
            'overwrite_existing' => 'boolean'
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

            $importResults = [
                'total_processed' => 0,
                'successful_imports' => 0,
                'failed_imports' => 0,
                'updated_existing' => 0,
                'errors' => []
            ];

            foreach ($request->rates as $index => $rateData) {
                $importResults['total_processed']++;

                try {
                    // Check if rate already exists
                    $existingRate = $this->findExistingRate($rateData);

                    if ($existingRate && !$request->overwrite_existing) {
                        $importResults['errors'][] = [
                            'index' => $index,
                            'error' => 'Rate already exists and overwrite is disabled'
                        ];
                        $importResults['failed_imports']++;
                        continue;
                    }

                    if ($existingRate && $request->overwrite_existing) {
                        // Update existing rate
                        $existingRate->update($this->prepareRateData($rateData));
                        $importResults['updated_existing']++;
                    } else {
                        // Create new rate
                        ShippingRate::create($this->prepareRateData($rateData));
                        $importResults['successful_imports']++;
                    }

                } catch (\Exception $e) {
                    $importResults['errors'][] = [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ];
                    $importResults['failed_imports']++;
                }
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.shipping_rates.bulk_imported', [
                'import_results' => $importResults,
                'imported_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $importResults,
                'message' => "Bulk import completed. {$importResults['successful_imports']} rates imported successfully."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to import shipping rates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shipping rate analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $carrierId = $request->get('carrier_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = ShippingRate::query();

        if ($carrierId) {
            $query->where('carrier_id', $carrierId);
        }

        $analytics = [
            'total_rates' => $query->where('is_active', true)->count(),
            'by_carrier' => $this->getRatesByCarrier($query),
            'by_service_type' => $this->getRatesByServiceType($query),
            'by_rate_structure' => $this->getRatesByRateStructure($query),
            'rate_coverage' => $this->getRateCoverage($query),
            'average_rates' => $this->getAverageRates($query),
            'rate_trends' => $this->getRateTrends($dateFrom, $dateTo, $carrierId),
            'cost_analysis' => $this->getCostAnalysis($dateFrom, $dateTo, $carrierId),
            'competitive_analysis' => $this->getCompetitiveAnalysis($query)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Shipping rate analytics retrieved successfully'
        ]);
    }

    /**
     * Helper methods
     */
    private function checkForOverlappingRates($rateData): void
    {
        $overlapping = ShippingRate::where('carrier_id', $rateData['carrier_id'])
            ->where('service_type', $rateData['service_type'])
            ->where('origin_zone_id', $rateData['origin_zone_id'])
            ->where('destination_zone_id', $rateData['destination_zone_id'])
            ->where('is_active', true)
            ->where('effective_from', '<=', $rateData['effective_to'] ?? '9999-12-31')
            ->where(function ($query) use ($rateData) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $rateData['effective_from']);
            })
            ->exists();

        if ($overlapping) {
            throw new \Exception('Overlapping shipping rate found for the same carrier, service, and route');
        }
    }

    private function generateCalculationRules($rateData): array
    {
        $rules = [
            'rate_structure' => $rateData['rate_structure'],
            'base_calculation' => $this->getBaseCalculationRule($rateData),
            'surcharges' => $this->getSurchargeRules($rateData),
            'limits' => $this->getLimitRules($rateData)
        ];

        return $rules;
    }

    private function getBaseCalculationRule($rateData): array
    {
        switch ($rateData['rate_structure']) {
            case 'flat':
                return ['type' => 'flat', 'rate' => $rateData['base_rate']];
            
            case 'weight_based':
                return [
                    'type' => 'weight_based',
                    'base_rate' => $rateData['base_rate'],
                    'tiers' => $rateData['weight_tiers'] ?? []
                ];
            
            case 'distance_based':
                return [
                    'type' => 'distance_based',
                    'base_rate' => $rateData['base_rate'],
                    'tiers' => $rateData['distance_tiers'] ?? []
                ];
            
            case 'dimensional':
                return [
                    'type' => 'dimensional',
                    'base_rate' => $rateData['base_rate'],
                    'dimensional_factor' => $rateData['dimensional_factor'] ?? 0
                ];
            
            default:
                return ['type' => 'zone_based', 'rate' => $rateData['base_rate']];
        }
    }

    private function getSurchargeRules($rateData): array
    {
        return [
            'fuel_surcharge' => $rateData['fuel_surcharge_rate'] ?? 0,
            'residential_surcharge' => $rateData['residential_surcharge'] ?? 0,
            'signature_surcharge' => $rateData['signature_surcharge'] ?? 0,
            'insurance_rate' => $rateData['insurance_rate'] ?? 0
        ];
    }

    private function getLimitRules($rateData): array
    {
        return [
            'minimum_charge' => $rateData['minimum_charge'],
            'maximum_charge' => $rateData['maximum_charge']
        ];
    }

    private function generateRateExamples($shippingRate): array
    {
        $examples = [];
        
        // Generate examples for different weights/distances
        $testCases = [
            ['weight' => 1, 'distance' => 100],
            ['weight' => 5, 'distance' => 250],
            ['weight' => 10, 'distance' => 500],
            ['weight' => 25, 'distance' => 1000]
        ];

        foreach ($testCases as $testCase) {
            $examples[] = [
                'weight' => $testCase['weight'],
                'distance' => $testCase['distance'],
                'calculated_rate' => $this->calculateRateForExample($shippingRate, $testCase)
            ];
        }

        return $examples;
    }

    private function calculateRateForExample($shippingRate, $testCase): float
    {
        // Simplified rate calculation for examples
        $rate = $shippingRate->base_rate;

        if ($shippingRate->rate_structure === 'weight_based' && !empty($shippingRate->weight_tiers)) {
            foreach ($shippingRate->weight_tiers as $tier) {
                if ($testCase['weight'] >= $tier['min_weight'] && $testCase['weight'] <= $tier['max_weight']) {
                    $rate = $tier['rate_per_unit'] * $testCase['weight'];
                    break;
                }
            }
        }

        // Apply minimum/maximum limits
        if ($shippingRate->minimum_charge && $rate < $shippingRate->minimum_charge) {
            $rate = $shippingRate->minimum_charge;
        }

        if ($shippingRate->maximum_charge && $rate > $shippingRate->maximum_charge) {
            $rate = $shippingRate->maximum_charge;
        }

        return round($rate, 2);
    }

    private function calculateShippingRates($parameters, $calculationDate): array
    {
        $query = ShippingRate::where('origin_zone_id', $parameters['origin_zone_id'])
            ->where('destination_zone_id', $parameters['destination_zone_id'])
            ->where('is_active', true)
            ->where('effective_from', '<=', $calculationDate)
            ->where(function ($q) use ($calculationDate) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $calculationDate);
            });

        if (isset($parameters['carrier_id'])) {
            $query->where('carrier_id', $parameters['carrier_id']);
        }

        if (isset($parameters['service_type'])) {
            $query->where('service_type', $parameters['service_type']);
        }

        $rates = $query->with('carrier')->get();
        $calculatedRates = [];

        foreach ($rates as $rate) {
            $calculatedRate = $this->performRateCalculation($rate, $parameters);
            $calculatedRates[] = [
                'carrier' => $rate->carrier,
                'service_type' => $rate->service_type,
                'rate_structure' => $rate->rate_structure,
                'base_rate' => $calculatedRate['base_rate'],
                'surcharges' => $calculatedRate['surcharges'],
                'total_rate' => $calculatedRate['total_rate'],
                'currency' => $rate->currency,
                'calculation_details' => $calculatedRate['details']
            ];
        }

        // Sort by total rate
        usort($calculatedRates, function ($a, $b) {
            return $a['total_rate'] <=> $b['total_rate'];
        });

        return $calculatedRates;
    }

    private function performRateCalculation($rate, $parameters): array
    {
        $baseRate = 0;
        $details = [];

        // Calculate base rate based on structure
        switch ($rate->rate_structure) {
            case 'flat':
                $baseRate = $rate->base_rate;
                $details['calculation_method'] = 'Flat rate';
                break;

            case 'weight_based':
                $baseRate = $this->calculateWeightBasedRate($rate, $parameters['weight']);
                $details['calculation_method'] = 'Weight-based calculation';
                $details['weight_used'] = $parameters['weight'];
                break;

            case 'distance_based':
                $distance = $parameters['distance'] ?? $this->calculateDistance($parameters);
                $baseRate = $this->calculateDistanceBasedRate($rate, $distance);
                $details['calculation_method'] = 'Distance-based calculation';
                $details['distance_used'] = $distance;
                break;

            case 'dimensional':
                $dimensionalWeight = $this->calculateDimensionalWeight($parameters['dimensions'], $rate->dimensional_factor);
                $actualWeight = $parameters['weight'];
                $chargeableWeight = max($dimensionalWeight, $actualWeight);
                $baseRate = $rate->base_rate * $chargeableWeight;
                $details['calculation_method'] = 'Dimensional weight calculation';
                $details['dimensional_weight'] = $dimensionalWeight;
                $details['actual_weight'] = $actualWeight;
                $details['chargeable_weight'] = $chargeableWeight;
                break;

            default:
                $baseRate = $rate->base_rate;
                $details['calculation_method'] = 'Zone-based rate';
        }

        // Calculate surcharges
        $surcharges = $this->calculateSurcharges($rate, $parameters);

        // Calculate total
        $totalRate = $baseRate + array_sum($surcharges);

        // Apply limits
        if ($rate->minimum_charge && $totalRate < $rate->minimum_charge) {
            $totalRate = $rate->minimum_charge;
            $details['minimum_charge_applied'] = true;
        }

        if ($rate->maximum_charge && $totalRate > $rate->maximum_charge) {
            $totalRate = $rate->maximum_charge;
            $details['maximum_charge_applied'] = true;
        }

        return [
            'base_rate' => round($baseRate, 2),
            'surcharges' => $surcharges,
            'total_rate' => round($totalRate, 2),
            'details' => $details
        ];
    }

    private function calculateWeightBasedRate($rate, $weight): float
    {
        if (empty($rate->weight_tiers)) {
            return $rate->base_rate * $weight;
        }

        foreach ($rate->weight_tiers as $tier) {
            if ($weight >= $tier['min_weight'] && $weight <= $tier['max_weight']) {
                return $tier['rate_per_unit'] * $weight;
            }
        }

        return $rate->base_rate * $weight;
    }

    private function calculateDistanceBasedRate($rate, $distance): float
    {
        if (empty($rate->distance_tiers)) {
            return $rate->base_rate * $distance;
        }

        foreach ($rate->distance_tiers as $tier) {
            if ($distance >= $tier['min_distance'] && $distance <= $tier['max_distance']) {
                return $tier['rate_per_unit'] * $distance;
            }
        }

        return $rate->base_rate * $distance;
    }

    private function calculateDimensionalWeight($dimensions, $factor): float
    {
        if ($factor <= 0) {
            return 0;
        }

        $volume = $dimensions['length'] * $dimensions['width'] * $dimensions['height'];
        return $volume / $factor;
    }

    private function calculateDistance($parameters): float
    {
        // This would integrate with a distance calculation service
        // For now, return a placeholder
        return 500; // km
    }

    private function calculateSurcharges($rate, $parameters): array
    {
        $surcharges = [];

        // Fuel surcharge
        if ($rate->fuel_surcharge_rate > 0) {
            $surcharges['fuel_surcharge'] = ($rate->base_rate * $rate->fuel_surcharge_rate) / 100;
        }

        // Residential surcharge
        if (($parameters['is_residential'] ?? false) && $rate->residential_surcharge > 0) {
            $surcharges['residential_surcharge'] = $rate->residential_surcharge;
        }

        // Signature surcharge
        if (($parameters['requires_signature'] ?? false) && $rate->signature_surcharge > 0) {
            $surcharges['signature_surcharge'] = $rate->signature_surcharge;
        }

        // Insurance
        if (isset($parameters['declared_value']) && $rate->insurance_rate > 0) {
            $surcharges['insurance'] = ($parameters['declared_value'] * $rate->insurance_rate) / 100;
        }

        return array_map(function ($value) {
            return round($value, 2);
        }, $surcharges);
    }

    private function findExistingRate($rateData): ?ShippingRate
    {
        return ShippingRate::where('carrier_id', $rateData['carrier_id'])
            ->where('service_type', $rateData['service_type'])
            ->where('origin_zone_id', $rateData['origin_zone_id'])
            ->where('destination_zone_id', $rateData['destination_zone_id'])
            ->where('is_active', true)
            ->first();
    }

    private function prepareRateData($rateData): array
    {
        return array_merge($rateData, [
            'created_by' => auth()->id(),
            'rate_calculation_rules' => $this->generateCalculationRules($rateData)
        ]);
    }

    /**
     * Analytics helper methods
     */
    private function getRatesByCarrier($query): array
    {
        return $query->with('carrier')
            ->where('is_active', true)
            ->selectRaw('carrier_id, count(*) as rate_count, avg(base_rate) as avg_rate')
            ->groupBy('carrier_id')
            ->get()
            ->map(function ($item) {
                return [
                    'carrier' => $item->carrier,
                    'rate_count' => $item->rate_count,
                    'avg_rate' => round($item->avg_rate, 2)
                ];
            })
            ->toArray();
    }

    private function getRatesByServiceType($query): array
    {
        return $query->where('is_active', true)
            ->groupBy('service_type')
            ->selectRaw('service_type, count(*) as count, avg(base_rate) as avg_rate')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->service_type => [
                    'count' => $item->count,
                    'avg_rate' => round($item->avg_rate, 2)
                ]];
            })
            ->toArray();
    }

    private function getRatesByRateStructure($query): array
    {
        return $query->where('is_active', true)
            ->groupBy('rate_structure')
            ->selectRaw('rate_structure, count(*) as count')
            ->pluck('count', 'rate_structure')
            ->toArray();
    }

    private function getRateCoverage($query): array
    {
        $totalZonePairs = Zone::count() * Zone::count();
        $coveredPairs = $query->where('is_active', true)
            ->distinct()
            ->selectRaw('CONCAT(origin_zone_id, "-", destination_zone_id) as zone_pair')
            ->get()
            ->count();

        return [
            'total_possible_routes' => $totalZonePairs,
            'covered_routes' => $coveredPairs,
            'coverage_percentage' => $totalZonePairs > 0 ? ($coveredPairs / $totalZonePairs) * 100 : 0
        ];
    }

    private function getAverageRates($query): array
    {
        return [
            'overall_average' => round($query->where('is_active', true)->avg('base_rate'), 2),
            'by_service_type' => $query->where('is_active', true)
                ->groupBy('service_type')
                ->selectRaw('service_type, avg(base_rate) as avg_rate')
                ->pluck('avg_rate', 'service_type')
                ->map(function ($rate) {
                    return round($rate, 2);
                })
                ->toArray()
        ];
    }

    private function getRateTrends($dateFrom, $dateTo, $carrierId): array
    {
        // This would analyze rate changes over time
        return [
            'rate_increases' => 15,
            'rate_decreases' => 8,
            'new_rates_added' => 25,
            'rates_discontinued' => 5
        ];
    }

    private function getCostAnalysis($dateFrom, $dateTo, $carrierId): array
    {
        // This would analyze actual shipping costs vs rates
        return [
            'average_shipping_cost' => 12.50,
            'cost_variance' => 5.2,
            'most_expensive_routes' => [
                ['origin' => 'Zone A', 'destination' => 'Zone B', 'avg_cost' => 25.00],
                ['origin' => 'Zone C', 'destination' => 'Zone D', 'avg_cost' => 22.50]
            ],
            'most_economical_routes' => [
                ['origin' => 'Zone E', 'destination' => 'Zone F', 'avg_cost' => 8.00],
                ['origin' => 'Zone G', 'destination' => 'Zone H', 'avg_cost' => 9.50]
            ]
        ];
    }

    private function getCompetitiveAnalysis($query): array
    {
        // This would compare rates across carriers
        return [
            'most_competitive_carrier' => 'Carrier A',
            'highest_rates_carrier' => 'Carrier B',
            'rate_spread_percentage' => 25.5,
            'service_type_leaders' => [
                'standard' => 'Carrier A',
                'express' => 'Carrier C',
                'overnight' => 'Carrier B'
            ]
        ];
    }
}