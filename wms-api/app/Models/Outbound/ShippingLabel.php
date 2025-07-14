<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ShippingLabel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shipment_id',
        'packed_carton_id',
        'label_type',
        'tracking_number',
        'label_data',
        'label_format',
        'label_metadata',
        'is_printed',
        'printed_at',
        'printed_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'label_metadata' => 'json',
        'is_printed' => 'boolean',
        'printed_at' => 'datetime'
    ];

    /**
     * Get the shipment that owns the shipping label.
     */
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the packed carton that owns the shipping label.
     */
    public function packedCarton()
    {
        return $this->belongsTo(PackedCarton::class);
    }

    /**
     * Get the user who printed the label.
     */
    public function printer()
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    /**
     * Scope a query to only include printed labels.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrinted($query)
    {
        return $query->where('is_printed', true);
    }

    /**
     * Scope a query to only include unprinted labels.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnprinted($query)
    {
        return $query->where('is_printed', false);
    }

    /**
     * Scope a query to only include shipping labels.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShipping($query)
    {
        return $query->where('label_type', 'shipping');
    }

    /**
     * Scope a query to only include return labels.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReturn($query)
    {
        return $query->where('label_type', 'return');
    }

    /**
     * Scope a query to only include hazmat labels.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHazmat($query)
    {
        return $query->where('label_type', 'hazmat');
    }

    /**
     * Mark the label as printed.
     *
     * @param  int  $userId
     * @return bool
     */
    public function markAsPrinted($userId)
    {
        $this->is_printed = true;
        $this->printed_at = now();
        $this->printed_by = $userId;
        
        return $this->save();
    }

    /**
     * Get the label dimensions from metadata.
     *
     * @return string|null
     */
    public function getLabelDimensions()
    {
        $metadata = json_decode($this->label_metadata, true);
        
        if (is_array($metadata) && isset($metadata['dimensions'])) {
            return $metadata['dimensions'];
        }
        
        return null;
    }

    /**
     * Get the label DPI from metadata.
     *
     * @return string|null
     */
    public function getLabelDpi()
    {
        $metadata = json_decode($this->label_metadata, true);
        
        if (is_array($metadata) && isset($metadata['dpi'])) {
            return $metadata['dpi'];
        }
        
        return null;
    }
}