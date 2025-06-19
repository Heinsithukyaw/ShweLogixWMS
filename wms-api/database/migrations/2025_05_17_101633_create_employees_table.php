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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code');
            $table->string('employee_name');
            $table->string('email');
            $table->string('phone_number');
            $table->string('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('job_title')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('shift')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('salary')->nullable();
            $table->string('currency')->nullable();
            $table->tinyInteger('is_supervisor')->default(0);
            $table->tinyInteger('status')->default(1)->comment('0 - in active / 1 - active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
