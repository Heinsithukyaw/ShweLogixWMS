<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_id',
        'is_active',
    ];

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(CostCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(CostCategory::class, 'parent_id');
    }

    /**
     * Get the overhead costs for this category.
     */
    public function overheadCosts()
    {
        return $this->hasMany(OverheadCost::class);
    }

    /**
     * Get the budget vs actual records for this category.
     */
    public function budgetVsActual()
    {
        return $this->hasMany(BudgetVsActual::class);
    }

    /**
     * Get the cost allocations for this category.
     */
    public function costAllocations()
    {
        return $this->hasMany(CostAllocation::class);
    }
}