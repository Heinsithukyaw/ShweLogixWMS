<?php

namespace App\Models\LayoutSimulation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Warehouse;
use App\Models\User;

class LayoutSimulation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'layout_simulations';

    protected $fillable = [
        'name',
        'description',
        'warehouse_id',
        'base_layout_id',
        'layout_data',
        'simulation_parameters',
        'status',
        'simulation_results',
        'kpi_predictions',
        'performance_metrics',
        'created_by',
        'last_simulated_at'
    ];

    protected $casts = [
        'layout_data' => 'json',
        'simulation_parameters' => 'json',
        'simulation_results' => 'json',
        'kpi_predictions' => 'json',
        'performance_metrics' => 'json',
        'last_simulated_at' => 'datetime'
    ];

    // Relationships
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function baseLayout()
    {
        return $this->belongsTo(LayoutSimulation::class, 'base_layout_id');
    }

    public function derivedLayouts()
    {
        return $this->hasMany(LayoutSimulation::class, 'base_layout_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scenarios()
    {
        return $this->hasMany(SimulationScenario::class);
    }

    public function elements()
    {
        return $this->hasMany(LayoutElement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // Methods
    public function runSimulation(array $parameters = [])
    {
        $this->status = 'running';
        $this->simulation_parameters = array_merge($this->simulation_parameters ?? [], $parameters);
        $this->save();

        try {
            // Run the simulation
            $results = $this->executeSimulation();
            
            $this->simulation_results = $results;
            $this->kpi_predictions = $this->calculateKPIPredictions($results);
            $this->performance_metrics = $this->calculatePerformanceMetrics($results);
            $this->status = 'completed';
            $this->last_simulated_at = now();
            
            $this->save();

            return $results;

        } catch (\Exception $e) {
            $this->status = 'failed';
            $this->simulation_results = ['error' => $e->getMessage()];
            $this->save();
            
            throw $e;
        }
    }

    private function executeSimulation()
    {
        $layoutData = $this->layout_data;
        $parameters = $this->simulation_parameters;

        // Simulate material flow
        $materialFlow = $this->simulateMaterialFlow($layoutData, $parameters);
        
        // Simulate labor efficiency
        $laborEfficiency = $this->simulateLaborEfficiency($layoutData, $parameters);
        
        // Simulate space utilization
        $spaceUtilization = $this->simulateSpaceUtilization($layoutData, $parameters);
        
        // Simulate throughput
        $throughput = $this->simulateThroughput($layoutData, $parameters);

        return [
            'material_flow' => $materialFlow,
            'labor_efficiency' => $laborEfficiency,
            'space_utilization' => $spaceUtilization,
            'throughput' => $throughput,
            'simulation_timestamp' => now()->toISOString()
        ];
    }

    private function simulateMaterialFlow($layoutData, $parameters)
    {
        // Calculate distances between key areas
        $distances = $this->calculateDistances($layoutData);
        
        // Simulate order picking paths
        $pickingPaths = $this->simulatePickingPaths($layoutData, $parameters);
        
        // Calculate flow efficiency
        $flowEfficiency = $this->calculateFlowEfficiency($distances, $pickingPaths);

        return [
            'average_travel_distance' => $distances['average'],
            'total_travel_time' => $pickingPaths['total_time'],
            'flow_efficiency_score' => $flowEfficiency,
            'bottlenecks' => $this->identifyBottlenecks($layoutData, $pickingPaths)
        ];
    }

    private function simulateLaborEfficiency($layoutData, $parameters)
    {
        $workstations = collect($layoutData['elements'])->where('type', 'workstation');
        $laborHours = $parameters['daily_labor_hours'] ?? 480; // 8 hours * 60 minutes
        
        $efficiency = [
            'picking_efficiency' => $this->calculatePickingEfficiency($layoutData, $parameters),
            'packing_efficiency' => $this->calculatePackingEfficiency($layoutData, $parameters),
            'putaway_efficiency' => $this->calculatePutawayEfficiency($layoutData, $parameters),
            'overall_efficiency' => 0
        ];

        $efficiency['overall_efficiency'] = array_sum($efficiency) / 3;

        return $efficiency;
    }

    private function simulateSpaceUtilization($layoutData, $parameters)
    {
        $totalArea = $layoutData['dimensions']['width'] * $layoutData['dimensions']['height'];
        $usedArea = 0;
        $storageArea = 0;

        foreach ($layoutData['elements'] as $element) {
            $elementArea = $element['width'] * $element['height'];
            $usedArea += $elementArea;
            
            if ($element['type'] === 'storage') {
                $storageArea += $elementArea;
            }
        }

        return [
            'total_area' => $totalArea,
            'used_area' => $usedArea,
            'storage_area' => $storageArea,
            'utilization_percentage' => ($usedArea / $totalArea) * 100,
            'storage_density' => ($storageArea / $totalArea) * 100,
            'aisle_percentage' => (($totalArea - $usedArea) / $totalArea) * 100
        ];
    }

    private function simulateThroughput($layoutData, $parameters)
    {
        $dailyOrders = $parameters['daily_orders'] ?? 100;
        $avgItemsPerOrder = $parameters['avg_items_per_order'] ?? 3;
        
        // Calculate theoretical throughput based on layout
        $pickingStations = collect($layoutData['elements'])->where('type', 'picking_station')->count();
        $packingStations = collect($layoutData['elements'])->where('type', 'packing_station')->count();
        
        $pickingCapacity = $pickingStations * 60; // items per hour
        $packingCapacity = $packingStations * 40; // orders per hour
        
        $bottleneckCapacity = min($pickingCapacity / $avgItemsPerOrder, $packingCapacity);
        
        return [
            'theoretical_daily_capacity' => $bottleneckCapacity * 8, // 8 hour day
            'current_daily_demand' => $dailyOrders,
            'capacity_utilization' => ($dailyOrders / ($bottleneckCapacity * 8)) * 100,
            'bottleneck_process' => $pickingCapacity < $packingCapacity ? 'picking' : 'packing'
        ];
    }

    private function calculateKPIPredictions($results)
    {
        return [
            'order_fulfillment_time' => $this->predictOrderFulfillmentTime($results),
            'labor_productivity' => $this->predictLaborProductivity($results),
            'space_efficiency' => $results['space_utilization']['utilization_percentage'],
            'throughput_capacity' => $results['throughput']['theoretical_daily_capacity'],
            'cost_per_order' => $this->predictCostPerOrder($results),
            'accuracy_rate' => $this->predictAccuracyRate($results)
        ];
    }

    private function calculatePerformanceMetrics($results)
    {
        return [
            'travel_time_reduction' => $this->calculateTravelTimeReduction($results),
            'space_optimization' => $results['space_utilization']['utilization_percentage'],
            'throughput_improvement' => $this->calculateThroughputImprovement($results),
            'labor_efficiency_gain' => $results['labor_efficiency']['overall_efficiency'],
            'cost_savings' => $this->calculateCostSavings($results)
        ];
    }

    // Helper methods for simulation calculations
    private function calculateDistances($layoutData)
    {
        // Simplified distance calculation
        return ['average' => 50]; // meters
    }

    private function simulatePickingPaths($layoutData, $parameters)
    {
        return ['total_time' => 120]; // minutes
    }

    private function calculateFlowEfficiency($distances, $pickingPaths)
    {
        return 85.5; // percentage
    }

    private function identifyBottlenecks($layoutData, $pickingPaths)
    {
        return ['main_aisle', 'packing_area'];
    }

    private function calculatePickingEfficiency($layoutData, $parameters)
    {
        return 78.5;
    }

    private function calculatePackingEfficiency($layoutData, $parameters)
    {
        return 82.3;
    }

    private function calculatePutawayEfficiency($layoutData, $parameters)
    {
        return 75.8;
    }

    private function predictOrderFulfillmentTime($results)
    {
        return 45; // minutes
    }

    private function predictLaborProductivity($results)
    {
        return 92.5; // percentage
    }

    private function predictCostPerOrder($results)
    {
        return 12.50; // dollars
    }

    private function predictAccuracyRate($results)
    {
        return 99.2; // percentage
    }

    private function calculateTravelTimeReduction($results)
    {
        return 15.5; // percentage improvement
    }

    private function calculateThroughputImprovement($results)
    {
        return 22.3; // percentage improvement
    }

    private function calculateCostSavings($results)
    {
        return 8500; // dollars per month
    }

    public function compareWithScenario(LayoutSimulation $otherSimulation)
    {
        $thisKPIs = $this->kpi_predictions;
        $otherKPIs = $otherSimulation->kpi_predictions;

        $comparison = [];
        foreach ($thisKPIs as $kpi => $value) {
            $otherValue = $otherKPIs[$kpi] ?? 0;
            $comparison[$kpi] = [
                'current' => $value,
                'comparison' => $otherValue,
                'difference' => $value - $otherValue,
                'percentage_change' => $otherValue > 0 ? (($value - $otherValue) / $otherValue) * 100 : 0
            ];
        }

        return $comparison;
    }

    public function exportLayout()
    {
        return [
            'name' => $this->name,
            'layout_data' => $this->layout_data,
            'kpi_predictions' => $this->kpi_predictions,
            'performance_metrics' => $this->performance_metrics,
            'exported_at' => now()->toISOString()
        ];
    }
}