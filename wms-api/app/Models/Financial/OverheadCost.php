<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OverheadCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'cost_category_id',
        'amount',
        'frequency',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the cost category that owns this overhead cost.
     */
    public function costCategory()
    {
        return $this->belongsTo(CostCategory::class);
    }
}