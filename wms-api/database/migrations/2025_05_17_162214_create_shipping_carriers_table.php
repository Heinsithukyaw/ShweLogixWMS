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
        Schema::create('shipping_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('carrier_code');
            $table->string('carrier_name');
            $table->string('contact_person');
            $table->string('phone_number');
            $table->string('email');
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('contract_details')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('service_type')->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('performance_rating')->nullable();
            $table->string('capabilities')->nullable();
            $table->string('created_by')->nullable();
            $table->string('last_modified_by')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 - inactive / 1 - active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_carriers');
    }
};
