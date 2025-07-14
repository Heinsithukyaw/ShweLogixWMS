<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryThresholdAlert extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'location_id',
        'threshold_type',
        'threshold_value',
        'current_value',
        'severity',
        'is_resolved',
        'resolved_at',
        'detected_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'threshold_value' => 'integer',
        'current_value' => 'integer',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'detected_at' => 'datetime',
    ];

    /**
     * Get the product associated with the alert.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location associated with the alert.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Scope a query to only include active (unresolved) alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope a query to only include resolved alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Scope a query to only include alerts for a specific product.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to only include alerts for a specific location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $locationId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope a query to only include alerts of a specific threshold type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $thresholdType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $thresholdType)
    {
        return $query->where('threshold_type', $thresholdType);
    }

    /**
     * Scope a query to only include alerts with a specific severity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $severity
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Mark the alert as resolved.
     *
     * @return bool
     */
    public function markAsResolved()
    {
        $this->is_resolved = true;
        $this->resolved_at = now();
        
        return $this->save();
    }
}