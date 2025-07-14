<?php

namespace App\Models\Dashboard;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WidgetLibrary extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'widget_library';

    protected $fillable = [
        'name',
        'code',
        'category',
        'widget_type',
        'description',
        'default_config',
        'config_schema',
        'data_requirements',
        'component_path',
        'supported_data_sources',
        'customization_options',
        'preview_image',
        'is_public',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'default_config' => 'array',
        'config_schema' => 'array',
        'data_requirements' => 'array',
        'supported_data_sources' => 'array',
        'customization_options' => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function dashboardWidgets()
    {
        return $this->hasMany(EnhancedDashboardWidget::class, 'widget_library_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('widget_type', $type);
    }

    public function scopeCompatibleWith($query, $dataSource)
    {
        return $query->whereJsonContains('supported_data_sources', $dataSource);
    }

    // Methods
    public function validateConfig($config)
    {
        $errors = [];
        $schema = $this->config_schema;
        
        if (!$schema) {
            return $errors;
        }
        
        foreach ($schema as $field => $rules) {
            $value = $config[$field] ?? null;
            
            // Check required fields
            if (($rules['required'] ?? false) && $value === null) {
                $errors[] = "Field '{$field}' is required";
                continue;
            }
            
            if ($value !== null) {
                // Validate data type
                if (isset($rules['type'])) {
                    if (!$this->validateDataType($value, $rules['type'])) {
                        $errors[] = "Field '{$field}' must be of type {$rules['type']}";
                    }
                }
                
                // Validate options
                if (isset($rules['options']) && !in_array($value, $rules['options'])) {
                    $errors[] = "Field '{$field}' must be one of: " . implode(', ', $rules['options']);
                }
                
                // Validate range
                if (isset($rules['min']) && $value < $rules['min']) {
                    $errors[] = "Field '{$field}' must be at least {$rules['min']}";
                }
                
                if (isset($rules['max']) && $value > $rules['max']) {
                    $errors[] = "Field '{$field}' must be at most {$rules['max']}";
                }
            }
        }
        
        return $errors;
    }

    private function validateDataType($value, $expectedType)
    {
        switch ($expectedType) {
            case 'string':
                return is_string($value);
            case 'integer':
                return is_int($value);
            case 'number':
                return is_numeric($value);
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value) || is_array($value);
            default:
                return true;
        }
    }

    public function createWidget($dashboardId, $config)
    {
        $validationErrors = $this->validateConfig($config);
        
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException('Configuration validation failed: ' . implode(', ', $validationErrors));
        }
        
        $widgetConfig = array_merge($this->default_config, $config);
        
        return EnhancedDashboardWidget::create([
            'dashboard_id' => $dashboardId,
            'widget_library_id' => $this->id,
            'title' => $config['title'] ?? $this->name,
            'widget_config' => $widgetConfig,
            'data_config' => $config['data_config'] ?? [],
            'display_config' => $config['display_config'] ?? [],
            'interaction_config' => $config['interaction_config'] ?? null
        ]);
    }

    public function getUsageStatistics()
    {
        return [
            'total_instances' => $this->dashboardWidgets()->count(),
            'active_instances' => $this->dashboardWidgets()->where('is_visible', true)->count(),
            'unique_dashboards' => $this->dashboardWidgets()->distinct('dashboard_id')->count(),
            'average_rating' => 0, // Could be implemented with a rating system
            'last_used' => $this->dashboardWidgets()->max('updated_at')
        ];
    }

    public function getCompatibleDataSources()
    {
        return $this->supported_data_sources ?? [];
    }

    public function supportsDataSource($dataSource)
    {
        return in_array($dataSource, $this->getCompatibleDataSources());
    }

    public function getCustomizationOptions()
    {
        return $this->customization_options ?? [];
    }

    public function getAvailableCustomizations($category = null)
    {
        $options = $this->getCustomizationOptions();
        
        if ($category) {
            return $options[$category] ?? [];
        }
        
        return $options;
    }

    public function generatePreview($config = null)
    {
        $previewConfig = $config ?? $this->default_config;
        
        // Generate preview data based on widget type
        switch ($this->widget_type) {
            case 'line_chart':
                return $this->generateLineChartPreview($previewConfig);
            case 'bar_chart':
                return $this->generateBarChartPreview($previewConfig);
            case 'pie_chart':
                return $this->generatePieChartPreview($previewConfig);
            case 'kpi_card':
                return $this->generateKpiCardPreview($previewConfig);
            case 'data_table':
                return $this->generateDataTablePreview($previewConfig);
            default:
                return ['type' => 'placeholder', 'message' => 'Preview not available'];
        }
    }

    private function generateLineChartPreview($config)
    {
        return [
            'type' => 'line_chart',
            'data' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                'datasets' => [
                    [
                        'label' => 'Sample Data',
                        'data' => [10, 20, 15, 25, 30],
                        'borderColor' => $config['color'] ?? '#007bff'
                    ]
                ]
            ],
            'options' => $config['chart_options'] ?? []
        ];
    }

    private function generateBarChartPreview($config)
    {
        return [
            'type' => 'bar_chart',
            'data' => [
                'labels' => ['A', 'B', 'C', 'D'],
                'datasets' => [
                    [
                        'label' => 'Sample Data',
                        'data' => [12, 19, 8, 15],
                        'backgroundColor' => $config['color'] ?? '#007bff'
                    ]
                ]
            ],
            'options' => $config['chart_options'] ?? []
        ];
    }

    private function generatePieChartPreview($config)
    {
        return [
            'type' => 'pie_chart',
            'data' => [
                'labels' => ['Red', 'Blue', 'Yellow'],
                'datasets' => [
                    [
                        'data' => [30, 50, 20],
                        'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56']
                    ]
                ]
            ],
            'options' => $config['chart_options'] ?? []
        ];
    }

    private function generateKpiCardPreview($config)
    {
        return [
            'type' => 'kpi_card',
            'value' => '1,234',
            'label' => 'Sample KPI',
            'trend' => '+5.2%',
            'trend_direction' => 'up',
            'color' => $config['color'] ?? '#28a745'
        ];
    }

    private function generateDataTablePreview($config)
    {
        return [
            'type' => 'data_table',
            'headers' => ['Name', 'Value', 'Status'],
            'rows' => [
                ['Item 1', '100', 'Active'],
                ['Item 2', '200', 'Inactive'],
                ['Item 3', '150', 'Active']
            ],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total_records' => 3
            ]
        ];
    }
}