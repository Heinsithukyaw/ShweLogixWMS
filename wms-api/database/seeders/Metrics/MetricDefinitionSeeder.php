<?php

namespace Database\Seeders\Metrics;

use App\Models\Metrics\MetricDefinition;
use Illuminate\Database\Seeder;

class MetricDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $metricDefinitions = [
            // Inbound Metrics
            [
                'name' => 'Receiving Accuracy',
                'code' => 'RECV_ACCURACY',
                'description' => 'Percentage of items received correctly without discrepancies',
                'category' => 'inbound',
                'unit_of_measure' => 'percentage',
                'calculation_formula' => '(Correct Receipts / Total Receipts) * 100',
                'data_source' => 'receipts',
                'frequency' => 'daily',
                'is_kpi' => true,
                'target_value' => 99.5,
                'threshold_warning' => 98.0,
                'threshold_critical' => 95.0,
                'higher_is_better' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Dock to Stock Time',
                'code' => 'DOCK_TO_STOCK',
                'description' => 'Average time from receipt to putaway completion',
                'category' => 'inbound',
                'unit_of_measure' => 'hours',
                'calculation_formula' => 'AVG(Putaway Completion Time - Receipt Time)',
                'data_source' => 'receipts,putaway_tasks',
                'frequency' => 'daily',
                'is_kpi' => true,
                'target_value' => 4.0,
                'threshold_warning' => 6.0,
                'threshold_critical' => 8.0,
                'higher_is_better' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Putaway Accuracy',
                'code' => 'PUTAWAY_ACCURACY',
                'description' => 'Percentage of items put away to correct locations',
                'category' => 'inbound',
                'unit_of_measure' => 'percentage',
                'calculation_formula' => '(Correct Putaways / Total Putaways) * 100',
                'data_source' => 'putaway_tasks',
                'frequency' => 'daily',
                'is_kpi' => true,
                'target_value' => 99.0,
                'threshold_warning' => 97.0,
                'threshold_critical' => 95.0,
                'higher_is_better' => true,
                'is_active' => true,
            ],

            // Inventory Metrics
            [
                'name' => 'Inventory Accuracy',
                'code' => 'INV_ACCURACY',
                'description' => 'Percentage of inventory records that match physical count',
                'category' => 'inventory',
                'unit_of_measure' => 'percentage',
                'calculation_formula' => '(Accurate Records / Total Records) * 100',
                'data_source' => 'cycle_counts',
                'frequency' => 'daily',
                'is_kpi' => true,
                'target_value' => 99.5,
                'threshold_warning' => 98.0,
                'threshold_critical' => 95.0,
                'higher_is_better' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Inventory Turnover',
                'code' => 'INV_TURNOVER',
                'description' => 'Number of times inventory is sold and replaced over a period',
                'category' => 'inventory',
                'unit_of_measure' => 'times',
                'calculation_formula' => 'Cost of Goods Sold / Average Inventory Value',
                'data_source' => 'inventory,shipments',
                'frequency' => 'monthly',
                'is_kpi' => true,
                'target_value' => 12.0,
                'threshold_warning' => 8.0,
                'threshold_critical' => 6.0,
                'higher_is_better' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Storage Utilization',
                'code' => 'STORAGE_UTIL',
                'description' => 'Percentage of available storage space being used',
                'category' => 'inventory',
                'unit_of_measure' => 'percentage',
                'calculation_formula' => '(Used Space / Total Available Space) * 100',
                'data_source' => 'locations,inventory',
                'frequency' => 'daily',
                'is_kpi' => true,
                'target_value' => 85.0,
                'threshold_warning' => 95.0,
                'threshold_critical' => 98.0,
                'higher_is_better' => false,
                'is_active' => true,
            ],

            // Outbound Metrics
            [
                'name' => 'Order Accuracy',
                'code' => 'ORDER_ACCURACY',
                'description' => 'Percentage of orders shipped without errors',
                'category' => 'outbound',
                'unit_of_measure' => 'percentage',
                'calculation_formula' => '(Error-free Orders / Total Orders) * 100',
                'data_source' => 'orders,shipments',
                'frequency' => 'daily',
                'is_kpi' => true,
                'target_value' => 99.5,
                'threshold_warning' => 98.0,
                'threshold_critical' => 95.0,
                'higher_is_better' => true,
                'is_active' => true,
            ],
            [
                'name' => 'On-Time Shipping',
                'code' => 'ONTIME_SHIP',
                'description' => 'Percentage of orders shipped on or before promised date',
                'category' => 'outbound',
                'unit_of_measure' => 'percentage',
                'calculation_formula' => '(On-time Shipments / Total Shipments) * 100',
                'data_source' => 'shipments',
                'frequency' => 'daily',
                'is_kpi' => true,
                'target_value' => 98.0,
                'threshold_warning' => 95.0,
                'threshold_critical' => 90.0,
                'higher_is_better' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Picking Accuracy',
                'code' => 'PICK_ACCURACY',
                'description' => 'Percentage of items picked correctly',
                'category' => 'outbound',
                'unit_of_measure' => 'percentage',
                'calculation_formula' => '(Correct Picks / Total Picks) * 100',
                'data_source' => 'picking_tasks',
                'frequency' => 'daily',
                'is_kpi' => true,
                'target_value' => 99.5,
                'threshold_warning' => 98.0,
                'threshold_critical' => 95.0,
                'higher_is_better' => true,
                'is_active' => true,
            ],

            // Performance Metrics
            [
                'name' => 'Labor Efficiency',
                'code' => 'LABOR_EFF',
                'description' => 'Ratio of standard hours to actual hours worked',
                'category' => 'performance',
                'unit_of_measure' => 'percentage',
                'calculation_formula' => '(Standard Hours / Actual Hours) * 100',
                'data_source' => 'labor_tracking',
                'frequency' => 'daily',
                'is_kpi' => true,
                'target_value' => 95.0,
                'threshold_warning' => 85.0,
                'threshold_critical' => 75.0,
                'higher_is_better' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Cost per Order',
                'code' => 'COST_PER_ORDER',
                'description' => 'Total warehouse cost divided by number of orders processed',
                'category' => 'performance',
                'unit_of_measure' => 'currency',
                'calculation_formula' => 'Total Warehouse Costs / Total Orders',
                'data_source' => 'costs,orders',
                'frequency' => 'monthly',
                'is_kpi' => true,
                'target_value' => 5.0,
                'threshold_warning' => 7.0,
                'threshold_critical' => 10.0,
                'higher_is_better' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Equipment Utilization',
                'code' => 'EQUIP_UTIL',
                'description' => 'Percentage of time equipment is actively being used',
                'category' => 'performance',
                'unit_of_measure' => 'percentage',
                'calculation_formula' => '(Active Time / Total Available Time) * 100',
                'data_source' => 'equipment_tracking',
                'frequency' => 'daily',
                'is_kpi' => true,
                'target_value' => 80.0,
                'threshold_warning' => 70.0,
                'threshold_critical' => 60.0,
                'higher_is_better' => true,
                'is_active' => true,
            ],
        ];

        foreach ($metricDefinitions as $definition) {
            MetricDefinition::create($definition);
        }
    }
}