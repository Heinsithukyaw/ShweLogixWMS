<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dashboard\WidgetLibrary;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WidgetLibraryController extends Controller
{
    /**
     * Display a listing of widget library items
     */
    public function index(Request $request): JsonResponse
    {
        $query = WidgetLibrary::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by widget type
        if ($request->has('widget_type')) {
            $query->where('widget_type', $request->widget_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by public status
        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $widgets = $request->has('per_page') 
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $widgets,
            'message' => 'Widget library items retrieved successfully'
        ]);
    }

    /**
     * Store a newly created widget
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:widget_library,code',
            'category' => 'required|in:metric,chart,map,table,gauge,custom',
            'widget_type' => 'required|in:line_chart,bar_chart,pie_chart,gauge,heat_map,table,metric_card,map',
            'description' => 'nullable|string',
            'default_config' => 'required|array',
            'config_schema' => 'required|array',
            'data_requirements' => 'required|array|min:1',
            'component_path' => 'required|string|max:255',
            'supported_data_sources' => 'required|array|min:1',
            'customization_options' => 'nullable|array',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);

        $validated['is_public'] = $validated['is_public'] ?? true;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Validate configuration schema
        $this->validateConfigSchema($validated['config_schema']);

        // Validate data requirements
        $this->validateDataRequirements($validated['data_requirements']);

        $widget = WidgetLibrary::create($validated);

        return response()->json([
            'success' => true,
            'data' => $widget,
            'message' => 'Widget created successfully'
        ], 201);
    }

    /**
     * Display the specified widget
     */
    public function show(WidgetLibrary $widget): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $widget,
            'message' => 'Widget retrieved successfully'
        ]);
    }

    /**
     * Update the specified widget
     */
    public function update(Request $request, WidgetLibrary $widget): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:100|unique:widget_library,code,' . $widget->id,
            'category' => 'sometimes|in:metric,chart,map,table,gauge,custom',
            'widget_type' => 'sometimes|in:line_chart,bar_chart,pie_chart,gauge,heat_map,table,metric_card,map',
            'description' => 'sometimes|string',
            'default_config' => 'sometimes|array',
            'config_schema' => 'sometimes|array',
            'data_requirements' => 'sometimes|array|min:1',
            'component_path' => 'sometimes|string|max:255',
            'supported_data_sources' => 'sometimes|array|min:1',
            'customization_options' => 'sometimes|array',
            'is_public' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean'
        ]);

        // Validate configuration schema if provided
        if (isset($validated['config_schema'])) {
            $this->validateConfigSchema($validated['config_schema']);
        }

        // Validate data requirements if provided
        if (isset($validated['data_requirements'])) {
            $this->validateDataRequirements($validated['data_requirements']);
        }

        $widget->update($validated);

        return response()->json([
            'success' => true,
            'data' => $widget,
            'message' => 'Widget updated successfully'
        ]);
    }

    /**
     * Remove the specified widget
     */
    public function destroy(WidgetLibrary $widget): JsonResponse
    {
        $widget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Widget deleted successfully'
        ]);
    }

    /**
     * Get widget categories
     */
    public function categories(): JsonResponse
    {
        $categories = [
            'metric' => [
                'name' => 'Metrics',
                'description' => 'Single value metric displays',
                'widget_types' => ['metric_card', 'gauge'],
                'examples' => ['Space Utilization %', 'Equipment Count', 'Efficiency Score']
            ],
            'chart' => [
                'name' => 'Charts',
                'description' => 'Data visualization charts',
                'widget_types' => ['line_chart', 'bar_chart', 'pie_chart'],
                'examples' => ['Utilization Trends', 'Equipment Performance', 'Zone Comparison']
            ],
            'map' => [
                'name' => 'Maps',
                'description' => 'Spatial and heat map visualizations',
                'widget_types' => ['heat_map', 'map'],
                'examples' => ['Equipment Tracking', 'Activity Heat Maps', 'Zone Layout']
            ],
            'table' => [
                'name' => 'Tables',
                'description' => 'Tabular data displays',
                'widget_types' => ['table'],
                'examples' => ['Equipment List', 'Zone Status', 'Recent Activities']
            ],
            'gauge' => [
                'name' => 'Gauges',
                'description' => 'Gauge and progress indicators',
                'widget_types' => ['gauge'],
                'examples' => ['Capacity Gauge', 'Performance Meter', 'Battery Level']
            ],
            'custom' => [
                'name' => 'Custom',
                'description' => 'Custom widget implementations',
                'widget_types' => ['custom'],
                'examples' => ['Custom Analytics', 'Specialized Views', 'Integration Widgets']
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Widget categories retrieved successfully'
        ]);
    }

    /**
     * Get widget types
     */
    public function widgetTypes(): JsonResponse
    {
        $widgetTypes = [
            'line_chart' => [
                'name' => 'Line Chart',
                'description' => 'Time series and trend visualization',
                'category' => 'chart',
                'data_format' => 'time_series',
                'config_options' => ['colors', 'axes', 'legend', 'grid']
            ],
            'bar_chart' => [
                'name' => 'Bar Chart',
                'description' => 'Categorical data comparison',
                'category' => 'chart',
                'data_format' => 'categorical',
                'config_options' => ['colors', 'axes', 'legend', 'orientation']
            ],
            'pie_chart' => [
                'name' => 'Pie Chart',
                'description' => 'Proportional data visualization',
                'category' => 'chart',
                'data_format' => 'proportional',
                'config_options' => ['colors', 'legend', 'labels', 'donut_mode']
            ],
            'gauge' => [
                'name' => 'Gauge',
                'description' => 'Single value with range indicator',
                'category' => 'gauge',
                'data_format' => 'single_value',
                'config_options' => ['min_max', 'colors', 'thresholds', 'units']
            ],
            'heat_map' => [
                'name' => 'Heat Map',
                'description' => 'Spatial intensity visualization',
                'category' => 'map',
                'data_format' => 'spatial',
                'config_options' => ['color_scale', 'intensity_levels', 'overlay_options']
            ],
            'table' => [
                'name' => 'Table',
                'description' => 'Structured data display',
                'category' => 'table',
                'data_format' => 'tabular',
                'config_options' => ['columns', 'sorting', 'filtering', 'pagination']
            ],
            'metric_card' => [
                'name' => 'Metric Card',
                'description' => 'Key metric with trend indicator',
                'category' => 'metric',
                'data_format' => 'single_value',
                'config_options' => ['colors', 'trend_indicator', 'comparison', 'units']
            ],
            'map' => [
                'name' => 'Map',
                'description' => 'Geographic or floor plan visualization',
                'category' => 'map',
                'data_format' => 'geographic',
                'config_options' => ['zoom_level', 'markers', 'overlays', 'interaction']
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $widgetTypes,
            'message' => 'Widget types retrieved successfully'
        ]);
    }

    /**
     * Get data sources for widgets
     */
    public function dataSources(): JsonResponse
    {
        $dataSources = [
            'space_utilization_snapshots' => [
                'name' => 'Space Utilization Snapshots',
                'description' => 'Historical space utilization data',
                'data_format' => 'time_series',
                'available_fields' => ['utilization_percentage', 'occupied_locations', 'density_per_sqm'],
                'update_frequency' => 'real_time'
            ],
            'capacity_tracking' => [
                'name' => 'Capacity Tracking',
                'description' => 'Capacity utilization and forecasting',
                'data_format' => 'time_series',
                'available_fields' => ['capacity_utilization', 'peak_utilization', 'available_capacity'],
                'update_frequency' => 'hourly'
            ],
            'warehouse_equipment' => [
                'name' => 'Warehouse Equipment',
                'description' => 'Equipment status and metrics',
                'data_format' => 'current_state',
                'available_fields' => ['status', 'battery_level', 'last_activity', 'current_zone'],
                'update_frequency' => 'real_time'
            ],
            'equipment_movements' => [
                'name' => 'Equipment Movements',
                'description' => 'Equipment tracking and movement data',
                'data_format' => 'event_stream',
                'available_fields' => ['distance_traveled', 'speed', 'movement_time', 'zones_visited'],
                'update_frequency' => 'real_time'
            ],
            'aisle_efficiency_metrics' => [
                'name' => 'Aisle Efficiency Metrics',
                'description' => 'Aisle performance data',
                'data_format' => 'time_series',
                'available_fields' => ['efficiency_score', 'pick_density', 'accessibility_score'],
                'update_frequency' => 'daily'
            ],
            'heat_map_data' => [
                'name' => 'Heat Map Data',
                'description' => 'Activity and utilization heat maps',
                'data_format' => 'spatial',
                'available_fields' => ['intensity', 'coordinates', 'map_type', 'intensity_level'],
                'update_frequency' => 'real_time'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $dataSources,
            'message' => 'Data sources retrieved successfully'
        ]);
    }

    /**
     * Generate widget configuration template
     */
    public function configTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widget_type' => 'required|in:line_chart,bar_chart,pie_chart,gauge,heat_map,table,metric_card,map',
            'data_source' => 'required|string'
        ]);

        $template = $this->generateConfigTemplate($validated['widget_type'], $validated['data_source']);

        return response()->json([
            'success' => true,
            'data' => $template,
            'message' => 'Widget configuration template generated successfully'
        ]);
    }

    /**
     * Validate widget configuration
     */
    public function validateConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widget_code' => 'required|exists:widget_library,code',
            'config' => 'required|array'
        ]);

        $widget = WidgetLibrary::where('code', $validated['widget_code'])->first();
        
        $validationResult = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => []
        ];

        try {
            $this->validateWidgetConfig($widget, $validated['config']);
        } catch (\Exception $e) {
            $validationResult['is_valid'] = false;
            $validationResult['errors'][] = $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'data' => $validationResult,
            'message' => 'Widget configuration validated'
        ]);
    }

    /**
     * Get widget preview data
     */
    public function preview(WidgetLibrary $widget, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'config' => 'nullable|array',
            'data_filters' => 'nullable|array'
        ]);

        $config = array_merge($widget->default_config, $validated['config'] ?? []);
        $filters = $validated['data_filters'] ?? [];

        // Generate sample data based on widget type and configuration
        $previewData = $this->generatePreviewData($widget, $config, $filters);

        return response()->json([
            'success' => true,
            'data' => [
                'widget_info' => [
                    'name' => $widget->name,
                    'type' => $widget->widget_type,
                    'category' => $widget->category
                ],
                'config' => $config,
                'preview_data' => $previewData,
                'component_path' => $widget->component_path
            ],
            'message' => 'Widget preview generated successfully'
        ]);
    }

    /**
     * Clone an existing widget
     */
    public function clone(WidgetLibrary $widget, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:widget_library,code',
            'modify_config' => 'nullable|boolean'
        ]);

        $clonedData = $widget->toArray();
        unset($clonedData['id'], $clonedData['created_at'], $clonedData['updated_at']);

        $clonedData['name'] = $validated['name'];
        $clonedData['code'] = $validated['code'];
        $clonedData['is_public'] = false; // Cloned widgets are private by default

        $clonedWidget = WidgetLibrary::create($clonedData);

        return response()->json([
            'success' => true,
            'data' => $clonedWidget,
            'message' => 'Widget cloned successfully'
        ], 201);
    }

    // Private helper methods

    private function validateConfigSchema(array $configSchema): void
    {
        $requiredKeys = ['type', 'required'];
        $validTypes = ['string', 'integer', 'boolean', 'array', 'object'];

        foreach ($configSchema as $key => $schema) {
            // Check required keys
            foreach ($requiredKeys as $requiredKey) {
                if (!isset($schema[$requiredKey])) {
                    throw new \InvalidArgumentException("Config schema for '{$key}' is missing required key: {$requiredKey}");
                }
            }

            // Validate type
            if (!in_array($schema['type'], $validTypes)) {
                throw new \InvalidArgumentException("Invalid config schema type '{$schema['type']}' for key '{$key}'");
            }
        }
    }

    private function validateDataRequirements(array $dataRequirements): void
    {
        $validRequirements = [
            'utilization_percentage', 'capacity_data', 'equipment_positions', 'equipment_status',
            'time_series', 'spatial_data', 'tabular_data', 'single_value', 'categorical_data'
        ];

        foreach ($dataRequirements as $requirement) {
            if (!in_array($requirement, $validRequirements)) {
                throw new \InvalidArgumentException("Invalid data requirement: {$requirement}");
            }
        }
    }

    private function generateConfigTemplate(string $widgetType, string $dataSource): array
    {
        $baseTemplate = [
            'title' => [
                'type' => 'string',
                'required' => true,
                'default' => 'Widget Title',
                'description' => 'Display title for the widget'
            ],
            'refresh_interval' => [
                'type' => 'integer',
                'required' => false,
                'default' => 300,
                'min' => 30,
                'max' => 3600,
                'description' => 'Auto-refresh interval in seconds'
            ]
        ];

        $typeSpecificConfig = match($widgetType) {
            'gauge' => [
                'min_value' => ['type' => 'integer', 'required' => true, 'default' => 0],
                'max_value' => ['type' => 'integer', 'required' => true, 'default' => 100],
                'unit' => ['type' => 'string', 'required' => false, 'default' => '%'],
                'color_ranges' => ['type' => 'array', 'required' => false]
            ],
            'line_chart' => [
                'x_axis_label' => ['type' => 'string', 'required' => false, 'default' => 'Time'],
                'y_axis_label' => ['type' => 'string', 'required' => false, 'default' => 'Value'],
                'show_legend' => ['type' => 'boolean', 'required' => false, 'default' => true],
                'line_color' => ['type' => 'string', 'required' => false, 'default' => '#007bff']
            ],
            'heat_map' => [
                'color_scale' => ['type' => 'array', 'required' => false],
                'intensity_levels' => ['type' => 'integer', 'required' => false, 'default' => 5],
                'show_legend' => ['type' => 'boolean', 'required' => false, 'default' => true]
            ],
            'table' => [
                'columns' => ['type' => 'array', 'required' => true],
                'sortable' => ['type' => 'boolean', 'required' => false, 'default' => true],
                'filterable' => ['type' => 'boolean', 'required' => false, 'default' => false],
                'page_size' => ['type' => 'integer', 'required' => false, 'default' => 10]
            ],
            default => []
        };

        return array_merge($baseTemplate, $typeSpecificConfig);
    }

    private function validateWidgetConfig(WidgetLibrary $widget, array $config): void
    {
        $schema = $widget->config_schema;

        foreach ($schema as $key => $schemaConfig) {
            $isRequired = $schemaConfig['required'] ?? false;
            $configValue = $config[$key] ?? null;

            // Check required fields
            if ($isRequired && $configValue === null) {
                throw new \InvalidArgumentException("Required configuration key '{$key}' is missing");
            }

            // Validate type if value is provided
            if ($configValue !== null) {
                $this->validateConfigValue($key, $configValue, $schemaConfig);
            }
        }
    }

    private function validateConfigValue(string $key, $value, array $schema): void
    {
        $expectedType = $schema['type'];

        switch ($expectedType) {
            case 'string':
                if (!is_string($value)) {
                    throw new \InvalidArgumentException("Config key '{$key}' must be a string");
                }
                break;
            
            case 'integer':
                if (!is_int($value)) {
                    throw new \InvalidArgumentException("Config key '{$key}' must be an integer");
                }
                
                if (isset($schema['min']) && $value < $schema['min']) {
                    throw new \InvalidArgumentException("Config key '{$key}' must be at least {$schema['min']}");
                }
                
                if (isset($schema['max']) && $value > $schema['max']) {
                    throw new \InvalidArgumentException("Config key '{$key}' must be at most {$schema['max']}");
                }
                break;
            
            case 'boolean':
                if (!is_bool($value)) {
                    throw new \InvalidArgumentException("Config key '{$key}' must be a boolean");
                }
                break;
            
            case 'array':
                if (!is_array($value)) {
                    throw new \InvalidArgumentException("Config key '{$key}' must be an array");
                }
                break;
        }
    }

    private function generatePreviewData(WidgetLibrary $widget, array $config, array $filters): array
    {
        return match($widget->widget_type) {
            'gauge' => [
                'value' => rand(0, 100),
                'min' => $config['min_value'] ?? 0,
                'max' => $config['max_value'] ?? 100,
                'unit' => $config['unit'] ?? '%'
            ],
            'line_chart' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'datasets' => [
                    [
                        'label' => 'Utilization',
                        'data' => [65, 72, 68, 75, 82, 78],
                        'color' => $config['line_color'] ?? '#007bff'
                    ]
                ]
            ],
            'bar_chart' => [
                'labels' => ['Zone A', 'Zone B', 'Zone C', 'Zone D'],
                'datasets' => [
                    [
                        'label' => 'Utilization %',
                        'data' => [85, 72, 91, 68]
                    ]
                ]
            ],
            'pie_chart' => [
                'labels' => ['Storage', 'Picking', 'Receiving', 'Shipping'],
                'data' => [45, 25, 15, 15]
            ],
            'table' => [
                'columns' => [
                    ['field' => 'zone', 'label' => 'Zone'],
                    ['field' => 'utilization', 'label' => 'Utilization %'],
                    ['field' => 'status', 'label' => 'Status']
                ],
                'rows' => [
                    ['zone' => 'Zone A', 'utilization' => 85, 'status' => 'Active'],
                    ['zone' => 'Zone B', 'utilization' => 72, 'status' => 'Active'],
                    ['zone' => 'Zone C', 'utilization' => 91, 'status' => 'Active']
                ]
            ],
            'heat_map' => [
                'data_points' => [
                    ['x' => 10, 'y' => 20, 'intensity' => 0.8],
                    ['x' => 30, 'y' => 40, 'intensity' => 0.6],
                    ['x' => 50, 'y' => 30, 'intensity' => 0.9],
                    ['x' => 70, 'y' => 60, 'intensity' => 0.4]
                ]
            ],
            'metric_card' => [
                'value' => 87.5,
                'unit' => '%',
                'trend' => 'up',
                'change' => 2.3,
                'comparison_period' => 'vs last week'
            ],
            default => ['message' => 'Preview not available for this widget type']
        };
    }
}