<?php

namespace App\Models\Metrics;

use App\Models\BusinessParty;
use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricData extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_definition_id',
        'warehouse_id',
        'zone_id',
        'business_party_id',
        'value',
        'measurement_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'measurement_time' => 'datetime',
        'value' => 'decimal:4',
    ];

    /**
     * Get the metric definition that owns this metric data.
     */
    public function metricDefinition()
    {
        return $this->belongsTo(MetricDefinition::class);
    }

    /**
     * Get the warehouse that owns this metric data.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the zone that owns this metric data.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the business party that owns this metric data.
     */
    public function businessParty()
    {
        return $this->belongsTo(BusinessParty::class);
    }

    /**
     * Set the status based on thresholds before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->metricDefinition) {
                $definition = $model->metricDefinition;
                
                // Only set status if thresholds are defined
                if ($definition->threshold_warning !== null && $definition->threshold_critical !== null) {
                    if ($definition->higher_is_better) {
                        if ($model->value < $definition->threshold_critical) {
                            $model->status = 'critical';
                        } elseif ($model->value < $definition->threshold_warning) {
                            $model->status = 'warning';
                        } else {
                            $model->status = 'normal';
                        }
                    } else {
                        if ($model->value > $definition->threshold_critical) {
                            $model->status = 'critical';
                        } elseif ($model->value > $definition->threshold_warning) {
                            $model->status = 'warning';
                        } else {
                            $model->status = 'normal';
                        }
                    }
                }
            }
        });
    }
}