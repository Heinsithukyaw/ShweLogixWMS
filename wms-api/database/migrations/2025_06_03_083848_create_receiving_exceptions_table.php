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
        Schema::create('receiving_exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('exception_code');
            $table->foreignId('asn_id')->constrained('advanced_shipping_notices')->onDelete('cascade');
            $table->foreignId('asn_detail_id')->constrained('advanced_shipping_notice_details')->onDelete('cascade');
            $table->string('exception_type');
            $table->foreignId('item_id')->constrained('products')->onDelete('cascade');
            $table->text('item_description')->nullable();
            $table->tinyInteger('severity')->default(0)->comment('0 - Low / 1 - medium / 2 - high / 3 - critical');
            $table->tinyInteger('status')->default(0)->comment('0 - pending info / 1 - in progress / 2 - open / 3 - resolved');
            $table->unsignedBigInteger('reported_by_id')->nullable();
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->date('reported_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_exceptions');
    }
};
