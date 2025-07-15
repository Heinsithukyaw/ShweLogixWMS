<?php

namespace App\Models\Profitability;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusinessParty;
use App\Models\Product;
use App\Models\Warehouse;

class ProfitabilityAnalysis extends Model
{
    use HasFactory;

    protected $table = 'profitability_analyses';

    protected $fillable = [
        'analysis_period',
        'period_start',
        'period_end',
        'analysis_type',
        'entity_type',
        'entity_id',
        'total_revenue',
        'total_costs',
        'gross_profit',
        'gross_margin_percentage',
        'operating_costs',
        'net_profit',
        'net_margin_percentage',
        'cost_allocation_method',
        'cost_breakdown',
        'revenue_breakdown',
        'kpi_metrics',
        'analysis_notes',
        'created_by'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_revenue' => 'decimal:2',
        'total_costs' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'gross_margin_percentage' => 'decimal:2',
        'operating_costs' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'net_margin_percentage' => 'decimal:2',
        'cost_breakdown' => 'json',
        'revenue_breakdown' => 'json',
        'kpi_metrics' => 'json'
    ];

    // Relationships
    public function entity()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function costAllocations()
    {
        return $this->hasMany(CostAllocation::class);
    }

    public function profitabilityMetrics()
    {
        return $this->hasMany(ProfitabilityMetric::class);
    }

    // Scopes
    public function scopeByPeriod($query, $start, $end)
    {
        return $query->whereBetween('period_start', [$start, $end])
                    ->orWhereBetween('period_end', [$start, $end]);
    }

    public function scopeByEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeByAnalysisType($query, $analysisType)
    {
        return $query->where('analysis_type', $analysisType);
    }

    public function scopeMonthly($query)
    {
        return $query->where('analysis_period', 'monthly');
    }

    public function scopeQuarterly($query)
    {
        return $query->where('analysis_period', 'quarterly');
    }

    public function scopeYearly($query)
    {
        return $query->where('analysis_period', 'yearly');
    }

    // Methods
    public function calculateProfitability()
    {
        $this->gross_profit = $this->total_revenue - $this->total_costs;
        $this->net_profit = $this->gross_profit - $this->operating_costs;
        
        if ($this->total_revenue > 0) {
            $this->gross_margin_percentage = ($this->gross_profit / $this->total_revenue) * 100;
            $this->net_margin_percentage = ($this->net_profit / $this->total_revenue) * 100;
        }
        
        $this->save();
    }

    public function getROI()
    {
        $totalInvestment = $this->total_costs + $this->operating_costs;
        return $totalInvestment > 0 ? ($this->net_profit / $totalInvestment) * 100 : 0;
    }

    public function getROA()
    {
        // Return on Assets - would need asset data
        // Placeholder calculation
        return $this->net_margin_percentage * 0.8; // Simplified calculation
    }

    public function getBreakEvenPoint()
    {
        $fixedCosts = $this->getFixedCosts();
        $variableCostRatio = $this->getVariableCostRatio();
        
        if ($variableCostRatio < 1) {
            return $fixedCosts / (1 - $variableCostRatio);
        }
        
        return null; // Cannot break even
    }

    public function getContributionMargin()
    {
        $variableCosts = $this->getVariableCosts();
        return $this->total_revenue - $variableCosts;
    }

    public function getContributionMarginRatio()
    {
        if ($this->total_revenue > 0) {
            return ($this->getContributionMargin() / $this->total_revenue) * 100;
        }
        return 0;
    }

    private function getFixedCosts()
    {
        $costBreakdown = $this->cost_breakdown ?? [];
        return collect($costBreakdown)->where('type', 'fixed')->sum('amount');
    }

    private function getVariableCosts()
    {
        $costBreakdown = $this->cost_breakdown ?? [];
        return collect($costBreakdown)->where('type', 'variable')->sum('amount');
    }

    private function getVariableCostRatio()
    {
        return $this->total_revenue > 0 ? $this->getVariableCosts() / $this->total_revenue : 0;
    }

    public function generateKPIMetrics()
    {
        $metrics = [
            'gross_margin' => $this->gross_margin_percentage,
            'net_margin' => $this->net_margin_percentage,
            'roi' => $this->getROI(),
            'roa' => $this->getROA(),
            'contribution_margin' => $this->getContributionMargin(),
            'contribution_margin_ratio' => $this->getContributionMarginRatio(),
            'break_even_point' => $this->getBreakEvenPoint(),
            'cost_per_revenue_dollar' => $this->total_revenue > 0 ? $this->total_costs / $this->total_revenue : 0
        ];

        $this->kpi_metrics = $metrics;
        $this->save();

        return $metrics;
    }

    public static function generateClientProfitability($clientId, $startDate, $endDate)
    {
        $client = BusinessParty::findOrFail($clientId);
        
        // Calculate revenue from client orders
        $revenue = $client->salesOrders()
            ->whereBetween('order_date', [$startDate, $endDate])
            ->sum('total_amount');

        // Calculate costs associated with client
        $costs = self::calculateClientCosts($clientId, $startDate, $endDate);

        return self::create([
            'analysis_period' => 'custom',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'analysis_type' => 'client_profitability',
            'entity_type' => BusinessParty::class,
            'entity_id' => $clientId,
            'total_revenue' => $revenue,
            'total_costs' => $costs['total'],
            'cost_breakdown' => $costs['breakdown'],
            'created_by' => auth()->id()
        ]);
    }

    private static function calculateClientCosts($clientId, $startDate, $endDate)
    {
        // This would integrate with various cost tracking systems
        // For now, return mock data structure
        return [
            'total' => 0,
            'breakdown' => [
                ['category' => 'storage', 'amount' => 0, 'type' => 'fixed'],
                ['category' => 'handling', 'amount' => 0, 'type' => 'variable'],
                ['category' => 'shipping', 'amount' => 0, 'type' => 'variable']
            ]
        ];
    }
}