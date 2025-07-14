<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Models\User;

class QualityCheckpoint extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'checkpoint_code',
        'checkpoint_name',
        'warehouse_id',
        'zone_id',
        'checkpoint_type',
        'checkpoint_location',
        'is_mandatory',
        'quality_criteria',
        'checkpoint_sequence',
        'is_active',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_mandatory' => 'boolean',
        'quality_criteria' => 'json',
        'checkpoint_sequence' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Get the warehouse that owns the quality checkpoint.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the zone that owns the quality checkpoint.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the user who created the checkpoint.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the quality checks for the checkpoint.
     */
    public function qualityChecks()
    {
        return $this->hasMany(OutboundQualityCheck::class, 'checkpoint_id');
    }

    /**
     * Scope a query to only include active checkpoints.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include mandatory checkpoints.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope a query to only include packing checkpoints.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePacking($query)
    {
        return $query->where('checkpoint_type', 'packing');
    }

    /**
     * Scope a query to only include shipping checkpoints.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShipping($query)
    {
        return $query->where('checkpoint_type', 'shipping');
    }

    /**
     * Scope a query to only include final checkpoints.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFinal($query)
    {
        return $query->where('checkpoint_type', 'final');
    }
}