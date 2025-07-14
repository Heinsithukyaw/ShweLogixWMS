<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesOrder;
use App\Models\Employee;

class OutboundQualityCheck extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'check_number',
        'checkpoint_id',
        'sales_order_id',
        'shipment_id',
        'packed_carton_id',
        'inspector_id',
        'check_results',
        'overall_result',
        'quality_score',
        'inspection_notes',
        'corrective_actions',
        'requires_reinspection',
        'inspected_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'check_results' => 'json',
        'quality_score' => 'decimal:2',
        'requires_reinspection' => 'boolean',
        'inspected_at' => 'datetime'
    ];

    /**
     * Get the quality checkpoint that owns the check.
     */
    public function checkpoint()
    {
        return $this->belongsTo(QualityCheckpoint::class, 'checkpoint_id');
    }

    /**
     * Get the sales order that owns the quality check.
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the shipment that owns the quality check.
     */
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the packed carton that owns the quality check.
     */
    public function packedCarton()
    {
        return $this->belongsTo(PackedCarton::class);
    }

    /**
     * Get the employee who performed the inspection.
     */
    public function inspector()
    {
        return $this->belongsTo(Employee::class, 'inspector_id');
    }

    /**
     * Scope a query to only include passed quality checks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePassed($query)
    {
        return $query->where('overall_result', 'passed');
    }

    /**
     * Scope a query to only include failed quality checks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('overall_result', 'failed');
    }

    /**
     * Scope a query to only include conditional quality checks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConditional($query)
    {
        return $query->where('overall_result', 'conditional');
    }

    /**
     * Scope a query to only include checks that require reinspection.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequiresReinspection($query)
    {
        return $query->where('requires_reinspection', true);
    }
}