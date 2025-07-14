<?php

namespace App\Models\Financial;

use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageRevenueRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'revenue_category_id',
        'warehouse_id',
        'zone_id',
        'rate_per_unit',
        'unit_type',
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
     * Get the revenue category that owns this storage revenue rate.
     */
    public function revenueCategory()
    {
        return $this->belongsTo(RevenueCategory::class);
    }

    /**
     * Get the warehouse that owns this storage revenue rate.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the zone that owns this storage revenue rate.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}