<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tenants
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable();
            $table->string('database_name')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('subscription_plan')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->bigInteger('storage_limit')->nullable(); // in bytes
            $table->integer('user_limit')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'subscription_expires_at']);
        });

        // Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->foreignId('tenant_id')->nullable()->constrained();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name', 'tenant_id']);
            $table->index(['tenant_id', 'is_system_role']);
        });

        // Permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('module');
            $table->string('category');
            $table->boolean('is_system_permission')->default(false);
            $table->timestamps();

            $table->index(['module', 'category']);
        });

        // Role Permissions
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('granted_by')->nullable()->constrained('users');
            $table->timestamp('granted_at');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // User Roles
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users');
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        // User Permissions (direct permissions)
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('granted_by')->nullable()->constrained('users');
            $table->timestamp('granted_at');
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });

        // User Activity Logs
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('tenant_id')->nullable()->constrained();
            $table->string('activity_type');
            $table->text('activity_description');
            $table->string('module');
            $table->string('entity_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->string('status');
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index(['tenant_id', 'activity_type']);
            $table->index(['module', 'entity_type']);
        });

        // Add tenant_id to users table if it doesn't exist
        if (!Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->constrained();
                $table->index('tenant_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        
        if (Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
        
        Schema::dropIfExists('tenants');
    }
};