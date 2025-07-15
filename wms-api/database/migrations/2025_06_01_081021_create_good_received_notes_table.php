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
        Schema::create('good_received_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_code');
            $table->foreignId('inbound_shipment_id')->constrained('inbound_shipments')->onDelete('cascade');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->foreignId('supplier_id')->constrained('business_parties')->onDelete('cascade');
            $table->date('received_date')->nullable();
            $table->foreignId('created_by')->constrained('employees')->onDelete('cascade')->nullable();
            $table->foreignId('approved_by')->constrained('employees')->onDelete('cascade')->nullable();
            $table->integer('total_items')->nullable();
            $table->text('notes')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0 - pending / 1 - rejected / 2 - approved');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('good_received_notes');
    }
};
