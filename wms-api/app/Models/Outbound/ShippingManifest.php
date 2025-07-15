<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShippingCarrier;
use App\Models\User;

class ShippingManifest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'manifest_number',
        'shipping_carrier_id',
        'manifest_date',
        'shipment_ids',
        'total_shipments',
        'total_pieces',
        'total_weight_kg',
        'total_declared_value',
        'manifest_status',
        'manifest_data',
        'closed_at',
        'transmitted_at',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'manifest_date' => 'date',
        'shipment_ids' => 'json',
        'total_shipments' => 'integer',
        'total_pieces' => 'integer',
        'total_weight_kg' => 'decimal:3',
        'total_declared_value' => 'decimal:2',
        'closed_at' => 'datetime',
        'transmitted_at' => 'datetime'
    ];

    /**
     * Get the shipping carrier that owns the manifest.
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }

    /**
     * Get the user who created the manifest.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the shipments for the manifest.
     */
    public function shipments()
    {
        $shipmentIds = json_decode($this->shipment_ids, true);
        
        if (is_array($shipmentIds)) {
            return Shipment::whereIn('id', $shipmentIds)->get();
        }
        
        return collect();
    }

    /**
     * Scope a query to only include open manifests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        return $query->where('manifest_status', 'open');
    }

    /**
     * Scope a query to only include closed manifests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClosed($query)
    {
        return $query->where('manifest_status', 'closed');
    }

    /**
     * Scope a query to only include transmitted manifests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTransmitted($query)
    {
        return $query->where('manifest_status', 'transmitted');
    }

    /**
     * Scope a query to only include confirmed manifests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConfirmed($query)
    {
        return $query->where('manifest_status', 'confirmed');
    }

    /**
     * Scope a query to only include manifests for a specific carrier.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $carrierId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCarrier($query, $carrierId)
    {
        return $query->where('shipping_carrier_id', $carrierId);
    }

    /**
     * Scope a query to only include manifests for a specific date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('manifest_date', $date);
    }

    /**
     * Check if the manifest can be closed.
     *
     * @return bool
     */
    public function canBeClosed()
    {
        return $this->manifest_status === 'open';
    }

    /**
     * Check if the manifest can be transmitted.
     *
     * @return bool
     */
    public function canBeTransmitted()
    {
        return $this->manifest_status === 'closed';
    }

    /**
     * Calculate the average weight per piece.
     *
     * @return float|null
     */
    public function getAverageWeightPerPiece()
    {
        if ($this->total_pieces > 0) {
            return $this->total_weight_kg / $this->total_pieces;
        }
        
        return null;
    }

    /**
     * Calculate the average value per piece.
     *
     * @return float|null
     */
    public function getAverageValuePerPiece()
    {
        if ($this->total_pieces > 0 && $this->total_declared_value !== null) {
            return $this->total_declared_value / $this->total_pieces;
        }
        
        return null;
    }
}