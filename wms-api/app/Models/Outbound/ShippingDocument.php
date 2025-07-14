<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ShippingDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shipment_id',
        'document_type',
        'document_number',
        'document_data',
        'document_format',
        'document_metadata',
        'is_required',
        'is_generated',
        'generated_at',
        'generated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'document_metadata' => 'json',
        'is_required' => 'boolean',
        'is_generated' => 'boolean',
        'generated_at' => 'datetime'
    ];

    /**
     * Get the shipment that owns the shipping document.
     */
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the user who generated the document.
     */
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Scope a query to only include required documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope a query to only include generated documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGenerated($query)
    {
        return $query->where('is_generated', true);
    }

    /**
     * Scope a query to only include bill of lading documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBillOfLading($query)
    {
        return $query->where('document_type', 'bill_of_lading');
    }

    /**
     * Scope a query to only include packing slip documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePackingSlip($query)
    {
        return $query->where('document_type', 'packing_slip');
    }

    /**
     * Scope a query to only include commercial invoice documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommercialInvoice($query)
    {
        return $query->where('document_type', 'commercial_invoice');
    }

    /**
     * Scope a query to only include customs form documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustomsForm($query)
    {
        return $query->where('document_type', 'customs_form');
    }

    /**
     * Scope a query to only include hazmat form documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHazmatForm($query)
    {
        return $query->where('document_type', 'hazmat_form');
    }

    /**
     * Mark the document as generated.
     *
     * @param  int  $userId
     * @return bool
     */
    public function markAsGenerated($userId)
    {
        $this->is_generated = true;
        $this->generated_at = now();
        $this->generated_by = $userId;
        
        return $this->save();
    }

    /**
     * Get the document page count from metadata.
     *
     * @return int|null
     */
    public function getPageCount()
    {
        $metadata = json_decode($this->document_metadata, true);
        
        if (is_array($metadata) && isset($metadata['pages'])) {
            return $metadata['pages'];
        }
        
        return null;
    }

    /**
     * Get the document creation date from metadata.
     *
     * @return string|null
     */
    public function getCreationDate()
    {
        $metadata = json_decode($this->document_metadata, true);
        
        if (is_array($metadata) && isset($metadata['created_at'])) {
            return $metadata['created_at'];
        }
        
        return null;
    }
}