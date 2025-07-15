<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SpaceUtilization\WarehouseZone;
use App\Models\SpaceUtilization\WarehouseAisle;
use App\Models\SpaceUtilization\SpaceUtilizationSnapshot;
use App\Models\SpaceUtilization\CapacityTracking;
use App\Models\SpaceUtilization\AisleEfficiencyMetric;
use App\Models\SpaceUtilization\HeatMapData;
use App\Models\Visualization\WarehouseFloorPlan;
use App\Models\Visualization\WarehouseEquipment;
use App\Models\Visualization\EquipmentMovement;
use App\Models\Reporting\ReportTemplate;
use App\Models\Dashboard\WidgetLibrary;

class Phase2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedWarehouseZones();
        $this->seedWarehouseAisles();
        $this->seedSpaceUtilizationData();
        $this->seedWarehouseEquipment();
        $this->seedFloorPlans();
        $this->seedReportTemplates();
        $this->seedWidgetLibrary();
    }

    private function seedWarehouseZones()
    {
        $zones = [
            [
                'name' => 'Receiving Zone A',
                'code' => 'RCV-A',
                'type' => 'receiving',
                'length' => 50.00,
                'width' => 30.00,
                'height' => 8.00,
                'total_area' => 1500.00,
                'total_volume' => 12000.00,
                'usable_area' => 1400.00,
                'usable_volume' => 11200.00,
                'max_capacity' => 200,
                'coordinates' => ['x' => 10, 'y' => 10],
                'boundaries' => [
                    ['x' => 10, 'y' => 10],
                    ['x' => 60, 'y' => 10],
                    ['x' => 60, 'y' => 40],
                    ['x' => 10, 'y' => 40]
                ],
                'status' => 'active',
                'description' => 'Primary receiving area for incoming shipments'
            ],
            [
                'name' => 'Storage Zone B',
                'code' => 'STG-B',
                'type' => 'storage',
                'length' => 80.00,
                'width' => 60.00,
                'height' => 12.00,
                'total_area' => 4800.00,
                'total_volume' => 57600.00,
                'usable_area' => 4500.00,
                'usable_volume' => 54000.00,
                'max_capacity' => 1000,
                'coordinates' => ['x' => 70, 'y' => 10],
                'boundaries' => [
                    ['x' => 70, 'y' => 10],
                    ['x' => 150, 'y' => 10],
                    ['x' => 150, 'y' => 70],
                    ['x' => 70, 'y' => 70]
                ],
                'status' => 'active',
                'description' => 'Main storage area for inventory'
            ],
            [
                'name' => 'Picking Zone C',
                'code' => 'PCK-C',
                'type' => 'picking',
                'length' => 40.00,
                'width' => 25.00,
                'height' => 6.00,
                'total_area' => 1000.00,
                'total_volume' => 6000.00,
                'usable_area' => 950.00,
                'usable_volume' => 5700.00,
                'max_capacity' => 300,
                'coordinates' => ['x' => 160, 'y' => 10],
                'boundaries' => [
                    ['x' => 160, 'y' => 10],
                    ['x' => 200, 'y' => 10],
                    ['x' => 200, 'y' => 35],
                    ['x' => 160, 'y' => 35]
                ],
                'status' => 'active',
                'description' => 'High-frequency picking area'
            ],
            [
                'name' => 'Shipping Zone D',
                'code' => 'SHP-D',
                'type' => 'shipping',
                'length' => 45.00,
                'width' => 20.00,
                'height' => 7.00,
                'total_area' => 900.00,
                'total_volume' => 6300.00,
                'usable_area' => 850.00,
                'usable_volume' => 5950.00,
                'max_capacity' => 150,
                'coordinates' => ['x' => 210, 'y' => 10],
                'boundaries' => [
                    ['x' => 210, 'y' => 10],
                    ['x' => 255, 'y' => 10],
                    ['x' => 255, 'y' => 30],
                    ['x' => 210, 'y' => 30]
                ],
                'status' => 'active',
                'description' => 'Outbound shipping and staging area'
            ]
        ];

        foreach ($zones as $zoneData) {
            $zone = WarehouseZone::create($zoneData);
            $zone->updateCalculatedFields();
        }
    }

    private function seedWarehouseAisles()
    {
        $zones = WarehouseZone::all();
        
        foreach ($zones as $zone) {
            $aisleCount = $zone->type === 'storage' ? 8 : 4;
            
            for ($i = 1; $i <= $aisleCount; $i++) {
                WarehouseAisle::create([
                    'zone_id' => $zone->id,
                    'name' => "Aisle {$zone->code}-{$i}",
                    'code' => "{$zone->code}-A{$i}",
                    'length' => $zone->length / 2,
                    'width' => 3.00,
                    'height' => $zone->height,
                    'location_count' => rand(20, 50),
                    'occupied_locations' => rand(10, 40),
                    'coordinates' => [
                        'x' => $zone->coordinates['x'] + ($i * 5),
                        'y' => $zone->coordinates['y'] + 5
                    ],
                    'status' => 'active'
                ]);
            }
        }

        // Update utilization percentages
        WarehouseAisle::all()->each(function ($aisle) {
            $aisle->updateUtilization();
        });
    }

    private function seedSpaceUtilizationData()
    {
        $zones = WarehouseZone::all();
        
        foreach ($zones as $zone) {
            // Create utilization snapshots for the last 30 days
            for ($day = 30; $day >= 0; $day--) {
                $snapshotTime = now()->subDays($day)->setHour(rand(8, 18));
                $utilizationPercentage = rand(60, 95);
                $occupiedLocations = round($zone->max_capacity * ($utilizationPercentage / 100));
                
                SpaceUtilizationSnapshot::create([
                    'zone_id' => $zone->id,
                    'snapshot_time' => $snapshotTime,
                    'occupied_area' => $zone->usable_area * ($utilizationPercentage / 100),
                    'occupied_volume' => $zone->usable_volume * ($utilizationPercentage / 100),
                    'occupied_locations' => $occupiedLocations,
                    'total_locations' => $zone->max_capacity,
                    'utilization_percentage' => $utilizationPercentage,
                    'density_per_sqm' => $occupiedLocations / $zone->usable_area,
                    'density_per_cbm' => $occupiedLocations / $zone->usable_volume,
                    'item_count' => $occupiedLocations * rand(1, 3),
                    'weight_total' => $occupiedLocations * rand(10, 100),
                    'utilization_by_category' => [
                        'electronics' => rand(20, 40),
                        'clothing' => rand(15, 35),
                        'books' => rand(10, 25),
                        'other' => rand(5, 20)
                    ]
                ]);
            }

            // Create capacity tracking for the last 7 days
            for ($day = 7; $day >= 0; $day--) {
                $trackingDate = now()->subDays($day)->toDateString();
                $currentOccupancy = rand(100, $zone->max_capacity - 50);
                $reservedCapacity = rand(10, 50);
                
                CapacityTracking::create([
                    'zone_id' => $zone->id,
                    'tracking_date' => $trackingDate,
                    'max_capacity' => $zone->max_capacity,
                    'current_occupancy' => $currentOccupancy,
                    'reserved_capacity' => $reservedCapacity,
                    'available_capacity' => $zone->max_capacity - $currentOccupancy - $reservedCapacity,
                    'capacity_utilization' => ($currentOccupancy / $zone->max_capacity) * 100,
                    'peak_utilization' => rand(85, 98),
                    'peak_time' => now()->setHour(rand(10, 16))->setMinute(rand(0, 59)),
                    'hourly_utilization' => $this->generateHourlyUtilization(),
                    'capacity_forecast' => $this->generateCapacityForecast($currentOccupancy)
                ]);
            }
        }

        // Create aisle efficiency metrics
        $aisles = WarehouseAisle::all();
        foreach ($aisles as $aisle) {
            for ($day = 7; $day >= 0; $day--) {
                AisleEfficiencyMetric::create([
                    'aisle_id' => $aisle->id,
                    'metric_date' => now()->subDays($day)->toDateString(),
                    'pick_density' => rand(5, 15) + (rand(0, 99) / 100),
                    'travel_distance' => rand(20, 80) + (rand(0, 99) / 100),
                    'pick_time_avg' => rand(30, 90) + (rand(0, 99) / 100),
                    'congestion_incidents' => rand(0, 8),
                    'accessibility_score' => rand(70, 95) + (rand(0, 99) / 100),
                    'efficiency_score' => rand(65, 90) + (rand(0, 99) / 100),
                    'peak_hours' => [rand(9, 11), rand(13, 15), rand(16, 18)],
                    'bottleneck_locations' => [
                        ['location' => 'A1-15', 'severity' => 'medium'],
                        ['location' => 'A1-23', 'severity' => 'low']
                    ]
                ]);
            }
        }

        // Create heat map data
        $this->seedHeatMapData();
    }

    private function seedHeatMapData()
    {
        $zones = WarehouseZone::all();
        $mapTypes = ['utilization', 'activity', 'efficiency', 'temperature'];
        
        foreach ($zones as $zone) {
            foreach ($mapTypes as $mapType) {
                // Generate heat map points for the last 24 hours
                for ($hour = 24; $hour >= 0; $hour--) {
                    $dataTime = now()->subHours($hour);
                    
                    // Generate random heat map points within the zone
                    for ($point = 0; $point < 20; $point++) {
                        $intensity = rand(0, 100) / 100;
                        $intensityLevel = $this->getIntensityLevel($intensity);
                        
                        HeatMapData::create([
                            'map_type' => $mapType,
                            'zone_id' => $zone->id,
                            'data_time' => $dataTime,
                            'x_coordinate' => $zone->coordinates['x'] + rand(0, $zone->length),
                            'y_coordinate' => $zone->coordinates['y'] + rand(0, $zone->width),
                            'intensity' => $intensity,
                            'intensity_level' => $intensityLevel,
                            'metadata' => [
                                'temperature' => $mapType === 'temperature' ? rand(18, 25) : null,
                                'activity_count' => $mapType === 'activity' ? rand(1, 20) : null,
                                'utilization_rate' => $mapType === 'utilization' ? rand(60, 95) : null
                            ]
                        ]);
                    }
                }
            }
        }
    }

    private function seedWarehouseEquipment()
    {
        $zones = WarehouseZone::all();
        $equipmentTypes = ['forklift', 'conveyor', 'scanner', 'robot', 'crane'];
        
        foreach ($equipmentTypes as $type) {
            for ($i = 1; $i <= 3; $i++) {
                $zone = $zones->random();
                
                WarehouseEquipment::create([
                    'name' => ucfirst($type) . " {$i}",
                    'code' => strtoupper(substr($type, 0, 3)) . "-{$i}",
                    'type' => $type,
                    'status' => rand(0, 10) > 1 ? 'active' : 'maintenance',
                    'current_x' => $zone->coordinates['x'] + rand(0, $zone->length),
                    'current_y' => $zone->coordinates['y'] + rand(0, $zone->width),
                    'current_z' => 0,
                    'current_zone_id' => $zone->id,
                    'specifications' => [
                        'max_weight' => rand(1000, 5000),
                        'max_height' => rand(3, 8),
                        'speed' => rand(5, 20),
                        'last_maintenance' => now()->subDays(rand(1, 25))->toDateString(),
                        'maintenance_interval_days' => 30
                    ],
                    'last_activity' => now()->subMinutes(rand(1, 60)),
                    'battery_level' => in_array($type, ['robot', 'scanner']) ? rand(20, 100) : null,
                    'sensor_data' => [
                        'temperature' => rand(20, 30),
                        'vibration' => rand(0, 5),
                        'operating_hours' => rand(100, 2000)
                    ]
                ]);
            }
        }
    }

    private function seedFloorPlans()
    {
        WarehouseFloorPlan::create([
            'name' => 'Main Warehouse Floor Plan',
            'version' => '1.0',
            'total_length' => 300.00,
            'total_width' => 200.00,
            'total_height' => 15.00,
            'scale_unit' => 'meters',
            'layout_data' => [
                'grid_size' => 10,
                'zones' => WarehouseZone::all()->map(function($zone) {
                    return [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'coordinates' => $zone->coordinates,
                        'boundaries' => $zone->boundaries,
                        'type' => $zone->type
                    ];
                })->toArray()
            ],
            'grid_settings' => [
                'show_grid' => true,
                'grid_size' => 10,
                'grid_color' => '#cccccc',
                'scale_ratio' => 1
            ],
            'is_active' => true,
            'description' => 'Primary floor plan showing all warehouse zones and equipment'
        ]);
    }

    private function seedReportTemplates()
    {
        $templates = [
            [
                'name' => 'Space Utilization Report',
                'code' => 'space_utilization',
                'category' => 'operational',
                'description' => 'Comprehensive space utilization analysis across all warehouse zones',
                'data_sources' => ['warehouse_zones', 'space_utilization_snapshots', 'capacity_tracking'],
                'fields_config' => [
                    ['field' => 'zone_name', 'label' => 'Zone Name', 'type' => 'string', 'required' => true],
                    ['field' => 'utilization_percentage', 'label' => 'Utilization %', 'type' => 'decimal', 'required' => true],
                    ['field' => 'available_capacity', 'label' => 'Available Capacity', 'type' => 'integer', 'required' => false],
                    ['field' => 'efficiency_score', 'label' => 'Efficiency Score', 'type' => 'decimal', 'required' => false]
                ],
                'filters_config' => [
                    ['field' => 'date_range', 'label' => 'Date Range', 'type' => 'date_range'],
                    ['field' => 'zone_type', 'label' => 'Zone Type', 'type' => 'dropdown', 'options' => ['storage', 'picking', 'receiving', 'shipping']],
                    ['field' => 'utilization_threshold', 'label' => 'Utilization Threshold', 'type' => 'number_range']
                ],
                'output_formats' => 'pdf,excel,csv',
                'is_public' => true,
                'is_active' => true
            ],
            [
                'name' => 'Equipment Performance Report',
                'code' => 'equipment_performance',
                'category' => 'operational',
                'description' => 'Equipment utilization and performance metrics',
                'data_sources' => ['warehouse_equipment', 'equipment_movements'],
                'fields_config' => [
                    ['field' => 'equipment_name', 'label' => 'Equipment Name', 'type' => 'string', 'required' => true],
                    ['field' => 'equipment_type', 'label' => 'Type', 'type' => 'string', 'required' => true],
                    ['field' => 'utilization_rate', 'label' => 'Utilization Rate %', 'type' => 'decimal', 'required' => true],
                    ['field' => 'distance_traveled', 'label' => 'Distance Traveled', 'type' => 'decimal', 'required' => false],
                    ['field' => 'maintenance_due', 'label' => 'Maintenance Due', 'type' => 'date', 'required' => false]
                ],
                'filters_config' => [
                    ['field' => 'equipment_type', 'label' => 'Equipment Type', 'type' => 'multi_select', 'options' => ['forklift', 'conveyor', 'scanner', 'robot', 'crane']],
                    ['field' => 'status', 'label' => 'Status', 'type' => 'dropdown', 'options' => ['active', 'inactive', 'maintenance']]
                ],
                'output_formats' => 'pdf,excel',
                'is_public' => true,
                'is_active' => true
            ],
            [
                'name' => 'Warehouse Efficiency Dashboard',
                'code' => 'warehouse_efficiency',
                'category' => 'performance',
                'description' => 'Overall warehouse efficiency metrics and KPIs',
                'data_sources' => ['warehouse_zones', 'aisle_efficiency_metrics', 'capacity_tracking'],
                'fields_config' => [
                    ['field' => 'overall_efficiency', 'label' => 'Overall Efficiency %', 'type' => 'decimal', 'required' => true],
                    ['field' => 'space_utilization', 'label' => 'Space Utilization %', 'type' => 'decimal', 'required' => true],
                    ['field' => 'equipment_uptime', 'label' => 'Equipment Uptime %', 'type' => 'decimal', 'required' => true],
                    ['field' => 'throughput', 'label' => 'Throughput', 'type' => 'integer', 'required' => false]
                ],
                'chart_config' => [
                    'default_chart' => 'line_chart',
                    'available_charts' => ['line_chart', 'bar_chart', 'gauge']
                ],
                'output_formats' => 'pdf,excel,csv',
                'is_public' => false,
                'is_active' => true
            ]
        ];

        foreach ($templates as $templateData) {
            ReportTemplate::create($templateData);
        }
    }

    private function seedWidgetLibrary()
    {
        $widgets = [
            [
                'name' => 'Space Utilization Gauge',
                'code' => 'space_utilization_gauge',
                'category' => 'metric',
                'widget_type' => 'gauge',
                'description' => 'Displays current space utilization as a gauge chart',
                'default_config' => [
                    'min_value' => 0,
                    'max_value' => 100,
                    'unit' => '%',
                    'color_ranges' => [
                        ['min' => 0, 'max' => 60, 'color' => '#28a745'],
                        ['min' => 60, 'max' => 85, 'color' => '#ffc107'],
                        ['min' => 85, 'max' => 100, 'color' => '#dc3545']
                    ]
                ],
                'config_schema' => [
                    'title' => ['type' => 'string', 'required' => true],
                    'zone_id' => ['type' => 'integer', 'required' => false],
                    'refresh_interval' => ['type' => 'integer', 'min' => 30, 'max' => 3600, 'required' => false]
                ],
                'data_requirements' => ['utilization_percentage'],
                'component_path' => 'widgets/SpaceUtilizationGauge',
                'supported_data_sources' => ['space_utilization_snapshots'],
                'customization_options' => [
                    'colors' => ['primary_color', 'warning_color', 'danger_color'],
                    'display' => ['show_value', 'show_label', 'animation_speed']
                ],
                'is_public' => true,
                'is_active' => true
            ],
            [
                'name' => 'Equipment Status Map',
                'code' => 'equipment_status_map',
                'category' => 'map',
                'widget_type' => 'heat_map',
                'description' => 'Real-time equipment positions and status on warehouse floor plan',
                'default_config' => [
                    'show_equipment_labels' => true,
                    'show_movement_trails' => false,
                    'auto_refresh' => true,
                    'zoom_level' => 1
                ],
                'config_schema' => [
                    'floor_plan_id' => ['type' => 'integer', 'required' => true],
                    'equipment_types' => ['type' => 'array', 'required' => false],
                    'show_offline' => ['type' => 'boolean', 'required' => false]
                ],
                'data_requirements' => ['equipment_positions', 'equipment_status'],
                'component_path' => 'widgets/EquipmentStatusMap',
                'supported_data_sources' => ['warehouse_equipment', 'equipment_movements'],
                'customization_options' => [
                    'display' => ['map_style', 'icon_size', 'trail_length'],
                    'filters' => ['equipment_types', 'status_filters']
                ],
                'is_public' => true,
                'is_active' => true
            ],
            [
                'name' => 'Capacity Trend Chart',
                'code' => 'capacity_trend_chart',
                'category' => 'chart',
                'widget_type' => 'line_chart',
                'description' => 'Shows capacity utilization trends over time',
                'default_config' => [
                    'time_period' => '7d',
                    'show_forecast' => true,
                    'chart_type' => 'line',
                    'show_data_points' => true
                ],
                'config_schema' => [
                    'zone_ids' => ['type' => 'array', 'required' => false],
                    'time_period' => ['type' => 'string', 'options' => ['1d', '7d', '30d'], 'required' => false],
                    'metric' => ['type' => 'string', 'options' => ['utilization', 'capacity', 'efficiency'], 'required' => false]
                ],
                'data_requirements' => ['capacity_data', 'time_series'],
                'component_path' => 'widgets/CapacityTrendChart',
                'supported_data_sources' => ['capacity_tracking'],
                'customization_options' => [
                    'chart' => ['line_color', 'fill_area', 'point_style'],
                    'axes' => ['x_axis_format', 'y_axis_format', 'grid_lines']
                ],
                'is_public' => true,
                'is_active' => true
            ]
        ];

        foreach ($widgets as $widgetData) {
            WidgetLibrary::create($widgetData);
        }
    }

    private function generateHourlyUtilization()
    {
        $utilization = [];
        for ($hour = 0; $hour < 24; $hour++) {
            // Simulate higher utilization during business hours
            if ($hour >= 8 && $hour <= 18) {
                $utilization[$hour] = rand(70, 95);
            } else {
                $utilization[$hour] = rand(20, 50);
            }
        }
        return $utilization;
    }

    private function generateCapacityForecast($currentOccupancy)
    {
        return [
            'tomorrow' => $currentOccupancy + rand(-20, 30),
            'next_week' => $currentOccupancy + rand(-50, 80),
            'next_month' => $currentOccupancy + rand(-100, 150)
        ];
    }

    private function getIntensityLevel($intensity)
    {
        if ($intensity >= 0.8) return 'critical';
        if ($intensity >= 0.6) return 'high';
        if ($intensity >= 0.4) return 'medium';
        return 'low';
    }
}
