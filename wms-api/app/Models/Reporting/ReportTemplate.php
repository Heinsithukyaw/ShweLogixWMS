<?php

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'category',
        'description',
        'data_sources',
        'fields_config',
        'filters_config',
        'grouping_config',
        'sorting_config',
        'chart_config',
        'layout_config',
        'output_formats',
        'is_public',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'data_sources' => 'array',
        'fields_config' => 'array',
        'filters_config' => 'array',
        'grouping_config' => 'array',
        'sorting_config' => 'array',
        'chart_config' => 'array',
        'layout_config' => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function customReports()
    {
        return $this->hasMany(CustomReport::class, 'template_id');
    }

    public function dataCache()
    {
        return $this->hasMany(ReportDataCache::class, 'template_id');
    }

    public function permissions()
    {
        return $this->hasMany(ReportPermission::class, 'template_id');
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

    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('is_public', true)
              ->orWhere('created_by', $userId)
              ->orWhereHas('permissions', function($pq) use ($userId) {
                  $pq->where('user_id', $userId)
                    ->where('permission_type', 'view')
                    ->where('is_granted', true);
              });
        });
    }

    // Methods
    public function getSupportedFormats()
    {
        return explode(',', $this->output_formats);
    }

    public function supportsFormat($format)
    {
        return in_array($format, $this->getSupportedFormats());
    }

    public function getAvailableFields()
    {
        return collect($this->fields_config)->pluck('label', 'field')->toArray();
    }

    public function getRequiredFields()
    {
        return collect($this->fields_config)
            ->where('required', true)
            ->pluck('field')
            ->toArray();
    }

    public function getAvailableFilters()
    {
        return collect($this->filters_config ?? [])->pluck('label', 'field')->toArray();
    }

    public function validateFieldSelection($selectedFields)
    {
        $availableFields = collect($this->fields_config)->pluck('field')->toArray();
        $requiredFields = $this->getRequiredFields();
        
        $errors = [];
        
        // Check if all required fields are selected
        foreach ($requiredFields as $required) {
            if (!in_array($required, $selectedFields)) {
                $errors[] = "Required field '{$required}' is missing";
            }
        }
        
        // Check if all selected fields are available
        foreach ($selectedFields as $field) {
            if (!in_array($field, $availableFields)) {
                $errors[] = "Field '{$field}' is not available in this template";
            }
        }
        
        return $errors;
    }

    public function validateFilterValues($filterValues)
    {
        $errors = [];
        $availableFilters = collect($this->filters_config ?? []);
        
        foreach ($filterValues as $field => $value) {
            $filterConfig = $availableFilters->firstWhere('field', $field);
            
            if (!$filterConfig) {
                $errors[] = "Filter '{$field}' is not available";
                continue;
            }
            
            // Validate based on filter type
            switch ($filterConfig['type']) {
                case 'date_range':
                    if (!$this->isValidDateRange($value)) {
                        $errors[] = "Invalid date range for filter '{$field}'";
                    }
                    break;
                    
                case 'number_range':
                    if (!$this->isValidNumberRange($value)) {
                        $errors[] = "Invalid number range for filter '{$field}'";
                    }
                    break;
                    
                case 'dropdown':
                case 'multi_select':
                    $validOptions = $filterConfig['options'] ?? [];
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            if (!in_array($v, $validOptions)) {
                                $errors[] = "Invalid option '{$v}' for filter '{$field}'";
                            }
                        }
                    } else {
                        if (!in_array($value, $validOptions)) {
                            $errors[] = "Invalid option '{$value}' for filter '{$field}'";
                        }
                    }
                    break;
            }
        }
        
        return $errors;
    }

    private function isValidDateRange($value)
    {
        if (!is_array($value) || !isset($value['start']) || !isset($value['end'])) {
            return false;
        }
        
        try {
            $start = \Carbon\Carbon::parse($value['start']);
            $end = \Carbon\Carbon::parse($value['end']);
            return $start->lte($end);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isValidNumberRange($value)
    {
        if (!is_array($value) || !isset($value['min']) || !isset($value['max'])) {
            return false;
        }
        
        return is_numeric($value['min']) && is_numeric($value['max']) && $value['min'] <= $value['max'];
    }

    public function createCustomReport($data)
    {
        $validationErrors = $this->validateFieldSelection($data['field_selections']);
        
        if (!empty($data['filter_values'])) {
            $filterErrors = $this->validateFilterValues($data['filter_values']);
            $validationErrors = array_merge($validationErrors, $filterErrors);
        }
        
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException('Validation errors: ' . implode(', ', $validationErrors));
        }
        
        return $this->customReports()->create($data);
    }

    public function getUsageStatistics()
    {
        return [
            'total_custom_reports' => $this->customReports()->count(),
            'active_custom_reports' => $this->customReports()->whereNull('deleted_at')->count(),
            'total_executions' => $this->customReports()
                ->withCount('executions')
                ->get()
                ->sum('executions_count'),
            'last_used' => $this->customReports()
                ->join('report_executions', 'custom_reports.id', '=', 'report_executions.custom_report_id')
                ->max('report_executions.started_at')
        ];
    }

    public function duplicate($newName = null)
    {
        $duplicate = $this->replicate();
        $duplicate->name = $newName ?? $this->name . ' (Copy)';
        $duplicate->code = $this->code . '_copy_' . time();
        $duplicate->is_public = false;
        $duplicate->save();
        
        return $duplicate;
    }
}