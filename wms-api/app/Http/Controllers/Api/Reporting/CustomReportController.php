<?php

namespace App\Http\Controllers\Api\Reporting;

use App\Http\Controllers\Controller;
use App\Models\Reporting\CustomReport;
use App\Models\Reporting\ReportTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomReportController extends Controller
{
    /**
     * Display a listing of custom reports
     */
    public function index(Request $request): JsonResponse
    {
        $query = CustomReport::with(['template']);

        // Filter by template
        if ($request->has('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by created date range
        if ($request->has('created_from')) {
            $query->where('created_at', '>=', $request->created_from);
        }

        if ($request->has('created_to')) {
            $query->where('created_at', '<=', $request->created_to);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $reports = $request->has('per_page') 
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $reports,
            'message' => 'Custom reports retrieved successfully'
        ]);
    }

    /**
     * Generate a new custom report
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:report_templates,id',
            'name' => 'required|string|max:255',
            'parameters' => 'nullable|array',
            'filters' => 'nullable|array',
            'output_format' => 'required|in:json,csv,excel,pdf',
            'schedule_type' => 'nullable|in:once,daily,weekly,monthly',
            'schedule_config' => 'nullable|array'
        ]);

        $template = ReportTemplate::findOrFail($validated['template_id']);

        // Validate filters against template configuration
        $this->validateFilters($template, $validated['filters'] ?? []);

        // Create custom report record
        $report = CustomReport::create([
            'template_id' => $validated['template_id'],
            'name' => $validated['name'],
            'parameters' => $validated['parameters'] ?? [],
            'filters' => $validated['filters'] ?? [],
            'output_format' => $validated['output_format'],
            'schedule_type' => $validated['schedule_type'] ?? 'once',
            'schedule_config' => $validated['schedule_config'] ?? [],
            'status' => 'pending',
            'generated_by' => auth()->id() ?? 1, // Default user ID for now
        ]);

        // Generate the report data
        try {
            $reportData = $this->generateReportData($template, $validated['filters'] ?? [], $validated['parameters'] ?? []);
            
            $report->update([
                'status' => 'completed',
                'data' => $reportData,
                'generated_at' => now(),
                'row_count' => count($reportData['rows'] ?? [])
            ]);

            // Format output based on requested format
            $formattedOutput = $this->formatOutput($reportData, $validated['output_format']);
            
            $report->formatted_output = $formattedOutput;
            $report->save();

        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Report generation failed: ' . $e->getMessage()
            ], 500);
        }

        $report->load('template');

        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'Custom report generated successfully'
        ], 201);
    }

    /**
     * Display the specified custom report
     */
    public function show(CustomReport $report): JsonResponse
    {
        $report->load(['template']);

        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'Custom report retrieved successfully'
        ]);
    }

    /**
     * Update the specified custom report
     */
    public function update(Request $request, CustomReport $report): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parameters' => 'sometimes|array',
            'filters' => 'sometimes|array',
            'schedule_type' => 'sometimes|in:once,daily,weekly,monthly',
            'schedule_config' => 'sometimes|array'
        ]);

        // If filters are being updated, validate them
        if (isset($validated['filters'])) {
            $this->validateFilters($report->template, $validated['filters']);
        }

        $report->update($validated);
        $report->load('template');

        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'Custom report updated successfully'
        ]);
    }

    /**
     * Remove the specified custom report
     */
    public function destroy(CustomReport $report): JsonResponse
    {
        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Custom report deleted successfully'
        ]);
    }

    /**
     * Regenerate an existing custom report
     */
    public function regenerate(CustomReport $report): JsonResponse
    {
        $report->update([
            'status' => 'pending',
            'error_message' => null
        ]);

        try {
            $reportData = $this->generateReportData($report->template, $report->filters, $report->parameters);
            
            $report->update([
                'status' => 'completed',
                'data' => $reportData,
                'generated_at' => now(),
                'row_count' => count($reportData['rows'] ?? [])
            ]);

            // Format output
            $formattedOutput = $this->formatOutput($reportData, $report->output_format);
            $report->formatted_output = $formattedOutput;
            $report->save();

        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Report regeneration failed: ' . $e->getMessage()
            ], 500);
        }

        $report->load('template');

        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'Custom report regenerated successfully'
        ]);
    }

    /**
     * Download report in specified format
     */
    public function download(CustomReport $report, Request $request): JsonResponse
    {
        $format = $request->get('format', $report->output_format);

        if ($report->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Report is not ready for download'
            ], 400);
        }

        $downloadData = [
            'report_info' => [
                'name' => $report->name,
                'template' => $report->template->name,
                'generated_at' => $report->generated_at,
                'format' => $format
            ],
            'data' => $report->data,
            'formatted_output' => $this->formatOutput($report->data, $format)
        ];

        return response()->json([
            'success' => true,
            'data' => $downloadData,
            'message' => 'Report download data prepared successfully'
        ]);
    }

    /**
     * Get report statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        $stats = [
            'total_reports' => CustomReport::count(),
            'recent_reports' => CustomReport::where('created_at', '>=', $startDate)->count(),
            'completed_reports' => CustomReport::where('status', 'completed')->count(),
            'failed_reports' => CustomReport::where('status', 'failed')->count(),
            'pending_reports' => CustomReport::where('status', 'pending')->count(),
            'reports_by_template' => CustomReport::with('template')
                ->select('template_id', DB::raw('count(*) as count'))
                ->groupBy('template_id')
                ->get()
                ->map(function($item) {
                    return [
                        'template_name' => $item->template->name,
                        'count' => $item->count
                    ];
                }),
            'reports_by_format' => CustomReport::select('output_format', DB::raw('count(*) as count'))
                ->groupBy('output_format')
                ->get()
                ->pluck('count', 'output_format'),
            'daily_generation_trend' => CustomReport::where('created_at', '>=', $startDate)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Report statistics retrieved successfully'
        ]);
    }

    /**
     * Get scheduled reports
     */
    public function scheduled(Request $request): JsonResponse
    {
        $query = CustomReport::where('schedule_type', '!=', 'once')
            ->with(['template']);

        // Filter by schedule type
        if ($request->has('schedule_type')) {
            $query->where('schedule_type', $request->schedule_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $scheduledReports = $query->orderBy('created_at', 'desc')->get();

        // Add next run time for each scheduled report
        $scheduledReports->each(function($report) {
            $report->next_run_time = $this->calculateNextRunTime($report);
        });

        return response()->json([
            'success' => true,
            'data' => $scheduledReports,
            'message' => 'Scheduled reports retrieved successfully'
        ]);
    }

    /**
     * Execute scheduled reports
     */
    public function executeScheduled(): JsonResponse
    {
        $scheduledReports = CustomReport::where('schedule_type', '!=', 'once')
            ->where('status', '!=', 'pending')
            ->get();

        $executed = [];
        $failed = [];

        foreach ($scheduledReports as $report) {
            if ($this->shouldExecuteNow($report)) {
                try {
                    $this->regenerateReport($report);
                    $executed[] = $report->id;
                } catch (\Exception $e) {
                    $failed[] = [
                        'report_id' => $report->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'executed_count' => count($executed),
                'failed_count' => count($failed),
                'executed_reports' => $executed,
                'failed_reports' => $failed
            ],
            'message' => 'Scheduled reports execution completed'
        ]);
    }

    // Private helper methods

    private function validateFilters(ReportTemplate $template, array $filters): void
    {
        $templateFilters = collect($template->filters_config ?? []);
        
        foreach ($filters as $filterName => $filterValue) {
            $templateFilter = $templateFilters->firstWhere('field', $filterName);
            
            if (!$templateFilter) {
                throw new \InvalidArgumentException("Invalid filter: {$filterName}");
            }

            // Validate filter value based on type
            $this->validateFilterValue($templateFilter, $filterValue);
        }
    }

    private function validateFilterValue(array $filterConfig, $value): void
    {
        switch ($filterConfig['type']) {
            case 'date_range':
                if (!is_array($value) || !isset($value['start_date']) || !isset($value['end_date'])) {
                    throw new \InvalidArgumentException("Date range filter must have start_date and end_date");
                }
                break;
            
            case 'dropdown':
                $options = $filterConfig['options'] ?? [];
                if (!in_array($value, $options)) {
                    throw new \InvalidArgumentException("Invalid dropdown value: {$value}");
                }
                break;
            
            case 'multi_select':
                if (!is_array($value)) {
                    throw new \InvalidArgumentException("Multi-select filter must be an array");
                }
                $options = $filterConfig['options'] ?? [];
                foreach ($value as $item) {
                    if (!in_array($item, $options)) {
                        throw new \InvalidArgumentException("Invalid multi-select value: {$item}");
                    }
                }
                break;
            
            case 'number_range':
                if (!is_array($value) || !isset($value['min']) || !isset($value['max'])) {
                    throw new \InvalidArgumentException("Number range filter must have min and max values");
                }
                break;
        }
    }

    private function generateReportData(ReportTemplate $template, array $filters, array $parameters): array
    {
        $dataSources = $template->data_sources;
        $fieldsConfig = $template->fields_config;

        // Build query based on data sources and filters
        $query = $this->buildQuery($dataSources, $filters, $fieldsConfig);
        
        // Execute query and get results
        $results = $query->get();

        // Process results according to fields configuration
        $processedData = $this->processResults($results, $fieldsConfig);

        return [
            'template_info' => [
                'name' => $template->name,
                'code' => $template->code,
                'category' => $template->category
            ],
            'generation_info' => [
                'generated_at' => now(),
                'filters_applied' => $filters,
                'parameters' => $parameters
            ],
            'columns' => collect($fieldsConfig)->map(function($field) {
                return [
                    'field' => $field['field'],
                    'label' => $field['label'],
                    'type' => $field['type']
                ];
            })->toArray(),
            'rows' => $processedData,
            'summary' => [
                'total_rows' => count($processedData),
                'data_sources' => $dataSources
            ]
        ];
    }

    private function buildQuery(array $dataSources, array $filters, array $fieldsConfig): \Illuminate\Database\Query\Builder
    {
        // Start with the primary data source
        $primarySource = $dataSources[0];
        $query = DB::table($primarySource);

        // Join additional data sources if needed
        for ($i = 1; $i < count($dataSources); $i++) {
            $source = $dataSources[$i];
            $query = $this->joinDataSource($query, $primarySource, $source);
        }

        // Apply filters
        foreach ($filters as $filterName => $filterValue) {
            $query = $this->applyFilter($query, $filterName, $filterValue);
        }

        // Select only required fields
        $selectFields = collect($fieldsConfig)->pluck('field')->toArray();
        $query->select($selectFields);

        return $query;
    }

    private function joinDataSource(\Illuminate\Database\Query\Builder $query, string $primarySource, string $source): \Illuminate\Database\Query\Builder
    {
        // Define join relationships between data sources
        $joinMappings = [
            'warehouse_zones' => [
                'space_utilization_snapshots' => ['warehouse_zones.id', 'space_utilization_snapshots.zone_id'],
                'capacity_tracking' => ['warehouse_zones.id', 'capacity_tracking.zone_id'],
                'warehouse_equipment' => ['warehouse_zones.id', 'warehouse_equipment.current_zone_id']
            ],
            'warehouse_equipment' => [
                'equipment_movements' => ['warehouse_equipment.id', 'equipment_movements.equipment_id']
            ],
            'warehouse_aisles' => [
                'aisle_efficiency_metrics' => ['warehouse_aisles.id', 'aisle_efficiency_metrics.aisle_id']
            ]
        ];

        if (isset($joinMappings[$primarySource][$source])) {
            $joinCondition = $joinMappings[$primarySource][$source];
            $query->leftJoin($source, $joinCondition[0], '=', $joinCondition[1]);
        }

        return $query;
    }

    private function applyFilter(\Illuminate\Database\Query\Builder $query, string $filterName, $filterValue): \Illuminate\Database\Query\Builder
    {
        switch ($filterName) {
            case 'date_range':
                if (is_array($filterValue) && isset($filterValue['start_date'], $filterValue['end_date'])) {
                    $query->whereBetween('created_at', [$filterValue['start_date'], $filterValue['end_date']]);
                }
                break;
            
            case 'zone_type':
                $query->where('type', $filterValue);
                break;
            
            case 'equipment_type':
                $query->where('type', $filterValue);
                break;
            
            case 'status':
                $query->where('status', $filterValue);
                break;
            
            default:
                // Generic filter application
                if (is_array($filterValue)) {
                    $query->whereIn($filterName, $filterValue);
                } else {
                    $query->where($filterName, $filterValue);
                }
                break;
        }

        return $query;
    }

    private function processResults($results, array $fieldsConfig): array
    {
        return $results->map(function($row) use ($fieldsConfig) {
            $processedRow = [];
            
            foreach ($fieldsConfig as $field) {
                $value = $row->{$field['field']} ?? null;
                $processedRow[$field['field']] = $this->formatFieldValue($value, $field['type']);
            }
            
            return $processedRow;
        })->toArray();
    }

    private function formatFieldValue($value, string $type)
    {
        if ($value === null) return null;

        return match($type) {
            'decimal', 'percentage' => round((float)$value, 2),
            'currency' => number_format((float)$value, 2),
            'date' => Carbon::parse($value)->format('Y-m-d'),
            'datetime' => Carbon::parse($value)->format('Y-m-d H:i:s'),
            'boolean' => (bool)$value,
            default => $value
        };
    }

    private function formatOutput(array $data, string $format): array
    {
        switch ($format) {
            case 'csv':
                return $this->formatAsCsv($data);
            case 'excel':
                return $this->formatAsExcel($data);
            case 'pdf':
                return $this->formatAsPdf($data);
            default:
                return $data;
        }
    }

    private function formatAsCsv(array $data): array
    {
        $csv = [];
        
        // Header row
        if (!empty($data['columns'])) {
            $csv[] = collect($data['columns'])->pluck('label')->toArray();
        }
        
        // Data rows
        foreach ($data['rows'] ?? [] as $row) {
            $csv[] = array_values($row);
        }
        
        return [
            'format' => 'csv',
            'data' => $csv,
            'filename' => $this->generateFilename($data, 'csv')
        ];
    }

    private function formatAsExcel(array $data): array
    {
        return [
            'format' => 'excel',
            'sheets' => [
                'Report Data' => [
                    'columns' => $data['columns'] ?? [],
                    'rows' => $data['rows'] ?? []
                ]
            ],
            'filename' => $this->generateFilename($data, 'xlsx')
        ];
    }

    private function formatAsPdf(array $data): array
    {
        return [
            'format' => 'pdf',
            'template' => 'report_template',
            'data' => $data,
            'filename' => $this->generateFilename($data, 'pdf')
        ];
    }

    private function generateFilename(array $data, string $extension): string
    {
        $templateName = $data['template_info']['name'] ?? 'Report';
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return str_replace(' ', '_', $templateName) . '_' . $timestamp . '.' . $extension;
    }

    private function calculateNextRunTime(CustomReport $report): ?Carbon
    {
        if ($report->schedule_type === 'once') {
            return null;
        }

        $lastRun = $report->generated_at ?? $report->created_at;
        $scheduleConfig = $report->schedule_config ?? [];

        return match($report->schedule_type) {
            'daily' => Carbon::parse($lastRun)->addDay(),
            'weekly' => Carbon::parse($lastRun)->addWeek(),
            'monthly' => Carbon::parse($lastRun)->addMonth(),
            default => null
        };
    }

    private function shouldExecuteNow(CustomReport $report): bool
    {
        $nextRunTime = $this->calculateNextRunTime($report);
        
        return $nextRunTime && $nextRunTime->isPast();
    }

    private function regenerateReport(CustomReport $report): void
    {
        $report->update(['status' => 'pending']);

        $reportData = $this->generateReportData($report->template, $report->filters, $report->parameters);
        
        $report->update([
            'status' => 'completed',
            'data' => $reportData,
            'generated_at' => now(),
            'row_count' => count($reportData['rows'] ?? [])
        ]);

        $formattedOutput = $this->formatOutput($reportData, $report->output_format);
        $report->formatted_output = $formattedOutput;
        $report->save();
    }
}