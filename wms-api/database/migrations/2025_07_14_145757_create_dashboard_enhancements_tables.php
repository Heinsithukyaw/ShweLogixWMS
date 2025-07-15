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
        // Enhanced widget library
        Schema::create('widget_library', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('category'); // chart, metric, table, map, custom
            $table->string('widget_type'); // line_chart, bar_chart, pie_chart, gauge, kpi_card, data_table, heat_map
            $table->text('description')->nullable();
            $table->json('default_config'); // default widget configuration
            $table->json('config_schema'); // configuration options schema
            $table->json('data_requirements'); // required data structure
            $table->string('component_path'); // frontend component path
            $table->json('supported_data_sources'); // compatible data sources
            $table->json('customization_options'); // available customizations
            $table->string('preview_image')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // User dashboard customizations
        Schema::create('user_dashboard_customizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->onDelete('cascade');
            $table->string('user_id'); // user identifier
            $table->json('layout_config'); // grid layout configuration
            $table->json('widget_positions'); // widget positions and sizes
            $table->json('filter_preferences')->nullable(); // saved filter states
            $table->json('theme_settings')->nullable(); // color scheme, etc.
            $table->json('refresh_settings')->nullable(); // auto-refresh preferences
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->unique(['dashboard_id', 'user_id']);
        });

        // Enhanced dashboard widgets (extends existing dashboard_widgets)
        Schema::create('enhanced_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->onDelete('cascade');
            $table->foreignId('widget_library_id')->constrained('widget_library')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('widget_config'); // specific widget configuration
            $table->json('data_config'); // data source and query configuration
            $table->json('display_config'); // styling and display options
            $table->json('interaction_config')->nullable(); // drill-down, filters, etc.
            $table->integer('grid_x')->default(0); // grid position
            $table->integer('grid_y')->default(0);
            $table->integer('grid_width')->default(4); // grid width
            $table->integer('grid_height')->default(3); // grid height
            $table->integer('min_width')->default(2);
            $table->integer('min_height')->default(2);
            $table->integer('max_width')->nullable();
            $table->integer('max_height')->nullable();
            $table->boolean('is_resizable')->default(true);
            $table->boolean('is_movable')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->integer('refresh_interval')->default(300); // seconds
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Interactive visualization configurations
        Schema::create('visualization_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained('enhanced_dashboard_widgets')->onDelete('cascade');
            $table->string('interaction_type'); // click, hover, drill_down, filter, zoom
            $table->string('trigger_element'); // chart_element, button, menu_item
            $table->json('trigger_config'); // specific trigger configuration
            $table->string('action_type'); // navigate, filter, modal, api_call, export
            $table->json('action_config'); // action-specific configuration
            $table->json('target_config')->nullable(); // target widget/dashboard
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Data drill-down configurations
        Schema::create('drill_down_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained('enhanced_dashboard_widgets')->onDelete('cascade');
            $table->string('drill_level'); // level_1, level_2, level_3, etc.
            $table->string('source_field'); // field that triggers drill-down
            $table->json('target_data_config'); // new data source configuration
            $table->json('filter_mapping'); // how to map drill-down filters
            $table->json('display_config'); // how to display drill-down data
            $table->string('navigation_type')->default('modal'); // modal, new_tab, inline
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        // Widget data cache for performance
        Schema::create('widget_data_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained('enhanced_dashboard_widgets')->onDelete('cascade');
            $table->string('cache_key')->unique();
            $table->json('query_parameters'); // parameters used for this cache
            $table->longText('cached_data'); // serialized widget data
            $table->integer('record_count');
            $table->datetime('generated_at');
            $table->datetime('expires_at');
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
            
            $table->index(['widget_id', 'expires_at']);
        });

        // Dashboard themes
        Schema::create('dashboard_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->json('color_scheme'); // primary, secondary, accent colors
            $table->json('typography_config'); // font settings
            $table->json('layout_config'); // spacing, borders, etc.
            $table->json('widget_styles'); // default widget styling
            $table->json('chart_styles'); // chart-specific styling
            $table->string('preview_image')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Dashboard filters (global filters that affect multiple widgets)
        Schema::create('dashboard_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->onDelete('cascade');
            $table->string('name');
            $table->string('filter_type'); // date_range, dropdown, multi_select, text, number_range
            $table->string('data_field'); // field this filter applies to
            $table->json('filter_config'); // filter-specific configuration
            $table->json('default_value')->nullable();
            $table->json('options')->nullable(); // for dropdown/select filters
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Widget filter mappings (how dashboard filters affect widgets)
        Schema::create('widget_filter_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained('enhanced_dashboard_widgets')->onDelete('cascade');
            $table->foreignId('dashboard_filter_id')->constrained('dashboard_filters')->onDelete('cascade');
            $table->string('widget_field'); // field in widget data to filter
            $table->string('mapping_type')->default('direct'); // direct, calculated, custom
            $table->json('mapping_config')->nullable(); // transformation rules
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            
            $table->unique(['widget_id', 'dashboard_filter_id']);
        });

        // Dashboard sharing and permissions
        Schema::create('dashboard_sharing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->onDelete('cascade');
            $table->string('share_type'); // user, role, public, link
            $table->string('shared_with')->nullable(); // user_id, role_name, or null for public
            $table->string('permission_level'); // view, edit, admin
            $table->string('share_token')->nullable()->unique(); // for link sharing
            $table->datetime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('shared_by');
            $table->timestamps();
            
            $table->index(['dashboard_id', 'share_type']);
        });

        // Dashboard usage analytics
        Schema::create('dashboard_usage_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->onDelete('cascade');
            $table->foreignId('widget_id')->nullable()->constrained('enhanced_dashboard_widgets')->onDelete('cascade');
            $table->string('user_id');
            $table->string('action_type'); // view, interact, export, filter, drill_down
            $table->datetime('action_time');
            $table->json('action_details')->nullable(); // specific action data
            $table->string('session_id')->nullable();
            $table->integer('duration_seconds')->nullable(); // for view actions
            $table->timestamps();
            
            $table->index(['dashboard_id', 'action_time']);
            $table->index(['user_id', 'action_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_usage_analytics');
        Schema::dropIfExists('dashboard_sharing');
        Schema::dropIfExists('widget_filter_mappings');
        Schema::dropIfExists('dashboard_filters');
        Schema::dropIfExists('dashboard_themes');
        Schema::dropIfExists('widget_data_cache');
        Schema::dropIfExists('drill_down_configs');
        Schema::dropIfExists('visualization_interactions');
        Schema::dropIfExists('enhanced_dashboard_widgets');
        Schema::dropIfExists('user_dashboard_customizations');
        Schema::dropIfExists('widget_library');
    }
};
