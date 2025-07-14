<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Shipments
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number')->unique();
            $table->json('sales_order_ids'); // Orders in this shipment
            $table->foreignId('customer_id')->constrained('business_parties');
            $table->foreignId('shipping_carrier_id')->constrained();
            $table->string('service_level'); // ground, express, overnight, etc.
            $table->enum('shipment_status', ['planned', 'ready', 'picked_up', 'in_transit', 'delivered', 'exception']);
            $table->enum('shipment_type', ['standard', 'express', 'freight', 'ltl', 'parcel']);
            $table->string('tracking_number')->nullable();
            $table->json('shipping_address');
            $table->json('billing_address')->nullable();
            $table->decimal('total_weight_kg', 10, 3);
            $table->decimal('total_volume_cm3', 15, 2);
            $table->integer('total_cartons');
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->decimal('insurance_cost', 8, 2)->nullable();
            $table->json('special_services')->nullable(); // signature, insurance, etc.
            $table->date('ship_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->text('shipping_notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['shipment_status', 'ship_date']);
            $table->index(['customer_id', 'shipment_status']);
            $table->index('tracking_number');
        });

        // Shipping Rates
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_carrier_id')->constrained();
            $table->string('service_code');
            $table->string('service_name');
            $table->string('origin_zip');
            $table->string('destination_zip');
            $table->decimal('weight_from_kg', 8, 3);
            $table->decimal('weight_to_kg', 8, 3);
            $table->decimal('base_rate', 8, 2);
            $table->decimal('fuel_surcharge_rate', 5, 4)->default(0); // percentage
            $table->json('additional_charges')->nullable(); // residential, signature, etc.
            $table->integer('transit_days');
            $table->boolean('is_active')->default(true);
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            
            $table->index(['shipping_carrier_id', 'service_code', 'is_active']);
            $table->index(['origin_zip', 'destination_zip']);
        });

        // Rate Shopping Results
        Schema::create('rate_shopping_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained();
            $table->string('origin_zip');
            $table->string('destination_zip');
            $table->decimal('total_weight_kg', 8, 3);
            $table->decimal('total_volume_cm3', 12, 2);
            $table->json('rate_quotes'); // Array of carrier quotes
            $table->foreignId('selected_carrier_id')->nullable()->constrained('shipping_carriers');
            $table->string('selected_service_code')->nullable();
            $table->decimal('selected_rate', 8, 2)->nullable();
            $table->json('selection_criteria')->nullable(); // cost, speed, reliability
            $table->timestamp('quoted_at');
            $table->timestamp('expires_at');
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['sales_order_id', 'quoted_at']);
        });

        // Shipping Labels
        Schema::create('shipping_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('packed_carton_id')->nullable()->constrained();
            $table->string('label_type'); // shipping, return, hazmat, etc.
            $table->string('tracking_number');
            $table->text('label_data'); // Base64 encoded label
            $table->string('label_format'); // PDF, PNG, ZPL, etc.
            $table->json('label_metadata')->nullable(); // dimensions, printer settings
            $table->boolean('is_printed')->default(false);
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['shipment_id', 'label_type']);
            $table->index('tracking_number');
        });

        // Shipping Documents
        Schema::create('shipping_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', ['bill_of_lading', 'packing_slip', 'commercial_invoice', 'customs_form', 'hazmat_form']);
            $table->string('document_number')->nullable();
            $table->text('document_data'); // Base64 encoded document
            $table->string('document_format'); // PDF, XML, etc.
            $table->json('document_metadata')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_generated')->default(false);
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['shipment_id', 'document_type']);
        });

        // Load Plans
        Schema::create('load_plans', function (Blueprint $table) {
            $table->id();
            $table->string('load_plan_number')->unique();
            $table->foreignId('shipping_carrier_id')->constrained();
            $table->string('vehicle_type'); // truck, van, trailer
            $table->string('vehicle_id')->nullable();
            $table->json('shipment_ids'); // Shipments in this load
            $table->enum('load_status', ['planned', 'loading', 'loaded', 'dispatched', 'delivered']);
            $table->decimal('total_weight_kg', 10, 3);
            $table->decimal('total_volume_cm3', 15, 2);
            $table->decimal('vehicle_capacity_weight_kg', 10, 3);
            $table->decimal('vehicle_capacity_volume_cm3', 15, 2);
            $table->decimal('utilization_weight_pct', 5, 2); // percentage
            $table->decimal('utilization_volume_pct', 5, 2); // percentage
            $table->json('loading_sequence')->nullable(); // Optimized loading order
            $table->date('planned_departure_date');
            $table->time('planned_departure_time');
            $table->timestamp('actual_departure_time')->nullable();
            $table->text('loading_notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['load_status', 'planned_departure_date']);
            $table->index('shipping_carrier_id');
        });

        // Loading Docks
        Schema::create('loading_docks', function (Blueprint $table) {
            $table->id();
            $table->string('dock_code')->unique();
            $table->string('dock_name');
            $table->foreignId('warehouse_id')->constrained();
            $table->enum('dock_type', ['outbound', 'inbound', 'cross_dock']);
            $table->enum('dock_status', ['available', 'occupied', 'maintenance', 'closed']);
            $table->json('dock_capabilities')->nullable(); // truck_types, equipment
            $table->decimal('max_vehicle_length_m', 5, 2)->nullable();
            $table->decimal('max_vehicle_height_m', 4, 2)->nullable();
            $table->boolean('has_dock_leveler')->default(false);
            $table->boolean('has_dock_seal')->default(false);
            $table->json('equipment_available')->nullable(); // forklifts, conveyors
            $table->timestamps();
            
            $table->index(['warehouse_id', 'dock_type', 'dock_status']);
        });

        // Dock Schedules
        Schema::create('dock_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_dock_id')->constrained();
            $table->foreignId('load_plan_id')->nullable()->constrained();
            $table->foreignId('shipping_carrier_id')->constrained();
            $table->date('scheduled_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');
            $table->time('actual_start_time')->nullable();
            $table->time('actual_end_time')->nullable();
            $table->enum('appointment_status', ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show']);
            $table->string('driver_name')->nullable();
            $table->string('vehicle_license')->nullable();
            $table->string('trailer_number')->nullable();
            $table->text('special_instructions')->nullable();
            $table->foreignId('scheduled_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['loading_dock_id', 'scheduled_date']);
            $table->index(['appointment_status', 'scheduled_date']);
        });

        // Loading Confirmations
        Schema::create('loading_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('load_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('dock_schedule_id')->constrained();
            $table->json('loaded_shipments'); // Confirmed loaded shipments
            $table->decimal('actual_weight_kg', 10, 3)->nullable();
            $table->integer('total_pieces');
            $table->enum('loading_method', ['manual', 'forklift', 'conveyor', 'automated']);
            $table->foreignId('loading_supervisor_id')->constrained('employees');
            $table->string('driver_signature')->nullable(); // Base64 encoded signature
            $table->json('loading_photos')->nullable(); // Photo evidence
            $table->text('loading_notes')->nullable();
            $table->timestamp('loading_started_at');
            $table->timestamp('loading_completed_at');
            $table->timestamps();
            
            $table->index(['load_plan_id', 'loading_completed_at']);
        });

        // Delivery Confirmations
        Schema::create('delivery_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->string('tracking_number');
            $table->enum('delivery_status', ['delivered', 'attempted', 'exception', 'returned']);
            $table->timestamp('delivery_timestamp')->nullable();
            $table->string('delivered_to')->nullable(); // Person who received
            $table->string('delivery_location')->nullable(); // Front door, office, etc.
            $table->string('signature_data')->nullable(); // Base64 encoded signature
            $table->json('delivery_photos')->nullable(); // Proof of delivery photos
            $table->text('delivery_notes')->nullable();
            $table->json('exception_details')->nullable(); // If delivery failed
            $table->string('carrier_reference')->nullable();
            $table->timestamp('updated_at_carrier');
            $table->timestamps();
            
            $table->index(['shipment_id', 'delivery_status']);
            $table->index(['tracking_number', 'delivery_timestamp']);
        });

        // Shipping Manifests
        Schema::create('shipping_manifests', function (Blueprint $table) {
            $table->id();
            $table->string('manifest_number')->unique();
            $table->foreignId('shipping_carrier_id')->constrained();
            $table->date('manifest_date');
            $table->json('shipment_ids'); // Shipments in this manifest
            $table->integer('total_shipments');
            $table->integer('total_pieces');
            $table->decimal('total_weight_kg', 10, 3);
            $table->decimal('total_declared_value', 12, 2)->nullable();
            $table->enum('manifest_status', ['open', 'closed', 'transmitted', 'confirmed']);
            $table->text('manifest_data')->nullable(); // EDI or XML data
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('transmitted_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['shipping_carrier_id', 'manifest_date']);
            $table->index(['manifest_status', 'manifest_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_manifests');
        Schema::dropIfExists('delivery_confirmations');
        Schema::dropIfExists('loading_confirmations');
        Schema::dropIfExists('dock_schedules');
        Schema::dropIfExists('loading_docks');
        Schema::dropIfExists('load_plans');
        Schema::dropIfExists('shipping_documents');
        Schema::dropIfExists('shipping_labels');
        Schema::dropIfExists('rate_shopping_results');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipments');
    }
};