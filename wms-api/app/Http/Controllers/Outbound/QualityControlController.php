<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\OutboundQualityCheck;
use App\Models\Outbound\WeightVerification;
use App\Models\Outbound\DimensionVerification;
use App\Models\Outbound\QualityCheckpoint;
use App\Models\Outbound\DamageInspection;
use App\Models\Outbound\QualityMetric;
use App\Models\Outbound\QualityException;
use App\Models\Outbound\PackedCarton;
use App\Models\Outbound\Shipment;
use App\Models\SalesOrder;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class QualityControlController extends Controller
{
    /**
     * Get quality checkpoints
     *
     * @return \Illuminate\Http\Response
     */
    public function getQualityCheckpoints()
    {
        $checkpoints = QualityCheckpoint::with('warehouse')
            ->orderBy('checkpoint_name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $checkpoints
        ]);
    }

    /**
     * Create a quality checkpoint
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createQualityCheckpoint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkpoint_name' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'zone_id' => 'nullable|exists:zones,id',
            'checkpoint_type' => 'required|in:packing,shipping,final,custom',
            'checkpoint_location' => 'required|string|max:255',
            'is_mandatory' => 'boolean',
            'quality_criteria' => 'required|json',
            'checkpoint_sequence' => 'required|integer',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a unique checkpoint code
        $checkpointCode = 'QC-' . strtoupper(Str::random(6));
        
        $checkpoint = QualityCheckpoint::create([
            'checkpoint_code' => $checkpointCode,
            'checkpoint_name' => $request->checkpoint_name,
            'warehouse_id' => $request->warehouse_id,
            'zone_id' => $request->zone_id,
            'checkpoint_type' => $request->checkpoint_type,
            'checkpoint_location' => $request->checkpoint_location,
            'is_mandatory' => $request->is_mandatory ?? true,
            'quality_criteria' => $request->quality_criteria,
            'checkpoint_sequence' => $request->checkpoint_sequence,
            'is_active' => $request->is_active ?? true,
            'created_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quality checkpoint created successfully',
            'data' => $checkpoint
        ], 201);
    }

    /**
     * Perform outbound quality check
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function performQualityCheck(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkpoint_id' => 'required|exists:quality_checkpoints,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'packed_carton_id' => 'nullable|exists:packed_cartons,id',
            'inspector_id' => 'required|exists:employees,id',
            'check_results' => 'required|json',
            'overall_result' => 'required|in:passed,failed,conditional',
            'quality_score' => 'nullable|numeric|min:0|max:100',
            'inspection_notes' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
            'requires_reinspection' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Generate a unique check number
        $checkNumber = 'QCK-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        
        $qualityCheck = OutboundQualityCheck::create([
            'check_number' => $checkNumber,
            'checkpoint_id' => $request->checkpoint_id,
            'sales_order_id' => $request->sales_order_id,
            'shipment_id' => $request->shipment_id,
            'packed_carton_id' => $request->packed_carton_id,
            'inspector_id' => $request->inspector_id,
            'check_results' => $request->check_results,
            'overall_result' => $request->overall_result,
            'quality_score' => $request->quality_score,
            'inspection_notes' => $request->inspection_notes,
            'corrective_actions' => $request->corrective_actions,
            'requires_reinspection' => $request->requires_reinspection ?? false,
            'inspected_at' => now()
        ]);
        
        // If quality check failed, create an exception
        if ($request->overall_result === 'failed') {
            $exceptionNumber = 'QEX-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            
            QualityException::create([
                'exception_number' => $exceptionNumber,
                'quality_check_id' => $qualityCheck->id,
                'sales_order_id' => $request->sales_order_id,
                'shipment_id' => $request->shipment_id,
                'packed_carton_id' => $request->packed_carton_id,
                'exception_type' => 'quality_failure',
                'exception_description' => $request->inspection_notes ?? 'Failed quality check',
                'exception_status' => 'open',
                'reported_by' => $request->inspector_id,
                'reported_at' => now()
            ]);
            
            // Update related entities based on failure
            if ($request->packed_carton_id) {
                $packedCarton = PackedCarton::find($request->packed_carton_id);
                $packedCarton->carton_status = 'damaged';
                $packedCarton->save();
            }
            
            if ($request->shipment_id) {
                $shipment = Shipment::find($request->shipment_id);
                $shipment->shipment_status = 'exception';
                $shipment->save();
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Quality check completed',
            'data' => $qualityCheck
        ]);
    }

    /**
     * Verify weight
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function verifyWeight(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'packed_carton_id' => 'required|exists:packed_cartons,id',
            'expected_weight_kg' => 'required|numeric',
            'actual_weight_kg' => 'required|numeric',
            'tolerance_percentage' => 'nullable|numeric|min:0|max:100',
            'verification_status' => 'required|in:passed,failed,warning',
            'verified_by' => 'required|exists:employees,id',
            'verification_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Calculate weight difference percentage
        $weightDifference = abs($request->expected_weight_kg - $request->actual_weight_kg);
        $weightDifferencePercentage = ($weightDifference / $request->expected_weight_kg) * 100;
        
        $weightVerification = WeightVerification::create([
            'packed_carton_id' => $request->packed_carton_id,
            'expected_weight_kg' => $request->expected_weight_kg,
            'actual_weight_kg' => $request->actual_weight_kg,
            'weight_difference_kg' => $weightDifference,
            'weight_difference_percentage' => round($weightDifferencePercentage, 2),
            'tolerance_percentage' => $request->tolerance_percentage ?? 5.0,
            'verification_status' => $request->verification_status,
            'verified_by' => $request->verified_by,
            'verification_notes' => $request->verification_notes,
            'verified_at' => now()
        ]);
        
        // If weight verification failed, create an exception
        if ($request->verification_status === 'failed') {
            $exceptionNumber = 'WEX-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            
            QualityException::create([
                'exception_number' => $exceptionNumber,
                'packed_carton_id' => $request->packed_carton_id,
                'exception_type' => 'weight_mismatch',
                'exception_description' => "Weight mismatch: Expected {$request->expected_weight_kg}kg, Actual {$request->actual_weight_kg}kg",
                'exception_status' => 'open',
                'reported_by' => $request->verified_by,
                'reported_at' => now()
            ]);
            
            // Update carton status
            $packedCarton = PackedCarton::find($request->packed_carton_id);
            $packedCarton->carton_status = 'damaged';
            $packedCarton->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Weight verification completed',
            'data' => $weightVerification
        ]);
    }

    /**
     * Verify dimensions
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function verifyDimensions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'packed_carton_id' => 'required|exists:packed_cartons,id',
            'expected_length_cm' => 'required|numeric',
            'expected_width_cm' => 'required|numeric',
            'expected_height_cm' => 'required|numeric',
            'actual_length_cm' => 'required|numeric',
            'actual_width_cm' => 'required|numeric',
            'actual_height_cm' => 'required|numeric',
            'tolerance_percentage' => 'nullable|numeric|min:0|max:100',
            'verification_status' => 'required|in:passed,failed,warning',
            'verified_by' => 'required|exists:employees,id',
            'verification_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Calculate expected and actual volume
        $expectedVolume = $request->expected_length_cm * $request->expected_width_cm * $request->expected_height_cm;
        $actualVolume = $request->actual_length_cm * $request->actual_width_cm * $request->actual_height_cm;
        
        // Calculate volume difference percentage
        $volumeDifference = abs($expectedVolume - $actualVolume);
        $volumeDifferencePercentage = ($volumeDifference / $expectedVolume) * 100;
        
        $dimensionVerification = DimensionVerification::create([
            'packed_carton_id' => $request->packed_carton_id,
            'expected_length_cm' => $request->expected_length_cm,
            'expected_width_cm' => $request->expected_width_cm,
            'expected_height_cm' => $request->expected_height_cm,
            'expected_volume_cm3' => $expectedVolume,
            'actual_length_cm' => $request->actual_length_cm,
            'actual_width_cm' => $request->actual_width_cm,
            'actual_height_cm' => $request->actual_height_cm,
            'actual_volume_cm3' => $actualVolume,
            'volume_difference_cm3' => $volumeDifference,
            'volume_difference_percentage' => round($volumeDifferencePercentage, 2),
            'tolerance_percentage' => $request->tolerance_percentage ?? 10.0,
            'verification_status' => $request->verification_status,
            'verified_by' => $request->verified_by,
            'verification_notes' => $request->verification_notes,
            'verified_at' => now()
        ]);
        
        // If dimension verification failed, create an exception
        if ($request->verification_status === 'failed') {
            $exceptionNumber = 'DEX-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            
            QualityException::create([
                'exception_number' => $exceptionNumber,
                'packed_carton_id' => $request->packed_carton_id,
                'exception_type' => 'dimension_mismatch',
                'exception_description' => "Dimension mismatch: Expected volume {$expectedVolume}cm³, Actual volume {$actualVolume}cm³",
                'exception_status' => 'open',
                'reported_by' => $request->verified_by,
                'reported_at' => now()
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Dimension verification completed',
            'data' => $dimensionVerification
        ]);
    }

    /**
     * Perform damage inspection
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function performDamageInspection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'packed_carton_id' => 'nullable|exists:packed_cartons,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'product_id' => 'nullable|exists:products,id',
            'inspection_type' => 'required|in:pre_ship,receiving,return',
            'damage_found' => 'required|boolean',
            'damage_type' => 'nullable|string',
            'damage_severity' => 'nullable|in:minor,moderate,severe',
            'damage_description' => 'nullable|string',
            'damage_photos' => 'nullable|json',
            'inspector_id' => 'required|exists:employees,id',
            'inspection_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Generate a unique inspection number
        $inspectionNumber = 'DI-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        
        $damageInspection = DamageInspection::create([
            'inspection_number' => $inspectionNumber,
            'packed_carton_id' => $request->packed_carton_id,
            'shipment_id' => $request->shipment_id,
            'product_id' => $request->product_id,
            'inspection_type' => $request->inspection_type,
            'damage_found' => $request->damage_found,
            'damage_type' => $request->damage_type,
            'damage_severity' => $request->damage_severity,
            'damage_description' => $request->damage_description,
            'damage_photos' => $request->damage_photos,
            'inspector_id' => $request->inspector_id,
            'inspection_notes' => $request->inspection_notes,
            'inspected_at' => now()
        ]);
        
        // If damage found, create an exception and update related entities
        if ($request->damage_found) {
            $exceptionNumber = 'DMGEX-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            
            QualityException::create([
                'exception_number' => $exceptionNumber,
                'packed_carton_id' => $request->packed_carton_id,
                'shipment_id' => $request->shipment_id,
                'product_id' => $request->product_id,
                'exception_type' => 'damage',
                'exception_description' => $request->damage_description ?? 'Damage found during inspection',
                'exception_status' => 'open',
                'reported_by' => $request->inspector_id,
                'reported_at' => now()
            ]);
            
            // Update related entities based on damage
            if ($request->packed_carton_id) {
                $packedCarton = PackedCarton::find($request->packed_carton_id);
                $packedCarton->carton_status = 'damaged';
                $packedCarton->save();
            }
            
            if ($request->shipment_id) {
                $shipment = Shipment::find($request->shipment_id);
                $shipment->shipment_status = 'exception';
                $shipment->save();
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Damage inspection completed',
            'data' => $damageInspection
        ]);
    }

    /**
     * Get quality exceptions
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getQualityExceptions(Request $request)
    {
        $query = QualityException::with(['reporter']);
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('exception_status', $request->status);
        }
        
        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('exception_type', $request->type);
        }
        
        // Filter by date range if provided
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('reported_at', [$request->from_date, $request->to_date]);
        }
        
        $exceptions = $query->orderBy('reported_at', 'desc')->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $exceptions
        ]);
    }

    /**
     * Resolve quality exception
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function resolveQualityException(Request $request, $id)
    {
        $exception = QualityException::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'resolution_action' => 'required|string',
            'resolution_notes' => 'required|string',
            'resolved_by' => 'required|exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $exception->exception_status = 'resolved';
        $exception->resolution_action = $request->resolution_action;
        $exception->resolution_notes = $request->resolution_notes;
        $exception->resolved_by = $request->resolved_by;
        $exception->resolved_at = now();
        $exception->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Quality exception resolved',
            'data' => $exception
        ]);
    }

    /**
     * Get quality metrics
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getQualityMetrics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'warehouse_id' => 'nullable|exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get quality checks in date range
        $query = OutboundQualityCheck::whereBetween('inspected_at', [$request->from_date, $request->to_date]);
        
        // Filter by warehouse if provided
        if ($request->has('warehouse_id')) {
            $query->whereHas('checkpoint', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }
        
        $qualityChecks = $query->get();
        
        // Calculate metrics
        $totalChecks = $qualityChecks->count();
        $passedChecks = $qualityChecks->where('overall_result', 'passed')->count();
        $failedChecks = $qualityChecks->where('overall_result', 'failed')->count();
        $conditionalChecks = $qualityChecks->where('overall_result', 'conditional')->count();
        
        $passRate = $totalChecks > 0 ? ($passedChecks / $totalChecks) * 100 : 0;
        $failRate = $totalChecks > 0 ? ($failedChecks / $totalChecks) * 100 : 0;
        
        // Get average quality score
        $avgQualityScore = $qualityChecks->avg('quality_score') ?? 0;
        
        // Get exceptions in date range
        $exceptions = QualityException::whereBetween('reported_at', [$request->from_date, $request->to_date])->get();
        
        // Group exceptions by type
        $exceptionsByType = $exceptions->groupBy('exception_type')->map->count();
        
        // Calculate resolution time
        $resolvedExceptions = $exceptions->whereNotNull('resolved_at');
        $avgResolutionTimeHours = 0;
        
        if ($resolvedExceptions->count() > 0) {
            $totalResolutionTime = 0;
            
            foreach ($resolvedExceptions as $exception) {
                $reportedAt = new \DateTime($exception->reported_at);
                $resolvedAt = new \DateTime($exception->resolved_at);
                $interval = $reportedAt->diff($resolvedAt);
                $totalResolutionTime += ($interval->days * 24) + $interval->h + ($interval->i / 60);
            }
            
            $avgResolutionTimeHours = $totalResolutionTime / $resolvedExceptions->count();
        }
        
        // Save metrics to database
        $qualityMetric = QualityMetric::create([
            'date_range_start' => $request->from_date,
            'date_range_end' => $request->to_date,
            'warehouse_id' => $request->warehouse_id,
            'total_quality_checks' => $totalChecks,
            'passed_checks' => $passedChecks,
            'failed_checks' => $failedChecks,
            'conditional_checks' => $conditionalChecks,
            'pass_rate_percentage' => round($passRate, 2),
            'average_quality_score' => round($avgQualityScore, 2),
            'total_exceptions' => $exceptions->count(),
            'exceptions_by_type' => json_encode($exceptionsByType),
            'average_resolution_time_hours' => round($avgResolutionTimeHours, 2),
            'generated_by' => auth()->id(),
            'generated_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $qualityMetric,
                'details' => [
                    'total_checks' => $totalChecks,
                    'passed_checks' => $passedChecks,
                    'failed_checks' => $failedChecks,
                    'conditional_checks' => $conditionalChecks,
                    'pass_rate_percentage' => round($passRate, 2),
                    'fail_rate_percentage' => round($failRate, 2),
                    'average_quality_score' => round($avgQualityScore, 2),
                    'total_exceptions' => $exceptions->count(),
                    'exceptions_by_type' => $exceptionsByType,
                    'average_resolution_time_hours' => round($avgResolutionTimeHours, 2)
                ]
            ]
        ]);
    }
}