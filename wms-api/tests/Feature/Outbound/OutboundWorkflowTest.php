<?php

namespace Tests\Feature\Outbound;

use Tests\TestCase;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\ProductInventory;
use App\Models\Location;
use App\Models\Zone;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\ShippingCarrier;
use App\Models\CartonType;
use App\Models\Outbound\PackingStation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class OutboundWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $user;
    private $warehouse;
    private $customer;
    private $products;
    private $salesOrder;
    private $locations;
    private $zones;
    private $employee;
    private $vehicle;
    private $driver;
    private $carrier;
    private $cartonType;
    private $packingStation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
    }

    /**
     * Test complete outbound workflow from order to shipment
     */
    public function test_complete_outbound_workflow()
    {
        // Step 1: Order Priority Management
        $this->step1_order_priority_management();
        
        // Step 2: Order Consolidation
        $this->step2_order_consolidation();
        
        // Step 3: Batch Pick Creation
        $this->step3_batch_pick_creation();
        
        // Step 4: Zone Pick Execution
        $this->step4_zone_pick_execution();
        
        // Step 5: Cluster Pick Optimization
        $this->step5_cluster_pick_optimization();
        
        // Step 6: Pack Order Processing
        $this->step6_pack_order_processing();
        
        // Step 7: Shipment Creation
        $this->step7_shipment_creation();
        
        // Step 8: Load Plan and Dispatch
        $this->step8_load_plan_and_dispatch();
        
        // Step 9: Analytics Verification
        $this->step9_analytics_verification();
    }

    private function step1_order_priority_management()
    {
        // Set order priority
        $response = $this->actingAs($this->user)->postJson('/api/outbound/order-priorities', [
            'sales_order_id' => $this->salesOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'priority_level' => 'high',
            'priority_reason' => 'VIP Customer - Rush Order',
            'requested_ship_date' => now()->addDay()->toDateString(),
            'customer_priority' => 'expedited',
            'business_impact' => 'High value customer retention',
            'special_instructions' => 'Handle with priority'
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'sales_order_id',
                'priority_level',
                'priority_score',
                'priority_reason'
            ]
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('high', $response->json('data.priority_level'));
        $this->assertGreaterThan(60, $response->json('data.priority_score'));
    }

    private function step2_order_consolidation()
    {
        // Create additional order for consolidation
        $secondOrder = SalesOrder::create([
            'order_number' => 'ORD-' . time() . '-2',
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'status' => 'allocated',
            'total_amount' => 150.00,
            'order_date' => now(),
            'requested_ship_date' => now()->addDay()
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $secondOrder->id,
            'product_id' => $this->products[0]->id,
            'quantity' => 2,
            'unit_price' => 75.00,
            'total_amount' => 150.00,
            'location_id' => $this->locations[0]->id
        ]);

        // Test order consolidation
        $response = $this->actingAs($this->user)->postJson('/api/outbound/order-consolidations', [
            'warehouse_id' => $this->warehouse->id,
            'consolidation_type' => 'customer',
            'consolidation_criteria' => ['customer_id' => $this->customer->id],
            'orders' => [$this->salesOrder->id, $secondOrder->id],
            'priority_level' => 'high',
            'consolidation_window_hours' => 24,
            'max_orders' => 10,
            'notes' => 'Customer consolidation for efficiency'
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'consolidation_number',
                'total_orders',
                'estimated_savings',
                'consolidation_score'
            ]
        ]);

        $this->assertEquals(2, $response->json('data.total_orders'));
        $this->assertGreaterThan(0, $response->json('data.estimated_savings'));
    }

    private function step3_batch_pick_creation()
    {
        // Create batch pick
        $response = $this->actingAs($this->user)->postJson('/api/outbound/batch-picks', [
            'warehouse_id' => $this->warehouse->id,
            'pick_type' => 'multi_order',
            'batch_strategy' => 'priority',
            'max_orders' => 5,
            'max_items' => 50,
            'assigned_picker_id' => $this->employee->id,
            'auto_assign' => true
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'batch_number',
                'total_orders',
                'total_items',
                'assigned_picker_id',
                'status'
            ]
        ]);

        $batchPickId = $response->json('data.id');

        // Start batch pick
        $startResponse = $this->actingAs($this->user)->postJson("/api/outbound/batch-picks/{$batchPickId}/start");
        $startResponse->assertStatus(200);
        $this->assertEquals('in_progress', $startResponse->json('data.status'));

        // Complete batch pick
        $completeResponse = $this->actingAs($this->user)->postJson("/api/outbound/batch-picks/{$batchPickId}/complete", [
            'completion_notes' => 'Batch pick completed successfully'
        ]);
        $completeResponse->assertStatus(200);
        $this->assertEquals('completed', $completeResponse->json('data.status'));
    }

    private function step4_zone_pick_execution()
    {
        // Create zone pick
        $response = $this->actingAs($this->user)->postJson('/api/outbound/zone-picks', [
            'warehouse_id' => $this->warehouse->id,
            'zone_id' => $this->zones[0]->id,
            'pick_strategy' => 'distance_optimized',
            'orders' => [$this->salesOrder->id],
            'max_pickers' => 2,
            'auto_assign_pickers' => true
        ]);

        $response->assertStatus(201);
        $zonePickId = $response->json('data.id');

        // Assign pickers
        $assignResponse = $this->actingAs($this->user)->postJson("/api/outbound/zone-picks/{$zonePickId}/assign-pickers", [
            'assignments' => [
                [
                    'picker_id' => $this->employee->id,
                    'assignment_type' => 'primary'
                ]
            ]
        ]);
        $assignResponse->assertStatus(200);

        // Start zone pick
        $startResponse = $this->actingAs($this->user)->postJson("/api/outbound/zone-picks/{$zonePickId}/start");
        $startResponse->assertStatus(200);

        // Complete zone pick
        $completeResponse = $this->actingAs($this->user)->postJson("/api/outbound/zone-picks/{$zonePickId}/complete", [
            'completion_notes' => 'Zone pick completed'
        ]);
        $completeResponse->assertStatus(200);
    }

    private function step5_cluster_pick_optimization()
    {
        // Create cluster pick
        $response = $this->actingAs($this->user)->postJson('/api/outbound/cluster-picks', [
            'warehouse_id' => $this->warehouse->id,
            'cluster_strategy' => 'location_based',
            'orders' => [$this->salesOrder->id],
            'max_cluster_size' => 5,
            'assigned_picker_id' => $this->employee->id,
            'auto_optimize' => true
        ]);

        $response->assertStatus(201);
        $clusterPickId = $response->json('data.id');

        // Optimize cluster pick
        $optimizeResponse = $this->actingAs($this->user)->postJson("/api/outbound/cluster-picks/{$clusterPickId}/optimize", [
            'optimization_type' => 'mixed',
            'force_reoptimize' => true
        ]);
        $optimizeResponse->assertStatus(200);
        $this->assertGreaterThan(0, $optimizeResponse->json('data.optimization_result.efficiency_score'));

        // Start and complete cluster pick
        $startResponse = $this->actingAs($this->user)->postJson("/api/outbound/cluster-picks/{$clusterPickId}/start");
        $startResponse->assertStatus(200);

        $completeResponse = $this->actingAs($this->user)->postJson("/api/outbound/cluster-picks/{$clusterPickId}/complete", [
            'completion_notes' => 'Cluster pick completed'
        ]);
        $completeResponse->assertStatus(200);
    }

    private function step6_pack_order_processing()
    {
        // Update order status to picked
        $this->salesOrder->update(['status' => 'picked']);

        // Create pack order
        $response = $this->actingAs($this->user)->postJson('/api/outbound/pack-orders', [
            'sales_order_id' => $this->salesOrder->id,
            'packing_station_id' => $this->packingStation->id,
            'packer_id' => $this->employee->id,
            'carton_type_id' => $this->cartonType->id,
            'pack_method' => 'multi_item',
            'priority_level' => 'high',
            'items' => [
                [
                    'sales_order_item_id' => $this->salesOrder->items->first()->id,
                    'quantity_to_pack' => $this->salesOrder->items->first()->quantity
                ]
            ]
        ]);

        $response->assertStatus(201);
        $packOrderId = $response->json('data.id');

        // Start packing
        $startResponse = $this->actingAs($this->user)->postJson("/api/outbound/pack-orders/{$packOrderId}/start-packing");
        $startResponse->assertStatus(200);

        // Complete packing
        $completeResponse = $this->actingAs($this->user)->postJson("/api/outbound/pack-orders/{$packOrderId}/complete-packing", [
            'actual_weight' => 2.5,
            'actual_dimensions' => [
                'length' => 30,
                'width' => 20,
                'height' => 15
            ],
            'carton_used' => $this->cartonType->id,
            'items' => [
                [
                    'item_id' => $response->json('data.items.0.id'),
                    'quantity_packed' => $this->salesOrder->items->first()->quantity,
                    'condition' => 'good'
                ]
            ]
        ]);
        $completeResponse->assertStatus(200);
        $this->assertEquals('packed', $completeResponse->json('data.status'));
    }

    private function step7_shipment_creation()
    {
        // Update order status to packed
        $this->salesOrder->update(['status' => 'packed']);

        // Create shipment
        $response = $this->actingAs($this->user)->postJson('/api/outbound/shipments', [
            'warehouse_id' => $this->warehouse->id,
            'carrier_id' => $this->carrier->id,
            'shipment_type' => 'standard',
            'service_level' => 'ground',
            'ship_date' => now()->addDay()->toDateString(),
            'ship_from_address' => [
                'street' => '123 Warehouse St',
                'city' => 'Warehouse City',
                'state' => 'WS',
                'postal_code' => '12345',
                'country' => 'US'
            ],
            'ship_to_address' => [
                'street' => '456 Customer Ave',
                'city' => 'Customer City',
                'state' => 'CS',
                'postal_code' => '67890',
                'country' => 'US'
            ],
            'orders' => [$this->salesOrder->id],
            'package_type' => 'box',
            'dimensions' => [
                'length' => 30,
                'width' => 20,
                'height' => 15
            ],
            'weight' => 2.5,
            'declared_value' => 100.00
        ]);

        $response->assertStatus(201);
        $shipmentId = $response->json('data.id');

        // Ship the shipment
        $shipResponse = $this->actingAs($this->user)->postJson("/api/outbound/shipments/{$shipmentId}/ship", [
            'actual_ship_date' => now()->toDateTimeString(),
            'shipped_by' => $this->user->id,
            'shipping_notes' => 'Shipment dispatched successfully'
        ]);
        $shipResponse->assertStatus(200);
        $this->assertEquals('shipped', $shipResponse->json('data.status'));
    }

    private function step8_load_plan_and_dispatch()
    {
        // Get the shipment
        $shipment = $this->salesOrder->fresh()->shipments->first();
        $shipment->update(['status' => 'ready_to_ship']);

        // Create load plan
        $response = $this->actingAs($this->user)->postJson('/api/outbound/load-plans', [
            'warehouse_id' => $this->warehouse->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'load_type' => 'delivery',
            'planned_departure_time' => now()->addHours(2)->toDateTimeString(),
            'route_optimization' => 'distance',
            'shipments' => [$shipment->id],
            'priority_level' => 'high',
            'auto_optimize' => true
        ]);

        $response->assertStatus(201);
        $loadPlanId = $response->json('data.id');

        // Optimize load plan
        $optimizeResponse = $this->actingAs($this->user)->postJson("/api/outbound/load-plans/{$loadPlanId}/optimize", [
            'optimization_type' => 'distance',
            'force_reoptimize' => true
        ]);
        $optimizeResponse->assertStatus(200);

        // Update status to loaded
        $updateResponse = $this->actingAs($this->user)->putJson("/api/outbound/load-plans/{$loadPlanId}", [
            'status' => 'loaded'
        ]);
        $updateResponse->assertStatus(200);

        // Dispatch load plan
        $dispatchResponse = $this->actingAs($this->user)->postJson("/api/outbound/load-plans/{$loadPlanId}/dispatch", [
            'actual_departure_time' => now()->toDateTimeString(),
            'dispatch_notes' => 'Load dispatched on schedule',
            'fuel_level' => 95.0,
            'odometer_reading' => 125000
        ]);
        $dispatchResponse->assertStatus(200);
        $this->assertEquals('dispatched', $dispatchResponse->json('data.status'));
    }

    private function step9_analytics_verification()
    {
        // Test order priority analytics
        $priorityAnalytics = $this->actingAs($this->user)->getJson('/api/outbound/order-priorities/analytics');
        $priorityAnalytics->assertStatus(200);
        $priorityAnalytics->assertJsonStructure([
            'success',
            'data' => [
                'total_priorities',
                'by_level',
                'average_score'
            ]
        ]);

        // Test batch pick analytics
        $batchAnalytics = $this->actingAs($this->user)->getJson('/api/outbound/batch-picks/analytics');
        $batchAnalytics->assertStatus(200);

        // Test shipment analytics
        $shipmentAnalytics = $this->actingAs($this->user)->getJson('/api/outbound/shipments/analytics');
        $shipmentAnalytics->assertStatus(200);

        // Test load plan analytics
        $loadPlanAnalytics = $this->actingAs($this->user)->getJson('/api/outbound/load-plans/analytics');
        $loadPlanAnalytics->assertStatus(200);

        // Verify all analytics return valid data
        $this->assertTrue($priorityAnalytics->json('success'));
        $this->assertTrue($batchAnalytics->json('success'));
        $this->assertTrue($shipmentAnalytics->json('success'));
        $this->assertTrue($loadPlanAnalytics->json('success'));
    }

    private function setupTestData()
    {
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ]);
        
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'code' => 'TW001',
            'address' => '123 Warehouse St',
            'city' => 'Test City',
            'state' => 'TS',
            'postal_code' => '12345',
            'country' => 'US',
            'is_active' => true
        ]);
        
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '555-0123',
            'address' => '456 Customer Ave',
            'city' => 'Customer City',
            'state' => 'CS',
            'postal_code' => '67890',
            'country' => 'US',
            'is_active' => true
        ]);
        
        $this->products = collect();
        for ($i = 1; $i <= 3; $i++) {
            $this->products->push(Product::create([
                'name' => "Test Product {$i}",
                'sku' => "SKU-{$i}",
                'description' => "Test product {$i} description",
                'weight' => 1.5,
                'dimensions' => ['length' => 10, 'width' => 8, 'height' => 6],
                'is_active' => true
            ]));
        }
        
        $this->zones = collect();
        for ($i = 1; $i <= 2; $i++) {
            $this->zones->push(Zone::create([
                'warehouse_id' => $this->warehouse->id,
                'name' => "Zone {$i}",
                'code' => "Z{$i}",
                'zone_type' => 'picking',
                'is_active' => true
            ]));
        }
        
        $this->locations = collect();
        for ($i = 1; $i <= 3; $i++) {
            $this->locations->push(Location::create([
                'warehouse_id' => $this->warehouse->id,
                'zone_id' => $this->zones[0]->id,
                'aisle' => "A{$i}",
                'bay' => "B{$i}",
                'level' => "L{$i}",
                'position' => "P{$i}",
                'location_code' => "A{$i}-B{$i}-L{$i}-P{$i}",
                'location_type' => 'pick',
                'is_active' => true
            ]));
        }
        
        // Create inventory
        foreach ($this->products as $product) {
            ProductInventory::create([
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouse->id,
                'location_id' => $this->locations[0]->id,
                'quantity_available' => 100,
                'quantity_on_hand' => 100,
                'quantity_reserved' => 0,
                'quantity_allocated' => 0
            ]);
        }
        
        $this->salesOrder = SalesOrder::create([
            'order_number' => 'ORD-' . time(),
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'status' => 'allocated',
            'total_amount' => 100.00,
            'order_date' => now(),
            'requested_ship_date' => now()->addDay()
        ]);
        
        SalesOrderItem::create([
            'sales_order_id' => $this->salesOrder->id,
            'product_id' => $this->products[0]->id,
            'quantity' => 5,
            'unit_price' => 20.00,
            'total_amount' => 100.00,
            'location_id' => $this->locations[0]->id
        ]);
        
        $this->employee = Employee::create([
            'warehouse_id' => $this->warehouse->id,
            'employee_number' => 'EMP001',
            'name' => 'Test Employee',
            'department' => 'warehouse',
            'position' => 'picker',
            'is_active' => true
        ]);
        
        $this->vehicle = Vehicle::create([
            'warehouse_id' => $this->warehouse->id,
            'vehicle_number' => 'VEH001',
            'vehicle_type' => 'truck',
            'make' => 'Ford',
            'model' => 'Transit',
            'year' => 2023,
            'license_plate' => 'ABC123',
            'max_weight' => 1000,
            'max_volume' => 50,
            'is_active' => true
        ]);
        
        $this->driver = Driver::create([
            'driver_number' => 'DRV001',
            'name' => 'Test Driver',
            'license_number' => 'DL123456',
            'phone' => '555-0456',
            'is_active' => true
        ]);
        
        $this->carrier = ShippingCarrier::create([
            'name' => 'Test Carrier',
            'code' => 'TC',
            'contact_info' => ['phone' => '555-0789'],
            'is_active' => true,
            'supports_labels' => true
        ]);
        
        $this->cartonType = CartonType::create([
            'carton_code' => 'BOX001',
            'name' => 'Standard Box',
            'carton_category' => 'box',
            'material_type' => 'cardboard',
            'external_dimensions' => ['length' => 35, 'width' => 25, 'height' => 20],
            'internal_dimensions' => ['length' => 33, 'width' => 23, 'height' => 18],
            'external_volume' => 17500,
            'internal_volume' => 14058,
            'max_weight' => 10,
            'tare_weight' => 0.5,
            'cost_per_unit' => 2.50,
            'is_active' => true,
            'volume_efficiency' => 0.8,
            'weight_to_volume_ratio' => 0.0007
        ]);
        
        $this->packingStation = PackingStation::create([
            'station_code' => 'PS001',
            'station_name' => 'Packing Station 1',
            'warehouse_id' => $this->warehouse->id,
            'zone_id' => $this->zones[0]->id,
            'station_type' => 'manual',
            'capacity_per_hour' => 20,
            'supported_package_types' => ['box', 'envelope'],
            'is_active' => true,
            'status' => 'idle'
        ]);
    }
}