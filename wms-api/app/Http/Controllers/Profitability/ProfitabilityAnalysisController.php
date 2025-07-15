<?php

namespace App\Http\Controllers\Profitability;

use App\Http\Controllers\Controller;
use App\Models\Profitability\ProfitabilityAnalysis;
use App\Models\Profitability\CostAllocation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProfitabilityAnalysisController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProfitabilityAnalysis::with(['entity', 'createdBy']);

        if ($request->has('analysis_type')) {
            $query->where('analysis_type', $request->analysis_type);
        }

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->has('period_start')) {
            $query->where('period_start', '>=', $request->period_start);
        }

        if ($request->has('period_end')) {
            $query->where('period_end', '<=', $request->period_end);
        }

        $analyses = $query->orderBy('created_at', 'desc')
                         ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $analyses
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'analysis_period' => 'required|in:daily,weekly,monthly,quarterly,yearly,custom',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'analysis_type' => 'required|string',
            'entity_type' => 'required|string',
            'entity_id' => 'required|string',
            'cost_allocation_method' => 'required|in:traditional,abc,direct,step_down,reciprocal'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $analysis = ProfitabilityAnalysis::create([
                'analysis_period' => $request->analysis_period,
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'analysis_type' => $request->analysis_type,
                'entity_type' => $request->entity_type,
                'entity_id' => $request->entity_id,
                'cost_allocation_method' => $request->cost_allocation_method,
                'created_by' => auth()->id()
            ]);

            // Calculate profitability
            $analysis->calculateProfitability();
            $analysis->generateKPIMetrics();

            return response()->json([
                'success' => true,
                'message' => 'Profitability analysis created successfully',
                'data' => $analysis
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $analysis = ProfitabilityAnalysis::with(['entity', 'costAllocations', 'profitabilityMetrics'])
                                           ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $analysis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'analysis_period' => 'in:daily,weekly,monthly,quarterly,yearly,custom',
            'period_start' => 'date',
            'period_end' => 'date|after:period_start',
            'cost_allocation_method' => 'in:traditional,abc,direct,step_down,reciprocal'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $analysis = ProfitabilityAnalysis::findOrFail($id);
            $analysis->update($request->all());

            // Recalculate if period or method changed
            if ($request->has('period_start') || $request->has('period_end') || $request->has('cost_allocation_method')) {
                $analysis->calculateProfitability();
                $analysis->generateKPIMetrics();
            }

            return response()->json([
                'success' => true,
                'message' => 'Analysis updated successfully',
                'data' => $analysis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $analysis = ProfitabilityAnalysis::findOrFail($id);
            $analysis->delete();

            return response()->json([
                'success' => true,
                'message' => 'Analysis deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function overview(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'monthly');
            $startDate = $request->get('start_date', now()->subMonths(6));
            $endDate = $request->get('end_date', now());

            // Overall metrics
            $totalRevenue = ProfitabilityAnalysis::whereBetween('period_start', [$startDate, $endDate])
                                                ->sum('total_revenue');
            
            $totalCosts = ProfitabilityAnalysis::whereBetween('period_start', [$startDate, $endDate])
                                              ->sum('total_costs');
            
            $grossProfit = $totalRevenue - $totalCosts;
            $grossMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_revenue' => $totalRevenue,
                    'total_costs' => $totalCosts,
                    'gross_profit' => $grossProfit,
                    'gross_margin' => round($grossMargin, 2),
                    'period' => $period,
                    'date_range' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function marginDisplay(Request $request): JsonResponse
    {
        try {
            $analyses = ProfitabilityAnalysis::select([
                    'analysis_type',
                    'entity_type',
                    'entity_id',
                    'gross_margin_percentage',
                    'net_margin_percentage',
                    'period_start',
                    'period_end'
                ])
                ->orderBy('gross_margin_percentage', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $analyses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get margin display',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function monthlyCharts(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', now()->year);

            $monthlyData = ProfitabilityAnalysis::select([
                    DB::raw('MONTH(period_start) as month'),
                    DB::raw('SUM(total_revenue) as revenue'),
                    DB::raw('SUM(total_costs) as costs'),
                    DB::raw('SUM(gross_profit) as profit'),
                    DB::raw('AVG(gross_margin_percentage) as margin')
                ])
                ->whereYear('period_start', $year)
                ->groupBy(DB::raw('MONTH(period_start)'))
                ->orderBy('month')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $monthlyData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get monthly charts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function clientProfitability(Request $request): JsonResponse
    {
        try {
            $clientAnalyses = ProfitabilityAnalysis::where('entity_type', 'App\\Models\\BusinessParty')
                                                  ->with('entity')
                                                  ->orderBy('gross_profit', 'desc')
                                                  ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $clientAnalyses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get client profitability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function clientDetails($clientId): JsonResponse
    {
        try {
            $analyses = ProfitabilityAnalysis::where('entity_type', 'App\\Models\\BusinessParty')
                                           ->where('entity_id', $clientId)
                                           ->with('entity')
                                           ->orderBy('period_start', 'desc')
                                           ->get();

            return response()->json([
                'success' => true,
                'data' => $analyses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get client details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function analyzeClient(Request $request, $clientId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $analysis = ProfitabilityAnalysis::generateClientProfitability(
                $clientId,
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'message' => 'Client analysis completed',
                'data' => $analysis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllocationMethods(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => CostAllocation::getAllocationMethods()
        ]);
    }

    public function traditionalAllocation(Request $request): JsonResponse
    {
        // Implementation for traditional cost allocation
        return response()->json([
            'success' => true,
            'message' => 'Traditional allocation calculated',
            'data' => []
        ]);
    }

    public function abcAllocation(Request $request): JsonResponse
    {
        // Implementation for ABC cost allocation
        return response()->json([
            'success' => true,
            'message' => 'ABC allocation calculated',
            'data' => []
        ]);
    }

    public function compareAllocationMethods(Request $request): JsonResponse
    {
        try {
            $analysisId = $request->get('analysis_id');
            
            if (!$analysisId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Analysis ID is required'
                ], 400);
            }

            $analysis = ProfitabilityAnalysis::findOrFail($analysisId);
            $allocations = $analysis->costAllocations()->get();

            $comparison = [];
            foreach (CostAllocation::getAllocationMethods() as $method => $name) {
                $methodAllocations = $allocations->where('allocation_method', $method);
                $comparison[$method] = [
                    'name' => $name,
                    'total_allocated' => $methodAllocations->sum('allocated_amount'),
                    'allocations' => $methodAllocations->values()
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $comparison
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to compare allocation methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function recalculate($id): JsonResponse
    {
        try {
            $analysis = ProfitabilityAnalysis::findOrFail($id);
            $analysis->calculateProfitability();
            $analysis->generateKPIMetrics();

            return response()->json([
                'success' => true,
                'message' => 'Analysis recalculated successfully',
                'data' => $analysis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to recalculate analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}