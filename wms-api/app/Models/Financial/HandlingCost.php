<?php

namespace App\Models\Financial;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HandlingCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'activity_type',
        'cost_per_unit',
        'unit_type',
        'warehouse_id',
        'effective_date',
        'expiry_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'cost_per_unit' => 'decimal:2',
    ];

    /**
     * Get the warehouse that owns this handling cost.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}