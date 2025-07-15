<?php

namespace App\Models\Financial;

use App\Models\BusinessParty;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HandlingRevenueRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'revenue_category_id',
        'activity_type',
        'rate_per_unit',
        'unit_type',
        'warehouse_id',
        'business_party_id',
        'effective_date',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'rate_per_unit' => 'decimal:2',
    ];

    /**
     * Get the revenue category that owns this handling revenue rate.
     */
    public function revenueCategory()
    {
        return $this->belongsTo(RevenueCategory::class);
    }

    /**
     * Get the warehouse that owns this handling revenue rate.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the business party that owns this handling revenue rate.
     */
    public function businessParty()
    {
        return $this->belongsTo(BusinessParty::class);
    }
}