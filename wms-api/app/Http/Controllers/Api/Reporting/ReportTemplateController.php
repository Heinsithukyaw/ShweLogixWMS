<?php

namespace App\Http\Controllers\Api\Reporting;

use App\Http\Controllers\Controller;
use App\Models\Reporting\ReportTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportTemplateController extends Controller
{
    /**
     * Display a listing of report templates
     */
    public function index(Request $request): JsonResponse
    {
        $query = ReportTemplate::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
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

        $templates = $request->has('per_page') 
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
            'message' => 'Report templates retrieved successfully'
        ]);
    }

    /**
     * Store a newly created report template
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:report_templates,code',
            'category' => 'required|in:operational,financial,performance,compliance,custom',
            'description' => 'nullable|string',
            'data_sources' => 'required|array|min:1',
            'fields_config' => 'required|array|min:1',
            'filters_config' => 'nullable|array',
            'chart_config' => 'nullable|array',
            'output_formats' => 'required|string',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);

        $validated['is_public'] = $validated['is_public'] ?? true;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Validate fields configuration
        $this->validateFieldsConfig($validated['fields_config']);

        // Validate data sources
        $this->validateDataSources($validated['data_sources']);

        $template = ReportTemplate::create($validated);

        return response()->json([
            'success' => true,
            'data' => $template,
            'message' => 'Report template created successfully'
        ], 201);
    }

    /**
     * Display the specified report template
     */
    public function show(ReportTemplate $template): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $template,
            'message' => 'Report template retrieved successfully'
        ]);
    }

    /**
     * Update the specified report template
     */
    public function update(Request $request, ReportTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:100|unique:report_templates,code,' . $template->id,
            'category' => 'sometimes|in:operational,financial,performance,compliance,custom',
            'description' => 'sometimes|string',
            'data_sources' => 'sometimes|array|min:1',
            'fields_config' => 'sometimes|array|min:1',
            'filters_config' => 'sometimes|array',
            'chart_config' => 'sometimes|array',
            'output_formats' => 'sometimes|string',
            'is_public' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean'
        ]);

        // Validate fields configuration if provided
        if (isset($validated['fields_config'])) {
            $this->validateFieldsConfig($validated['fields_config']);
        }

        // Validate data sources if provided
        if (isset($validated['data_sources'])) {
            $this->validateDataSources($validated['data_sources']);
        }

        $template->update($validated);

        return response()->json([
            'success' => true,
            'data' => $template,
            'message' => 'Report template updated successfully'
        ]);
    }

    /**
     * Remove the specified report template
     */
    public function destroy(ReportTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report template deleted successfully'
        ]);
    }

    /**
     * Get available data sources for reports
     */
    public function dataSources(): JsonResponse
    {
        $dataSources = [
            'warehouse_zones' => [
                'name' => 'Warehouse Zones',
                'description' => 'Zone configuration and basic information',
                'fields' => ['id', 'name', 'code', 'type', 'total_area', 'usable_area', 'max_capacity', 'status']
            ],
            'space_utilization_snapshots' => [
                'name' => 'Space Utilization Snapshots',
                'description' => 'Historical space utilization data',
                'fields' => ['zone_id', 'snapshot_time', 'utilization_percentage', 'occupied_locations', 'total_locations', 'density_per_sqm']
            ],
            'capacity_tracking' => [
                'name' => 'Capacity Tracking',
                'description' => 'Capacity utilization and forecasting data',
                'fields' => ['zone_id', 'tracking_date', 'max_capacity', 'current_occupancy', 'capacity_utilization', 'peak_utilization']
            ],
            'warehouse_equipment' => [
                'name' => 'Warehouse Equipment',
                'description' => 'Equipment information and status',
                'fields' => ['id', 'name', 'code', 'type', 'status', 'current_zone_id', 'battery_level', 'last_activity']
            ],
            'equipment_movements' => [
                'name' => 'Equipment Movements',
                'description' => 'Equipment movement and tracking data',
                'fields' => ['equipment_id', 'movement_time', 'distance_traveled', 'speed', 'from_zone_id', 'to_zone_id']
            ],
            'aisle_efficiency_metrics' => [
                'name' => 'Aisle Efficiency Metrics',
                'description' => 'Aisle performance and efficiency data',
                'fields' => ['aisle_id', 'metric_date', 'pick_density', 'travel_distance', 'efficiency_score', 'accessibility_score']
            ],
            'heat_map_data' => [
                'name' => 'Heat Map Data',
                'description' => 'Activity and utilization heat map data',
                'fields' => ['zone_id', 'map_type', 'data_time', 'x_coordinate', 'y_coordinate', 'intensity', 'intensity_level']
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $dataSources,
            'message' => 'Available data sources retrieved successfully'
        ]);
    }

    /**
     * Get field types and validation rules
     */
    public function fieldTypes(): JsonResponse
    {
        $fieldTypes = [
            'string' => [
                'name' => 'Text',
                'description' => 'Text field for names, codes, descriptions',
                'validation_rules' => ['max_length']
            ],
            'integer' => [
                'name' => 'Integer',
                'description' => 'Whole numbers',
                'validation_rules' => ['min_value', 'max_value']
            ],
            'decimal' => [
                'name' => 'Decimal',
                'description' => 'Numbers with decimal places',
                'validation_rules' => ['min_value', 'max_value', 'decimal_places']
            ],
            'date' => [
                'name' => 'Date',
                'description' => 'Date values',
                'validation_rules' => ['date_format', 'min_date', 'max_date']
            ],
            'datetime' => [
                'name' => 'Date Time',
                'description' => 'Date and time values',
                'validation_rules' => ['datetime_format', 'min_datetime', 'max_datetime']
            ],
            'boolean' => [
                'name' => 'Boolean',
                'description' => 'True/false values',
                'validation_rules' => []
            ],
            'percentage' => [
                'name' => 'Percentage',
                'description' => 'Percentage values (0-100)',
                'validation_rules' => ['min_value', 'max_value']
            ],
            'currency' => [
                'name' => 'Currency',
                'description' => 'Monetary values',
                'validation_rules' => ['currency_code', 'decimal_places']
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $fieldTypes,
            'message' => 'Field types retrieved successfully'
        ]);
    }

    /**
     * Get filter types and configurations
     */
    public function filterTypes(): JsonResponse
    {
        $filterTypes = [
            'date_range' => [
                'name' => 'Date Range',
                'description' => 'Filter by date range',
                'config' => ['start_date', 'end_date', 'default_range']
            ],
            'dropdown' => [
                'name' => 'Dropdown',
                'description' => 'Single selection from predefined options',
                'config' => ['options', 'default_value', 'allow_empty']
            ],
            'multi_select' => [
                'name' => 'Multi Select',
                'description' => 'Multiple selections from predefined options',
                'config' => ['options', 'default_values', 'min_selections', 'max_selections']
            ],
            'number_range' => [
                'name' => 'Number Range',
                'description' => 'Filter by numeric range',
                'config' => ['min_value', 'max_value', 'step', 'default_min', 'default_max']
            ],
            'text_search' => [
                'name' => 'Text Search',
                'description' => 'Text-based search filter',
                'config' => ['search_fields', 'case_sensitive', 'exact_match']
            ],
            'checkbox' => [
                'name' => 'Checkbox',
                'description' => 'Boolean filter',
                'config' => ['default_value', 'label']
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $filterTypes,
            'message' => 'Filter types retrieved successfully'
        ]);
    }

    /**
     * Preview report template with sample data
     */
    public function preview(ReportTemplate $template, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filters' => 'nullable|array',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $limit = $validated['limit'] ?? 10;
        $filters = $validated['filters'] ?? [];

        // Generate sample data based on template configuration
        $sampleData = $this->generateSampleData($template, $filters, $limit);

        $preview = [
            'template_info' => [
                'name' => $template->name,
                'code' => $template->code,
                'category' => $template->category,
                'description' => $template->description
            ],
            'fields' => $template->fields_config,
            'sample_data' => $sampleData,
            'applied_filters' => $filters,
            'data_count' => count($sampleData),
            'preview_note' => 'This is sample data for preview purposes only'
        ];

        return response()->json([
            'success' => true,
            'data' => $preview,
            'message' => 'Report template preview generated successfully'
        ]);
    }

    /**
     * Validate report template configuration
     */
    public function validateTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_sources' => 'required|array|min:1',
            'fields_config' => 'required|array|min:1',
            'filters_config' => 'nullable|array',
            'chart_config' => 'nullable|array'
        ]);

        $validationResults = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => []
        ];

        try {
            // Validate data sources
            $this->validateDataSources($validated['data_sources']);

            // Validate fields configuration
            $this->validateFieldsConfig($validated['fields_config']);

            // Validate filters configuration
            if (isset($validated['filters_config'])) {
                $this->validateFiltersConfig($validated['filters_config']);
            }

            // Validate chart configuration
            if (isset($validated['chart_config'])) {
                $this->validateChartConfig($validated['chart_config']);
            }

        } catch (\Exception $e) {
            $validationResults['is_valid'] = false;
            $validationResults['errors'][] = $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'data' => $validationResults,
            'message' => 'Template configuration validated'
        ]);
    }

    /**
     * Get template categories
     */
    public function categories(): JsonResponse
    {
        $categories = [
            'operational' => [
                'name' => 'Operational',
                'description' => 'Day-to-day operational reports',
                'examples' => ['Space Utilization', 'Equipment Performance', 'Aisle Efficiency']
            ],
            'financial' => [
                'name' => 'Financial',
                'description' => 'Financial and cost-related reports',
                'examples' => ['Cost Analysis', 'Revenue Reports', 'Budget vs Actual']
            ],
            'performance' => [
                'name' => 'Performance',
                'description' => 'Performance metrics and KPI reports',
                'examples' => ['Warehouse Efficiency', 'Productivity Metrics', 'Trend Analysis']
            ],
            'compliance' => [
                'name' => 'Compliance',
                'description' => 'Regulatory and compliance reports',
                'examples' => ['Safety Reports', 'Audit Reports', 'Regulatory Compliance']
            ],
            'custom' => [
                'name' => 'Custom',
                'description' => 'Custom reports for specific needs',
                'examples' => ['Ad-hoc Analysis', 'Special Projects', 'Custom Dashboards']
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Template categories retrieved successfully'
        ]);
    }

    /**
     * Clone an existing template
     */
    public function clone(ReportTemplate $template, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:report_templates,code',
            'modify_config' => 'nullable|boolean'
        ]);

        $clonedData = $template->toArray();
        unset($clonedData['id'], $clonedData['created_at'], $clonedData['updated_at']);

        $clonedData['name'] = $validated['name'];
        $clonedData['code'] = $validated['code'];

        $clonedTemplate = ReportTemplate::create($clonedData);

        return response()->json([
            'success' => true,
            'data' => $clonedTemplate,
            'message' => 'Report template cloned successfully'
        ], 201);
    }

    // Private helper methods

    private function validateFieldsConfig(array $fieldsConfig): void
    {
        $requiredKeys = ['field', 'label', 'type', 'required'];
        $validTypes = ['string', 'integer', 'decimal', 'date', 'datetime', 'boolean', 'percentage', 'currency'];

        foreach ($fieldsConfig as $index => $field) {
            // Check required keys
            foreach ($requiredKeys as $key) {
                if (!isset($field[$key])) {
                    throw new \InvalidArgumentException("Field at index {$index} is missing required key: {$key}");
                }
            }

            // Validate field type
            if (!in_array($field['type'], $validTypes)) {
                throw new \InvalidArgumentException("Invalid field type '{$field['type']}' at index {$index}");
            }

            // Validate field name format
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field['field'])) {
                throw new \InvalidArgumentException("Invalid field name '{$field['field']}' at index {$index}");
            }
        }
    }

    private function validateDataSources(array $dataSources): void
    {
        $availableSources = [
            'warehouse_zones', 'space_utilization_snapshots', 'capacity_tracking',
            'warehouse_equipment', 'equipment_movements', 'aisle_efficiency_metrics', 'heat_map_data'
        ];

        foreach ($dataSources as $source) {
            if (!in_array($source, $availableSources)) {
                throw new \InvalidArgumentException("Invalid data source: {$source}");
            }
        }
    }

    private function validateFiltersConfig(array $filtersConfig): void
    {
        $validFilterTypes = ['date_range', 'dropdown', 'multi_select', 'number_range', 'text_search', 'checkbox'];

        foreach ($filtersConfig as $index => $filter) {
            if (!isset($filter['type']) || !in_array($filter['type'], $validFilterTypes)) {
                throw new \InvalidArgumentException("Invalid filter type at index {$index}");
            }

            if (!isset($filter['field']) || !isset($filter['label'])) {
                throw new \InvalidArgumentException("Filter at index {$index} is missing required field or label");
            }
        }
    }

    private function validateChartConfig(array $chartConfig): void
    {
        $validChartTypes = ['line_chart', 'bar_chart', 'pie_chart', 'gauge', 'table'];

        if (isset($chartConfig['default_chart']) && !in_array($chartConfig['default_chart'], $validChartTypes)) {
            throw new \InvalidArgumentException("Invalid default chart type: {$chartConfig['default_chart']}");
        }

        if (isset($chartConfig['available_charts'])) {
            foreach ($chartConfig['available_charts'] as $chartType) {
                if (!in_array($chartType, $validChartTypes)) {
                    throw new \InvalidArgumentException("Invalid chart type: {$chartType}");
                }
            }
        }
    }

    private function generateSampleData(ReportTemplate $template, array $filters, int $limit): array
    {
        $sampleData = [];
        
        // Generate sample data based on fields configuration
        for ($i = 0; $i < $limit; $i++) {
            $row = [];
            foreach ($template->fields_config as $field) {
                $row[$field['field']] = $this->generateSampleValue($field['type'], $field['field']);
            }
            $sampleData[] = $row;
        }

        return $sampleData;
    }

    private function generateSampleValue(string $type, string $fieldName): mixed
    {
        return match($type) {
            'string' => $this->generateSampleString($fieldName),
            'integer' => rand(1, 1000),
            'decimal' => round(rand(1, 10000) / 100, 2),
            'date' => now()->subDays(rand(0, 30))->format('Y-m-d'),
            'datetime' => now()->subHours(rand(0, 720))->format('Y-m-d H:i:s'),
            'boolean' => (bool)rand(0, 1),
            'percentage' => round(rand(0, 10000) / 100, 2),
            'currency' => round(rand(100, 100000) / 100, 2),
            default => 'Sample Value'
        };
    }

    private function generateSampleString(string $fieldName): string
    {
        $samples = [
            'name' => ['Zone A', 'Zone B', 'Storage Area 1', 'Picking Zone', 'Shipping Dock'],
            'code' => ['ZN-001', 'ZN-002', 'SA-001', 'PZ-001', 'SD-001'],
            'type' => ['storage', 'picking', 'receiving', 'shipping', 'staging'],
            'status' => ['active', 'inactive', 'maintenance'],
            'zone_name' => ['Main Storage', 'Fast Pick', 'Receiving Bay', 'Shipping Area'],
            'equipment_name' => ['Forklift 1', 'Conveyor A', 'Scanner 1', 'Robot 1'],
            'default' => ['Sample ' . rand(1, 100)]
        ];

        $key = strtolower($fieldName);
        $sampleArray = $samples[$key] ?? $samples['default'];
        
        return $sampleArray[array_rand($sampleArray)];
    }
}