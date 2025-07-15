<?php

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_id',
        'name',
        'description',
        'filter_values',
        'field_selections',
        'grouping_selections',
        'sorting_selections',
        'chart_selections',
        'layout_customizations',
        'output_format',
        'is_favorite',
        'created_by',
        'shared_with'
    ];

    protected $casts = [
        'filter_values' => 'array',
        'field_selections' => 'array',
        'grouping_selections' => 'array',
        'sorting_selections' => 'array',
        'chart_selections' => 'array',
        'layout_customizations' => 'array',
        'is_favorite' => 'boolean'
    ];

    // Relationships
    public function template()
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    public function scheduledReports()
    {
        return $this->hasMany(ScheduledReport::class, 'custom_report_id');
    }

    public function executions()
    {
        return $this->hasMany(ReportExecution::class, 'custom_report_id');
    }

    public function permissions()
    {
        return $this->hasMany(ReportPermission::class, 'custom_report_id');
    }

    // Scopes
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeSharedWith($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('created_by', $userId)
              ->orWhereJsonContains('shared_with', $userId)
              ->orWhereHas('permissions', function($pq) use ($userId) {
                  $pq->where('user_id', $userId)
                    ->where('permission_type', 'view')
                    ->where('is_granted', true);
              });
        });
    }

    public function scopeByCategory($query, $category)
    {
        return $query->whereHas('template', function($q) use ($category) {
            $q->where('category', $category);
        });
    }

    // Methods
    public function execute($executionType = 'manual', $executedBy = null)
    {
        $execution = $this->executions()->create([
            'execution_type' => $executionType,
            'started_at' => now(),
            'status' => 'running',
            'output_format' => $this->output_format,
            'execution_parameters' => [
                'filter_values' => $this->filter_values,
                'field_selections' => $this->field_selections,
                'grouping_selections' => $this->grouping_selections,
                'sorting_selections' => $this->sorting_selections
            ],
            'executed_by' => $executedBy
        ]);

        try {
            $data = $this->generateReportData();
            $filePath = $this->exportToFile($data, $execution);
            
            $execution->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_records' => count($data),
                'execution_time_seconds' => now()->diffInSeconds($execution->started_at),
                'file_path' => $filePath,
                'file_size' => file_exists($filePath) ? filesize($filePath) : null
            ]);
            
        } catch (\Exception $e) {
            $execution->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage()
            ]);
            
            throw $e;
        }

        return $execution;
    }

    public function generateReportData()
    {
        // This would contain the actual data generation logic
        // For now, returning a placeholder
        return [
            'headers' => $this->field_selections,
            'data' => [],
            'summary' => [],
            'metadata' => [
                'generated_at' => now(),
                'filters_applied' => $this->filter_values,
                'total_records' => 0
            ]
        ];
    }

    public function exportToFile($data, $execution)
    {
        $fileName = $this->generateFileName($execution);
        $filePath = storage_path('app/reports/' . $fileName);
        
        // Ensure directory exists
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        switch ($this->output_format) {
            case 'pdf':
                return $this->exportToPdf($data, $filePath);
            case 'excel':
                return $this->exportToExcel($data, $filePath);
            case 'csv':
                return $this->exportToCsv($data, $filePath);
            default:
                throw new \InvalidArgumentException('Unsupported output format: ' . $this->output_format);
        }
    }

    private function generateFileName($execution)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $extension = $this->getFileExtension();
        return "{$this->name}_{$execution->id}_{$timestamp}.{$extension}";
    }

    private function getFileExtension()
    {
        switch ($this->output_format) {
            case 'pdf':
                return 'pdf';
            case 'excel':
                return 'xlsx';
            case 'csv':
                return 'csv';
            default:
                return 'txt';
        }
    }

    private function exportToPdf($data, $filePath)
    {
        // PDF export logic would go here
        // For now, creating a placeholder file
        file_put_contents($filePath, 'PDF content placeholder');
        return $filePath;
    }

    private function exportToExcel($data, $filePath)
    {
        // Excel export logic would go here
        // For now, creating a placeholder file
        file_put_contents($filePath, 'Excel content placeholder');
        return $filePath;
    }

    private function exportToCsv($data, $filePath)
    {
        $handle = fopen($filePath, 'w');
        
        // Write headers
        if (!empty($data['headers'])) {
            fputcsv($handle, $data['headers']);
        }
        
        // Write data rows
        foreach ($data['data'] as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        return $filePath;
    }

    public function schedule($scheduleConfig)
    {
        return $this->scheduledReports()->create($scheduleConfig);
    }

    public function share($userIds, $permissionLevel = 'view')
    {
        $sharedWith = is_array($this->shared_with) ? $this->shared_with : [];
        
        foreach ((array)$userIds as $userId) {
            if (!in_array($userId, $sharedWith)) {
                $sharedWith[] = $userId;
            }
            
            // Create permission record
            $this->permissions()->updateOrCreate([
                'user_id' => $userId,
                'permission_type' => $permissionLevel
            ], [
                'is_granted' => true,
                'granted_by' => auth()->id() ?? $this->created_by,
                'granted_at' => now()
            ]);
        }
        
        $this->update(['shared_with' => $sharedWith]);
    }

    public function unshare($userIds)
    {
        $sharedWith = is_array($this->shared_with) ? $this->shared_with : [];
        
        foreach ((array)$userIds as $userId) {
            $sharedWith = array_diff($sharedWith, [$userId]);
            
            // Remove permission records
            $this->permissions()->where('user_id', $userId)->delete();
        }
        
        $this->update(['shared_with' => array_values($sharedWith)]);
    }

    public function duplicate($newName = null)
    {
        $duplicate = $this->replicate();
        $duplicate->name = $newName ?? $this->name . ' (Copy)';
        $duplicate->is_favorite = false;
        $duplicate->shared_with = null;
        $duplicate->save();
        
        return $duplicate;
    }

    public function getLastExecution()
    {
        return $this->executions()->latest('started_at')->first();
    }

    public function getExecutionHistory($limit = 10)
    {
        return $this->executions()
            ->latest('started_at')
            ->limit($limit)
            ->get();
    }

    public function getAverageExecutionTime()
    {
        return $this->executions()
            ->where('status', 'completed')
            ->avg('execution_time_seconds');
    }

    public function getSuccessRate()
    {
        $total = $this->executions()->count();
        if ($total === 0) return 0;
        
        $successful = $this->executions()->where('status', 'completed')->count();
        return ($successful / $total) * 100;
    }
}