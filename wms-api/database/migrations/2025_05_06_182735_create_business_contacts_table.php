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
        Schema::create('business_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('contact_code');
            $table->string('contact_name');
            $table->foreignId('business_party_id')->constrained('business_parties')->onDelete('cascade');
            $table->string('designation');
            $table->string('department')->nullable();
            $table->string('phone_number');
            $table->string('email');
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('preferred_contact_method');
            $table->tinyInteger('status')->default(1)->comment('0 - inactive / 1 - active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_contacts');
    }
};
