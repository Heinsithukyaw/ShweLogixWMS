<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingMaterial extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'material_code',
        'material_name',
        'material_type',
        'unit_of_measure',
        'cost_per_unit',
        'current_stock',
        'min_stock_level',
        'is_active',
        'supplier'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'cost_per_unit' => 'decimal:4',
        'current_stock' => 'integer',
        'min_stock_level' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Scope a query to only include active packing materials.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include packing materials that are low in stock.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= min_stock_level');
    }

    /**
     * Check if the packing material is low in stock.
     *
     * @return bool
     */
    public function isLowStock()
    {
        return $this->current_stock <= $this->min_stock_level;
    }

    /**
     * Calculate the total value of the current stock.
     *
     * @return float
     */
    public function getTotalStockValue()
    {
        return $this->current_stock * $this->cost_per_unit;
    }

    /**
     * Update the stock level.
     *
     * @param int $quantity
     * @return void
     */
    public function updateStock($quantity)
    {
        $this->current_stock += $quantity;
        $this->save();
    }
}