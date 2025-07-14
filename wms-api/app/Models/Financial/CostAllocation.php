<?php

namespace App\Models\Financial;

use App\Models\BusinessParty;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost_category_id',
        'business_party_id',
        'product_id',
        'warehouse_id',
        'allocated_amount',
        'allocation_method',
        'allocation_date',
        'notes',
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'allocated_amount' => 'decimal:2',
    ];

    /**
     * Get the cost category that owns this cost allocation.
     */
    public function costCategory()
    {
        return $this->belongsTo(CostCategory::class);
    }

    /**
     * Get the business party that owns this cost allocation.
     */
    public function businessParty()
    {
        return $this->belongsTo(BusinessParty::class);
    }

    /**
     * Get the product that owns this cost allocation.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse that owns this cost allocation.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}