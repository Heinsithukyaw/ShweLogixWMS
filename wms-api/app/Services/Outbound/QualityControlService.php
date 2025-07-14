<?php

namespace App\Services\Outbound;

use App\Models\Outbound\QualityCheckpoint;
use App\Models\Outbound\OutboundQualityCheck;
use App\Models\Outbound\QualityException;
use App\Models\Outbound\WeightVerification;
use App\Models\Outbound\DimensionVerification;
use App\Models\Outbound\DamageInspection;
use App\Models\Outbound\PackedCarton;
use App\Models\Outbound\Shipment;
use App\Models\SalesOrder;
use App\Models\Employee;
use Exception;
use DB;

class QualityControlService
{
    /**
     * Perform a quality check
     *
     * @param array $data
     * @return \App\Models\Outbound\OutboundQualityCheck
     */
    public function performQualityCheck(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Get the checkpoint
            $checkpoint = QualityCheckpoint::findOrFail($data['checkpoint_id']);
            
            // Create the quality check
            $qualityCheck = OutboundQualityCheck::create([
                'check_number' => $this->generateCheckNumber(),
                'checkpoint_id' => $data['checkpoint_id'],
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'shipment_id' => $data['shipment_id'] ?? null,
                'packed_carton_id' => $data['packed_carton_id'] ?? null,
                'inspector_id' => $data['inspector_id'] ?? auth()->id(),
                'check_results' => $data['check_results'],
                'overall_result' => $data['overall_result'],
                'quality_score' => $data['quality_score'] ?? null,
                'inspection_notes' => $data['inspection_notes'] ?? null,
                'corrective_actions' => $data['corrective_actions'] ?? null,
                'requires_reinspection' => $data['requires_reinspection'] ?? false,
                'inspected_at' => now()
            ]);
            
            // Create quality exceptions for failed checks
            $checkResults = json_decode($data['check_results'], true);
            $qualityCriteria = json_decode($checkpoint->quality_criteria, true);
            
            foreach ($checkResults as $key => $result) {
                if ($result === false || $result === 'failed') {
                    $criterionName = isset($qualityCriteria[$key]['name']) ? $qualityCriteria[$key]['name'] : "Criterion $key";
                    $criterionSeverity = isset($qualityCriteria[$key]['severity']) ? $qualityCriteria[$key]['severity'] : 'minor';
                    
                    QualityException::create([
                        'exception_number' => $this->generateExceptionNumber(),
                        'quality_check_id' => $qualityCheck->id,
                        'checkpoint_id' => $data['checkpoint_id'],
                        'sales_order_id' => $data['sales_order_id'] ?? null,
                        'shipment_id' => $data['shipment_id'] ?? null,
                        'packed_carton_id' => $data['packed_carton_id'] ?? null,
                        'exception_type' => 'quality_check_failure',
                        'exception_severity' => $criterionSeverity,
                        'exception_status' => 'open',
                        'exception_details' => json_encode([
                            'criterion_name' => $criterionName,
                            'criterion_key' => $key,
                            'expected_result' => 'pass',
                            'actual_result' => 'fail'
                        ]),
                        'reported_by' => $data['inspector_id'] ?? auth()->id(),
                        'created_at' => now()
                    ]);
                }
            }
            
            // Update the status of the related entity based on the check result
            if ($data['packed_carton_id']) {
                $this->updatePackedCartonStatus($data['packed_carton_id'], $data['overall_result']);
            } else if ($data['shipment_id']) {
                $this->updateShipmentStatus($data['shipment_id'], $data['overall_result']);
            }
            
            DB::commit();
            return $qualityCheck;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Generate a unique quality check number
     *
     * @return string
     */
    private function generateCheckNumber()
    {
        $prefix = 'QC';
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        
        return $prefix . $timestamp . $random;
    }
    
    /**
     * Generate a unique quality exception number
     *
     * @return string
     */
    private function generateExceptionNumber()
    {
        $prefix = 'QE';
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        
        return $prefix . $timestamp . $random;
    }
    
    /**
     * Update packed carton status based on quality check result
     *
     * @param int $packedCartonId
     * @param string $checkResult
     * @return void
     */
    private function updatePackedCartonStatus($packedCartonId, $checkResult)
    {
        $packedCarton = PackedCarton::findOrFail($packedCartonId);
        
        if ($checkResult === 'failed') {
            $packedCarton->update(['carton_status' => 'quality_failed']);
        } else if ($checkResult === 'conditional') {
            $packedCarton->update(['carton_status' => 'quality_conditional']);
        } else {
            $packedCarton->update(['carton_status' => 'quality_passed']);
        }
    }
    
    /**
     * Update shipment status based on quality check result
     *
     * @param int $shipmentId
     * @param string $checkResult
     * @return void
     */
    private function updateShipmentStatus($shipmentId, $checkResult)
    {
        $shipment = Shipment::findOrFail($shipmentId);
        
        if ($checkResult === 'failed') {
            $shipment->update(['shipment_status' => 'quality_hold']);
        }
    }
    
    /**
     * Verify weight of a packed carton
     *
     * @param array $data
     * @return \App\Models\Outbound\WeightVerification
     */
    public function verifyWeight(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Create weight verification record
            $weightVerification = WeightVerification::create([
                'packed_carton_id' => $data['packed_carton_id'],
                'expected_weight_kg' => $data['expected_weight_kg'],
                'actual_weight_kg' => $data['actual_weight_kg'],
                'tolerance_percentage' => $data['tolerance_percentage'] ?? 5.0,
                'verification_notes' => $data['verification_notes'] ?? null,
                'verified_by' => $data['verified_by'] ?? auth()->id(),
                'verified_at' => now()
            ]);
            
            // Calculate difference and determine verification status
            $difference = abs($data['expected_weight_kg'] - $data['actual_weight_kg']);
            $differencePercentage = ($difference / $data['expected_weight_kg']) * 100;
            $tolerancePercentage = $data['tolerance_percentage'] ?? 5.0;
            
            if ($differencePercentage <= $tolerancePercentage) {
                $weightVerification->verification_status = 'passed';
            } else if ($differencePercentage <= $tolerancePercentage * 2) {
                $weightVerification->verification_status = 'warning';
                
                // Create a quality exception for warning
                $this->createWeightException($weightVerification, 'minor');
            } else {
                $weightVerification->verification_status = 'failed';
                
                // Create a quality exception for failure
                $this->createWeightException($weightVerification, 'major');
                
                // Update packed carton status
                $packedCarton = PackedCarton::findOrFail($data['packed_carton_id']);
                $packedCarton->update(['carton_status' => 'weight_verification_failed']);
            }
            
            $weightVerification->save();
            
            DB::commit();
            return $weightVerification;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Create a quality exception for weight verification
     *
     * @param \App\Models\Outbound\WeightVerification $weightVerification
     * @param string $severity
     * @return \App\Models\Outbound\QualityException
     */
    private function createWeightException($weightVerification, $severity)
    {
        $packedCarton = PackedCarton::findOrFail($weightVerification->packed_carton_id);
        
        return QualityException::create([
            'exception_number' => $this->generateExceptionNumber(),
            'checkpoint_id' => null,
            'packed_carton_id' => $weightVerification->packed_carton_id,
            'sales_order_id' => $packedCarton->packOrder->sales_order_id,
            'exception_type' => 'weight_verification',
            'exception_severity' => $severity,
            'exception_status' => 'open',
            'exception_details' => json_encode([
                'expected_weight_kg' => $weightVerification->expected_weight_kg,
                'actual_weight_kg' => $weightVerification->actual_weight_kg,
                'difference_kg' => abs($weightVerification->expected_weight_kg - $weightVerification->actual_weight_kg),
                'difference_percentage' => ($weightVerification->calculateDifferencePercentage()),
                'tolerance_percentage' => $weightVerification->tolerance_percentage
            ]),
            'reported_by' => $weightVerification->verified_by,
            'created_at' => now()
        ]);
    }
    
    /**
     * Verify dimensions of a packed carton
     *
     * @param array $data
     * @return \App\Models\Outbound\DimensionVerification
     */
    public function verifyDimensions(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Create dimension verification record
            $dimensionVerification = DimensionVerification::create([
                'packed_carton_id' => $data['packed_carton_id'],
                'expected_length_cm' => $data['expected_length_cm'],
                'expected_width_cm' => $data['expected_width_cm'],
                'expected_height_cm' => $data['expected_height_cm'],
                'actual_length_cm' => $data['actual_length_cm'],
                'actual_width_cm' => $data['actual_width_cm'],
                'actual_height_cm' => $data['actual_height_cm'],
                'tolerance_percentage' => $data['tolerance_percentage'] ?? 5.0,
                'verification_notes' => $data['verification_notes'] ?? null,
                'verified_by' => $data['verified_by'] ?? auth()->id(),
                'verified_at' => now()
            ]);
            
            // Calculate expected and actual volume
            $expectedVolume = $data['expected_length_cm'] * $data['expected_width_cm'] * $data['expected_height_cm'];
            $actualVolume = $data['actual_length_cm'] * $data['actual_width_cm'] * $data['actual_height_cm'];
            
            // Calculate difference and determine verification status
            $volumeDifference = abs($expectedVolume - $actualVolume);
            $volumeDifferencePercentage = ($volumeDifference / $expectedVolume) * 100;
            $tolerancePercentage = $data['tolerance_percentage'] ?? 5.0;
            
            if ($volumeDifferencePercentage <= $tolerancePercentage) {
                $dimensionVerification->verification_status = 'passed';
            } else if ($volumeDifferencePercentage <= $tolerancePercentage * 2) {
                $dimensionVerification->verification_status = 'warning';
                
                // Create a quality exception for warning
                $this->createDimensionException($dimensionVerification, 'minor');
            } else {
                $dimensionVerification->verification_status = 'failed';
                
                // Create a quality exception for failure
                $this->createDimensionException($dimensionVerification, 'major');
                
                // Update packed carton status
                $packedCarton = PackedCarton::findOrFail($data['packed_carton_id']);
                $packedCarton->update(['carton_status' => 'dimension_verification_failed']);
            }
            
            $dimensionVerification->save();
            
            DB::commit();
            return $dimensionVerification;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Create a quality exception for dimension verification
     *
     * @param \App\Models\Outbound\DimensionVerification $dimensionVerification
     * @param string $severity
     * @return \App\Models\Outbound\QualityException
     */
    private function createDimensionException($dimensionVerification, $severity)
    {
        $packedCarton = PackedCarton::findOrFail($dimensionVerification->packed_carton_id);
        
        $expectedVolume = $dimensionVerification->expected_length_cm * $dimensionVerification->expected_width_cm * $dimensionVerification->expected_height_cm;
        $actualVolume = $dimensionVerification->actual_length_cm * $dimensionVerification->actual_width_cm * $dimensionVerification->actual_height_cm;
        $volumeDifference = abs($expectedVolume - $actualVolume);
        $volumeDifferencePercentage = ($volumeDifference / $expectedVolume) * 100;
        
        return QualityException::create([
            'exception_number' => $this->generateExceptionNumber(),
            'checkpoint_id' => null,
            'packed_carton_id' => $dimensionVerification->packed_carton_id,
            'sales_order_id' => $packedCarton->packOrder->sales_order_id,
            'exception_type' => 'dimension_verification',
            'exception_severity' => $severity,
            'exception_status' => 'open',
            'exception_details' => json_encode([
                'expected_dimensions' => [
                    'length_cm' => $dimensionVerification->expected_length_cm,
                    'width_cm' => $dimensionVerification->expected_width_cm,
                    'height_cm' => $dimensionVerification->expected_height_cm,
                    'volume_cm3' => $expectedVolume
                ],
                'actual_dimensions' => [
                    'length_cm' => $dimensionVerification->actual_length_cm,
                    'width_cm' => $dimensionVerification->actual_width_cm,
                    'height_cm' => $dimensionVerification->actual_height_cm,
                    'volume_cm3' => $actualVolume
                ],
                'volume_difference_cm3' => $volumeDifference,
                'volume_difference_percentage' => $volumeDifferencePercentage,
                'tolerance_percentage' => $dimensionVerification->tolerance_percentage
            ]),
            'reported_by' => $dimensionVerification->verified_by,
            'created_at' => now()
        ]);
    }
    
    /**
     * Perform a damage inspection
     *
     * @param array $data
     * @return \App\Models\Outbound\DamageInspection
     */
    public function performDamageInspection(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Create damage inspection record
            $damageInspection = DamageInspection::create([
                'packed_carton_id' => $data['packed_carton_id'] ?? null,
                'shipment_id' => $data['shipment_id'] ?? null,
                'inspection_type' => $data['inspection_type'],
                'damage_found' => $data['damage_found'],
                'damage_severity' => $data['damage_severity'] ?? null,
                'damage_location' => $data['damage_location'] ?? null,
                'damage_description' => $data['damage_description'] ?? null,
                'damage_photos' => $data['damage_photos'] ?? null,
                'inspector_id' => $data['inspector_id'] ?? auth()->id(),
                'inspected_at' => now()
            ]);
            
            // If damage is found, create a quality exception
            if ($data['damage_found']) {
                $this->createDamageException($damageInspection);
                
                // Update the status of the related entity
                if ($data['packed_carton_id']) {
                    $packedCarton = PackedCarton::findOrFail($data['packed_carton_id']);
                    $packedCarton->update(['carton_status' => 'damaged']);
                } else if ($data['shipment_id']) {
                    $shipment = Shipment::findOrFail($data['shipment_id']);
                    $shipment->update(['shipment_status' => 'damaged']);
                }
            }
            
            DB::commit();
            return $damageInspection;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Create a quality exception for damage inspection
     *
     * @param \App\Models\Outbound\DamageInspection $damageInspection
     * @return \App\Models\Outbound\QualityException
     */
    private function createDamageException($damageInspection)
    {
        $salesOrderId = null;
        
        if ($damageInspection->packed_carton_id) {
            $packedCarton = PackedCarton::findOrFail($damageInspection->packed_carton_id);
            $salesOrderId = $packedCarton->packOrder->sales_order_id;
        } else if ($damageInspection->shipment_id) {
            $shipment = Shipment::findOrFail($damageInspection->shipment_id);
            $salesOrderIds = json_decode($shipment->sales_order_ids, true);
            $salesOrderId = $salesOrderIds[0] ?? null;
        }
        
        return QualityException::create([
            'exception_number' => $this->generateExceptionNumber(),
            'checkpoint_id' => null,
            'packed_carton_id' => $damageInspection->packed_carton_id,
            'shipment_id' => $damageInspection->shipment_id,
            'sales_order_id' => $salesOrderId,
            'exception_type' => 'damage_inspection',
            'exception_severity' => $damageInspection->damage_severity,
            'exception_status' => 'open',
            'exception_details' => json_encode([
                'inspection_type' => $damageInspection->inspection_type,
                'damage_location' => $damageInspection->damage_location,
                'damage_description' => $damageInspection->damage_description
            ]),
            'reported_by' => $damageInspection->inspector_id,
            'created_at' => now()
        ]);
    }
    
    /**
     * Resolve a quality exception
     *
     * @param int $exceptionId
     * @param array $data
     * @return \App\Models\Outbound\QualityException
     */
    public function resolveQualityException($exceptionId, array $data)
    {
        $exception = QualityException::findOrFail($exceptionId);
        
        if ($exception->exception_status === 'resolved') {
            throw new Exception('Exception is already resolved');
        }
        
        $exception->update([
            'exception_status' => 'resolved',
            'resolution_notes' => $data['resolution_notes'],
            'corrective_actions' => $data['corrective_actions'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $exception->assigned_to,
            'resolved_at' => now()
        ]);
        
        // Update the status of the related entity if needed
        if ($exception->packed_carton_id) {
            $packedCarton = PackedCarton::findOrFail($exception->packed_carton_id);
            
            // Check if all exceptions for this carton are resolved
            $openExceptions = QualityException::where('packed_carton_id', $exception->packed_carton_id)
                ->where('exception_status', '!=', 'resolved')
                ->count();
            
            if ($openExceptions === 0) {
                $packedCarton->update(['carton_status' => 'quality_passed']);
            }
        } else if ($exception->shipment_id) {
            $shipment = Shipment::findOrFail($exception->shipment_id);
            
            // Check if all exceptions for this shipment are resolved
            $openExceptions = QualityException::where('shipment_id', $exception->shipment_id)
                ->where('exception_status', '!=', 'resolved')
                ->count();
            
            if ($openExceptions === 0) {
                $shipment->update(['shipment_status' => 'ready']);
            }
        }
        
        return $exception;
    }
    
    /**
     * Get quality metrics
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getQualityMetrics($startDate = null, $endDate = null)
    {
        // Quality checks metrics
        $checksQuery = OutboundQualityCheck::query();
        
        if ($startDate) {
            $checksQuery->whereDate('inspected_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $checksQuery->whereDate('inspected_at', '<=', $endDate);
        }
        
        $checks = $checksQuery->get();
        
        $totalChecks = $checks->count();
        $passedChecks = $checks->where('overall_result', 'passed')->count();
        $failedChecks = $checks->where('overall_result', 'failed')->count();
        $conditionalChecks = $checks->where('overall_result', 'conditional')->count();
        
        $passRate = $totalChecks > 0 ? ($passedChecks / $totalChecks) * 100 : 0;
        
        // Quality exceptions metrics
        $exceptionsQuery = QualityException::query();
        
        if ($startDate) {
            $exceptionsQuery->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $exceptionsQuery->whereDate('created_at', '<=', $endDate);
        }
        
        $exceptions = $exceptionsQuery->get();
        
        $totalExceptions = $exceptions->count();
        $openExceptions = $exceptions->where('exception_status', 'open')->count();
        $inProgressExceptions = $exceptions->where('exception_status', 'in_progress')->count();
        $resolvedExceptions = $exceptions->where('exception_status', 'resolved')->count();
        
        // Calculate average resolution time
        $avgResolutionTime = 0;
        $resolvedCount = 0;
        
        foreach ($exceptions as $exception) {
            if ($exception->exception_status === 'resolved' && $exception->resolved_at) {
                $resolutionTime = $exception->created_at->diffInHours($exception->resolved_at);
                $avgResolutionTime += $resolutionTime;
                $resolvedCount++;
            }
        }
        
        $avgResolutionTime = $resolvedCount > 0 ? $avgResolutionTime / $resolvedCount : 0;
        
        // Exceptions by type
        $exceptionsByType = [];
        foreach ($exceptions as $exception) {
            if (!isset($exceptionsByType[$exception->exception_type])) {
                $exceptionsByType[$exception->exception_type] = 0;
            }
            
            $exceptionsByType[$exception->exception_type]++;
        }
        
        // Exceptions by severity
        $exceptionsBySeverity = [];
        foreach ($exceptions as $exception) {
            if (!isset($exceptionsBySeverity[$exception->exception_severity])) {
                $exceptionsBySeverity[$exception->exception_severity] = 0;
            }
            
            $exceptionsBySeverity[$exception->exception_severity]++;
        }
        
        // Top failure reasons
        $failureReasons = [];
        foreach ($exceptions as $exception) {
            $details = json_decode($exception->exception_details, true);
            $reason = '';
            
            if ($exception->exception_type === 'quality_check_failure' && isset($details['criterion_name'])) {
                $reason = $details['criterion_name'];
            } else if ($exception->exception_type === 'weight_verification') {
                $reason = 'Weight discrepancy';
            } else if ($exception->exception_type === 'dimension_verification') {
                $reason = 'Dimension discrepancy';
            } else if ($exception->exception_type === 'damage_inspection' && isset($details['damage_description'])) {
                $reason = $details['damage_description'];
            } else {
                $reason = $exception->exception_type;
            }
            
            if (!isset($failureReasons[$reason])) {
                $failureReasons[$reason] = 0;
            }
            
            $failureReasons[$reason]++;
        }
        
        // Sort failure reasons by count
        arsort($failureReasons);
        
        // Convert to array of objects
        $topFailureReasons = [];
        foreach (array_slice($failureReasons, 0, 5) as $reason => $count) {
            $topFailureReasons[] = [
                'reason' => $reason,
                'count' => $count
            ];
        }
        
        return [
            'total_checks' => $totalChecks,
            'passed_checks' => $passedChecks,
            'failed_checks' => $failedChecks,
            'conditional_checks' => $conditionalChecks,
            'pass_rate' => $passRate,
            'total_exceptions' => $totalExceptions,
            'open_exceptions' => $openExceptions,
            'in_progress_exceptions' => $inProgressExceptions,
            'resolved_exceptions' => $resolvedExceptions,
            'average_resolution_time' => $avgResolutionTime,
            'exceptions_by_type' => $exceptionsByType,
            'exceptions_by_severity' => $exceptionsBySeverity,
            'top_failure_reasons' => $topFailureReasons
        ];
    }
}