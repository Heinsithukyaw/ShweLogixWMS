<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RevenueCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    /**
     * Get the storage revenue rates for this category.
     */
    public function storageRevenueRates()
    {
        return $this->hasMany(StorageRevenueRate::class);
    }

    /**
     * Get the handling revenue rates for this category.
     */
    public function handlingRevenueRates()
    {
        return $this->hasMany(HandlingRevenueRate::class);
    }

    /**
     * Get the revenue transactions for this category.
     */
    public function revenueTransactions()
    {
        return $this->hasMany(RevenueTransaction::class);
    }
}