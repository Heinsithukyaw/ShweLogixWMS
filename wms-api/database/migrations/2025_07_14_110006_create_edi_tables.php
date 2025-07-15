<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // EDI trading partners
        Schema::create('edi_trading_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('partner_code')->unique();
            $table->text('description')->nullable();
            $table->foreignId('business_party_id')->nullable()->constrained('business_parties');
            $table->string('edi_standard'); // X12, EDIFACT, TRADACOMS, etc.
            $table->string('edi_version');
            $table->json('connection_details'); // FTP, AS2, VAN details
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // EDI document types
        Schema::create('edi_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_code')->unique(); // 850, 856, ORDERS, DESADV, etc.
            $table->text('description')->nullable();
            $table->string('edi_standard');
            $table->string('edi_version');
            $table->string('direction'); // inbound, outbound
            $table->json('segment_structure'); // Expected segments and structure
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // EDI mappings
        Schema::create('edi_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_partner_id')->constrained('edi_trading_partners');
            $table->foreignId('document_type_id')->constrained('edi_document_types');
            $table->string('entity_type'); // order, shipment, invoice, etc.
            $table->json('field_mappings'); // Mapping between EDI segments/elements and system fields
            $table->json('transformation_rules')->nullable(); // Data transformation rules
            $table->json('validation_rules')->nullable(); // Validation rules
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['trading_partner_id', 'document_type_id']);
        });
        
        // EDI transactions
        Schema::create('edi_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_partner_id')->constrained('edi_trading_partners');
            $table->foreignId('document_type_id')->constrained('edi_document_types');
            $table->string('transaction_id')->unique(); // Unique transaction ID
            $table->string('control_number'); // EDI control number
            $table->string('direction'); // inbound, outbound
            $table->string('status'); // received, processed, error, sent, acknowledged
            $table->text('original_file_path')->nullable();
            $table->text('processed_file_path')->nullable();
            $table->string('entity_type')->nullable(); // Related entity type
            $table->string('entity_id')->nullable(); // Related entity ID
            $table->json('transaction_data')->nullable(); // Parsed transaction data
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['trading_partner_id', 'document_type_id', 'status']);
            $table->index(['entity_type', 'entity_id']);
        });
        
        // EDI acknowledgments
        Schema::create('edi_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('edi_transactions');
            $table->string('acknowledgment_type'); // 997, 999, CONTRL, etc.
            $table->string('status'); // accepted, rejected, partial
            $table->text('original_file_path')->nullable();
            $table->json('acknowledgment_data')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
        
        // SAP IDoc configurations
        Schema::create('idoc_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('idoc_type'); // ORDERS01, DESADV01, etc.
            $table->string('idoc_version');
            $table->text('description')->nullable();
            $table->json('segment_structure'); // Expected segments and structure
            $table->string('direction'); // inbound, outbound
            $table->json('connection_details'); // SAP connection details
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // SAP IDoc transactions
        Schema::create('idoc_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idoc_configuration_id')->constrained('idoc_configurations');
            $table->string('idoc_number')->unique();
            $table->string('direction'); // inbound, outbound
            $table->string('status'); // received, processed, error, sent
            $table->text('original_file_path')->nullable();
            $table->text('processed_file_path')->nullable();
            $table->string('entity_type')->nullable(); // Related entity type
            $table->string('entity_id')->nullable(); // Related entity ID
            $table->json('transaction_data')->nullable(); // Parsed transaction data
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['idoc_configuration_id', 'status']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idoc_transactions');
        Schema::dropIfExists('idoc_configurations');
        Schema::dropIfExists('edi_acknowledgments');
        Schema::dropIfExists('edi_transactions');
        Schema::dropIfExists('edi_mappings');
        Schema::dropIfExists('edi_document_types');
        Schema::dropIfExists('edi_trading_partners');
    }
};