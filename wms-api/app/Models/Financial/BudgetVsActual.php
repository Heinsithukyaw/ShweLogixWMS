<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetVsActual extends Model
{
    use HasFactory;

    protected $table = 'budget_vs_actual';

    protected $fillable = [
        'cost_category_id',
        'budgeted_amount',
        'actual_amount',
        'variance_amount',
        'variance_percentage',
        'period_start',
        'period_end',
        'period_type',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'budgeted_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
    ];

    /**
     * Get the cost category that owns this budget vs actual record.
     */
    public function costCategory()
    {
        return $this->belongsTo(CostCategory::class);
    }

    /**
     * Calculate variance before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->budgeted_amount > 0) {
                $model->variance_amount = $model->actual_amount - $model->budgeted_amount;
                $model->variance_percentage = ($model->variance_amount / $model->budgeted_amount) * 100;
            }
        });
    }
}