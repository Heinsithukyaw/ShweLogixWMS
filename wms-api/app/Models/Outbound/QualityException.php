<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesOrder;
use App\Models\Product;
use App\Models\Employee;

class QualityException extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'exception_number',
        'quality_check_id',
        'sales_order_id',
        'shipment_id',
        'packed_carton_id',
        'product_id',
        'exception_type',
        'exception_description',
        'exception_status',
        'resolution_action',
        'resolution_notes',
        'reported_by',
        'resolved_by',
        'reported_at',
        'resolved_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    /**
     * Get the quality check that owns the exception.
     */
    public function qualityCheck()
    {
        return $this->belongsTo(OutboundQualityCheck::class, 'quality_check_id');
    }

    /**
     * Get the sales order that owns the exception.
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the shipment that owns the exception.
     */
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the packed carton that owns the exception.
     */
    public function packedCarton()
    {
        return $this->belongsTo(PackedCarton::class);
    }

    /**
     * Get the product that owns the exception.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the employee who reported the exception.
     */
    public function reporter()
    {
        return $this->belongsTo(Employee::class, 'reported_by');
    }

    /**
     * Get the employee who resolved the exception.
     */
    public function resolver()
    {
        return $this->belongsTo(Employee::class, 'resolved_by');
    }

    /**
     * Scope a query to only include open exceptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        return $query->where('exception_status', 'open');
    }

    /**
     * Scope a query to only include investigating exceptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInvestigating($query)
    {
        return $query->where('exception_status', 'investigating');
    }

    /**
     * Scope a query to only include resolved exceptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResolved($query)
    {
        return $query->where('exception_status', 'resolved');
    }

    /**
     * Scope a query to only include escalated exceptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEscalated($query)
    {
        return $query->where('exception_status', 'escalated');
    }

    /**
     * Scope a query to only include quality failure exceptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeQualityFailure($query)
    {
        return $query->where('exception_type', 'quality_failure');
    }

    /**
     * Scope a query to only include weight mismatch exceptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWeightMismatch($query)
    {
        return $query->where('exception_type', 'weight_mismatch');
    }

    /**
     * Scope a query to only include dimension mismatch exceptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDimensionMismatch($query)
    {
        return $query->where('exception_type', 'dimension_mismatch');
    }

    /**
     * Scope a query to only include damage exceptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDamage($query)
    {
        return $query->where('exception_type', 'damage');
    }

    /**
     * Calculate the resolution time in hours.
     *
     * @return float|null
     */
    public function getResolutionTimeHours()
    {
        if ($this->reported_at && $this->resolved_at) {
            return $this->reported_at->diffInHours($this->resolved_at);
        }
        
        return null;
    }

    /**
     * Check if the exception is overdue.
     *
     * @param  int  $hoursThreshold
     * @return bool
     */
    public function isOverdue($hoursThreshold = 24)
    {
        if ($this->exception_status === 'resolved') {
            return false;
        }
        
        return $this->reported_at->diffInHours(now()) > $hoursThreshold;
    }
}