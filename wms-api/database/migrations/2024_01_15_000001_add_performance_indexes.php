<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations for performance optimization
     */
    public function up(): void
    {
        // Sales Orders Performance Indexes
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->index(['warehouse_id', 'status', 'created_at'], 'idx_sales_orders_warehouse_status_created');
            $table->index(['customer_id', 'status'], 'idx_sales_orders_customer_status');
            $table->index(['priority_score', 'requested_ship_date'], 'idx_sales_orders_priority_ship_date');
            $table->index(['status', 'requested_ship_date'], 'idx_sales_orders_status_ship_date');
        });

        // Sales Order Items Performance Indexes
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->index(['product_id', 'location_id'], 'idx_sales_order_items_product_location');
            $table->index(['sales_order_id', 'status'], 'idx_sales_order_items_order_status');
            $table->index(['location_id', 'status'], 'idx_sales_order_items_location_status');
        });

        // Product Inventory Performance Indexes
        Schema::table('product_inventory', function (Blueprint $table) {
            $table->index(['warehouse_id', 'product_id', 'location_id'], 'idx_inventory_warehouse_product_location');
            $table->index(['product_id', 'quantity_available'], 'idx_inventory_product_available');
            $table->index(['location_id', 'quantity_available'], 'idx_inventory_location_available');
            $table->index(['warehouse_id', 'quantity_available'], 'idx_inventory_warehouse_available');
        });

        // Order Priorities Performance Indexes
        Schema::table('order_priorities', function (Blueprint $table) {
            $table->index(['warehouse_id', 'status', 'priority_score'], 'idx_order_priorities_warehouse_status_score');
            $table->index(['priority_level', 'created_at'], 'idx_order_priorities_level_created');
            $table->index(['effective_date', 'status'], 'idx_order_priorities_effective_status');
        });

        // Back Orders Performance Indexes
        Schema::table('back_orders', function (Blueprint $table) {
            $table->index(['warehouse_id', 'status', 'priority_level'], 'idx_back_orders_warehouse_status_priority');
            $table->index(['sales_order_id', 'status'], 'idx_back_orders_order_status');
            $table->index(['expected_availability_date', 'status'], 'idx_back_orders_availability_status');
            $table->index(['created_at', 'priority_level'], 'idx_back_orders_created_priority');
        });

        // Order Consolidations Performance Indexes
        Schema::table('order_consolidations', function (Blueprint $table) {
            $table->index(['warehouse_id', 'status', 'consolidation_type'], 'idx_consolidations_warehouse_status_type');
            $table->index(['status', 'expires_at'], 'idx_consolidations_status_expires');
            $table->index(['created_at', 'priority_level'], 'idx_consolidations_created_priority');
        });

        // Batch Picks Performance Indexes
        Schema::table('batch_picks', function (Blueprint $table) {
            $table->index(['warehouse_id', 'status', 'assigned_picker_id'], 'idx_batch_picks_warehouse_status_picker');
            $table->index(['pick_type', 'status'], 'idx_batch_picks_type_status');
            $table->index(['created_at', 'priority_score'], 'idx_batch_picks_created_priority');
            $table->index(['assigned_picker_id', 'status'], 'idx_batch_picks_picker_status');
        });

        // Zone Picks Performance Indexes
        Schema::table('zone_picks', function (Blueprint $table) {
            $table->index(['warehouse_id', 'zone_id', 'status'], 'idx_zone_picks_warehouse_zone_status');
            $table->index(['pick_strategy', 'status'], 'idx_zone_picks_strategy_status');
            $table->index(['created_at', 'priority_score'], 'idx_zone_picks_created_priority');
        });

        // Cluster Picks Performance Indexes
        Schema::table('cluster_picks', function (Blueprint $table) {
            $table->index(['warehouse_id', 'status', 'assigned_picker_id'], 'idx_cluster_picks_warehouse_status_picker');
            $table->index(['cluster_strategy', 'status'], 'idx_cluster_picks_strategy_status');
            $table->index(['created_at', 'priority_score'], 'idx_cluster_picks_created_priority');
        });

        // Pack Orders Performance Indexes
        Schema::table('pack_orders', function (Blueprint $table) {
            $table->index(['packing_station_id', 'status', 'packer_id'], 'idx_pack_orders_station_status_packer');
            $table->index(['sales_order_id', 'status'], 'idx_pack_orders_order_status');
            $table->index(['created_at', 'priority_level'], 'idx_pack_orders_created_priority');
            $table->index(['pack_deadline', 'status'], 'idx_pack_orders_deadline_status');
        });

        // Packing Stations Performance Indexes
        Schema::table('packing_stations', function (Blueprint $table) {
            $table->index(['warehouse_id', 'zone_id', 'is_active'], 'idx_packing_stations_warehouse_zone_active');
            $table->index(['status', 'is_active'], 'idx_packing_stations_status_active');
            $table->index(['station_type', 'is_active'], 'idx_packing_stations_type_active');
        });

        // Shipments Performance Indexes
        Schema::table('shipments', function (Blueprint $table) {
            $table->index(['warehouse_id', 'carrier_id', 'status'], 'idx_shipments_warehouse_carrier_status');
            $table->index(['ship_date', 'status'], 'idx_shipments_ship_date_status');
            $table->index(['tracking_number'], 'idx_shipments_tracking_number');
            $table->index(['created_at', 'shipment_type'], 'idx_shipments_created_type');
        });

        // Shipping Rates Performance Indexes
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->index(['carrier_id', 'service_type', 'origin_zone_id', 'destination_zone_id'], 'idx_shipping_rates_carrier_service_zones');
            $table->index(['effective_from', 'effective_to', 'is_active'], 'idx_shipping_rates_effective_active');
            $table->index(['rate_structure', 'is_active'], 'idx_shipping_rates_structure_active');
        });

        // Load Plans Performance Indexes
        Schema::table('load_plans', function (Blueprint $table) {
            $table->index(['warehouse_id', 'vehicle_id', 'status'], 'idx_load_plans_warehouse_vehicle_status');
            $table->index(['driver_id', 'status'], 'idx_load_plans_driver_status');
            $table->index(['planned_departure_time', 'status'], 'idx_load_plans_departure_status');
            $table->index(['load_type', 'status'], 'idx_load_plans_type_status');
        });

        // Carton Types Performance Indexes
        Schema::table('carton_types', function (Blueprint $table) {
            $table->index(['carton_category', 'is_active'], 'idx_carton_types_category_active');
            $table->index(['material_type', 'is_active'], 'idx_carton_types_material_active');
            $table->index(['max_weight', 'internal_volume'], 'idx_carton_types_weight_volume');
            $table->index(['cost_per_unit', 'is_active'], 'idx_carton_types_cost_active');
        });

        // Locations Performance Indexes
        Schema::table('locations', function (Blueprint $table) {
            $table->index(['warehouse_id', 'zone_id', 'is_active'], 'idx_locations_warehouse_zone_active');
            $table->index(['location_type', 'is_active'], 'idx_locations_type_active');
            $table->index(['aisle', 'bay', 'level'], 'idx_locations_aisle_bay_level');
        });

        // Zones Performance Indexes
        Schema::table('zones', function (Blueprint $table) {
            $table->index(['warehouse_id', 'zone_type', 'is_active'], 'idx_zones_warehouse_type_active');
            $table->index(['zone_type', 'is_active'], 'idx_zones_type_active');
        });

        // Products Performance Indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index(['sku'], 'idx_products_sku');
            $table->index(['is_active', 'created_at'], 'idx_products_active_created');
            $table->index(['category_id', 'is_active'], 'idx_products_category_active');
        });

        // Customers Performance Indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['email'], 'idx_customers_email');
            $table->index(['is_active', 'created_at'], 'idx_customers_active_created');
            $table->index(['priority_level', 'is_active'], 'idx_customers_priority_active');
        });

        // Employees Performance Indexes
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['warehouse_id', 'department', 'is_active'], 'idx_employees_warehouse_dept_active');
            $table->index(['employee_number'], 'idx_employees_number');
            $table->index(['position', 'is_active'], 'idx_employees_position_active');
        });

        // Vehicles Performance Indexes
        Schema::table('vehicles', function (Blueprint $table) {
            $table->index(['warehouse_id', 'is_active'], 'idx_vehicles_warehouse_active');
            $table->index(['vehicle_type', 'is_active'], 'idx_vehicles_type_active');
            $table->index(['license_plate'], 'idx_vehicles_license_plate');
        });

        // Drivers Performance Indexes
        Schema::table('drivers', function (Blueprint $table) {
            $table->index(['driver_number'], 'idx_drivers_number');
            $table->index(['is_active', 'created_at'], 'idx_drivers_active_created');
            $table->index(['license_number'], 'idx_drivers_license');
        });

        // Shipping Carriers Performance Indexes
        Schema::table('shipping_carriers', function (Blueprint $table) {
            $table->index(['code'], 'idx_shipping_carriers_code');
            $table->index(['is_active', 'created_at'], 'idx_shipping_carriers_active_created');
        });

        // Warehouses Performance Indexes
        Schema::table('warehouses', function (Blueprint $table) {
            $table->index(['code'], 'idx_warehouses_code');
            $table->index(['is_active', 'created_at'], 'idx_warehouses_active_created');
            $table->index(['city', 'state'], 'idx_warehouses_city_state');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Drop all performance indexes
        $tables = [
            'sales_orders' => [
                'idx_sales_orders_warehouse_status_created',
                'idx_sales_orders_customer_status',
                'idx_sales_orders_priority_ship_date',
                'idx_sales_orders_status_ship_date'
            ],
            'sales_order_items' => [
                'idx_sales_order_items_product_location',
                'idx_sales_order_items_order_status',
                'idx_sales_order_items_location_status'
            ],
            'product_inventory' => [
                'idx_inventory_warehouse_product_location',
                'idx_inventory_product_available',
                'idx_inventory_location_available',
                'idx_inventory_warehouse_available'
            ],
            'order_priorities' => [
                'idx_order_priorities_warehouse_status_score',
                'idx_order_priorities_level_created',
                'idx_order_priorities_effective_status'
            ],
            'back_orders' => [
                'idx_back_orders_warehouse_status_priority',
                'idx_back_orders_order_status',
                'idx_back_orders_availability_status',
                'idx_back_orders_created_priority'
            ],
            'order_consolidations' => [
                'idx_consolidations_warehouse_status_type',
                'idx_consolidations_status_expires',
                'idx_consolidations_created_priority'
            ],
            'batch_picks' => [
                'idx_batch_picks_warehouse_status_picker',
                'idx_batch_picks_type_status',
                'idx_batch_picks_created_priority',
                'idx_batch_picks_picker_status'
            ],
            'zone_picks' => [
                'idx_zone_picks_warehouse_zone_status',
                'idx_zone_picks_strategy_status',
                'idx_zone_picks_created_priority'
            ],
            'cluster_picks' => [
                'idx_cluster_picks_warehouse_status_picker',
                'idx_cluster_picks_strategy_status',
                'idx_cluster_picks_created_priority'
            ],
            'pack_orders' => [
                'idx_pack_orders_station_status_packer',
                'idx_pack_orders_order_status',
                'idx_pack_orders_created_priority',
                'idx_pack_orders_deadline_status'
            ],
            'packing_stations' => [
                'idx_packing_stations_warehouse_zone_active',
                'idx_packing_stations_status_active',
                'idx_packing_stations_type_active'
            ],
            'shipments' => [
                'idx_shipments_warehouse_carrier_status',
                'idx_shipments_ship_date_status',
                'idx_shipments_tracking_number',
                'idx_shipments_created_type'
            ],
            'shipping_rates' => [
                'idx_shipping_rates_carrier_service_zones',
                'idx_shipping_rates_effective_active',
                'idx_shipping_rates_structure_active'
            ],
            'load_plans' => [
                'idx_load_plans_warehouse_vehicle_status',
                'idx_load_plans_driver_status',
                'idx_load_plans_departure_status',
                'idx_load_plans_type_status'
            ],
            'carton_types' => [
                'idx_carton_types_category_active',
                'idx_carton_types_material_active',
                'idx_carton_types_weight_volume',
                'idx_carton_types_cost_active'
            ],
            'locations' => [
                'idx_locations_warehouse_zone_active',
                'idx_locations_type_active',
                'idx_locations_aisle_bay_level'
            ],
            'zones' => [
                'idx_zones_warehouse_type_active',
                'idx_zones_type_active'
            ],
            'products' => [
                'idx_products_sku',
                'idx_products_active_created',
                'idx_products_category_active'
            ],
            'customers' => [
                'idx_customers_email',
                'idx_customers_active_created',
                'idx_customers_priority_active'
            ],
            'employees' => [
                'idx_employees_warehouse_dept_active',
                'idx_employees_number',
                'idx_employees_position_active'
            ],
            'vehicles' => [
                'idx_vehicles_warehouse_active',
                'idx_vehicles_type_active',
                'idx_vehicles_license_plate'
            ],
            'drivers' => [
                'idx_drivers_number',
                'idx_drivers_active_created',
                'idx_drivers_license'
            ],
            'shipping_carriers' => [
                'idx_shipping_carriers_code',
                'idx_shipping_carriers_active_created'
            ],
            'warehouses' => [
                'idx_warehouses_code',
                'idx_warehouses_active_created',
                'idx_warehouses_city_state'
            ]
        ];

        foreach ($tables as $table => $indexes) {
            Schema::table($table, function (Blueprint $table) use ($indexes) {
                foreach ($indexes as $index) {
                    try {
                        $table->dropIndex($index);
                    } catch (Exception $e) {
                        // Index might not exist, continue
                    }
                }
            });
        }
    }
};