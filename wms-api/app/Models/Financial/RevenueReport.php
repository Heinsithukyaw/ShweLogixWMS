<?php

namespace App\Models\Financial;

use App\Models\BusinessParty;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevenueReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_type',
        'period_start',
        'period_end',
        'total_revenue',
        'storage_revenue',
        'handling_revenue',
        'value_added_revenue',
        'other_revenue',
        'business_party_id',
        'warehouse_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_revenue' => 'decimal:2',
        'storage_revenue' => 'decimal:2',
        'handling_revenue' => 'decimal:2',
        'value_added_revenue' => 'decimal:2',
        'other_revenue' => 'decimal:2',
    ];

    /**
     * Get the business party that owns this revenue report.
     */
    public function businessParty()
    {
        return $this->belongsTo(BusinessParty::class);
    }

    /**
     * Get the warehouse that owns this revenue report.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Calculate total revenue before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_revenue = $model->storage_revenue + $model->handling_revenue + 
                                   $model->value_added_revenue + $model->other_revenue;
        });
    }
}