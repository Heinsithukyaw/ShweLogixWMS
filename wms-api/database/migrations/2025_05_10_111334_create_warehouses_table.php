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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code');
            $table->string('warehouse_name');
            $table->string('warehouse_type');
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state_region')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code',20)->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('manager_name')->nullable();
            $table->string('storage_capacity')->nullable();
            $table->string('operating_hours')->nullable();
            $table->text('custom_attributes')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 - inactive / 1 - active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
