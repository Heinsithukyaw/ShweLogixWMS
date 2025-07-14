<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesOrder;
use App\Models\Employee;

class PackedCarton extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'carton_number',
        'pack_order_id',
        'sales_order_id',
        'carton_type_id',
        'packing_station_id',
        'packed_by',
        'carton_sequence',
        'gross_weight_kg',
        'net_weight_kg',
        'actual_length_cm',
        'actual_width_cm',
        'actual_height_cm',
        'packed_items',
        'materials_used',
        'carton_status',
        'packed_at',
        'verified_by',
        'verified_at',
        'packing_notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'gross_weight_kg' => 'decimal:3',
        'net_weight_kg' => 'decimal:3',
        'actual_length_cm' => 'decimal:2',
        'actual_width_cm' => 'decimal:2',
        'actual_height_cm' => 'decimal:2',
        'packed_items' => 'json',
        'materials_used' => 'json',
        'packed_at' => 'datetime',
        'verified_at' => 'datetime'
    ];

    /**
     * Get the pack order that owns the packed carton.
     */
    public function packOrder()
    {
        return $this->belongsTo(PackOrder::class);
    }

    /**
     * Get the sales order that owns the packed carton.
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the carton type that owns the packed carton.
     */
    public function cartonType()
    {
        return $this->belongsTo(CartonType::class);
    }

    /**
     * Get the packing station that owns the packed carton.
     */
    public function packingStation()
    {
        return $this->belongsTo(PackingStation::class);
    }

    /**
     * Get the employee who packed the carton.
     */
    public function packer()
    {
        return $this->belongsTo(Employee::class, 'packed_by');
    }

    /**
     * Get the employee who verified the carton.
     */
    public function verifier()
    {
        return $this->belongsTo(Employee::class, 'verified_by');
    }

    /**
     * Get the packing validations for the packed carton.
     */
    public function packingValidations()
    {
        return $this->hasMany(PackingValidation::class);
    }

    /**
     * Get the packing quality checks for the packed carton.
     */
    public function packingQualityChecks()
    {
        return $this->hasMany(PackingQualityCheck::class);
    }

    /**
     * Get the shipping label for the packed carton.
     */
    public function shippingLabel()
    {
        return $this->hasOne(ShippingLabel::class);
    }

    /**
     * Calculate the actual volume of the carton.
     *
     * @return float|null
     */
    public function calculateActualVolume()
    {
        if ($this->actual_length_cm && $this->actual_width_cm && $this->actual_height_cm) {
            return $this->actual_length_cm * $this->actual_width_cm * $this->actual_height_cm;
        }
        
        return null;
    }

    /**
     * Get the number of items in the carton.
     *
     * @return int
     */
    public function getItemCount()
    {
        $items = json_decode($this->packed_items, true);
        
        if (is_array($items)) {
            return count($items);
        }
        
        return 0;
    }

    /**
     * Scope a query to only include packed cartons.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePacked($query)
    {
        return $query->where('carton_status', 'packed');
    }

    /**
     * Scope a query to only include verified cartons.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->where('carton_status', 'verified');
    }

    /**
     * Scope a query to only include shipped cartons.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShipped($query)
    {
        return $query->where('carton_status', 'shipped');
    }

    /**
     * Scope a query to only include damaged cartons.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDamaged($query)
    {
        return $query->where('carton_status', 'damaged');
    }
}