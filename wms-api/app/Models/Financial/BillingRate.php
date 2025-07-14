<?php

namespace App\Models\Financial;

use App\Models\BusinessParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'business_party_id',
        'service_type',
        'rate',
        'unit',
        'minimum_charge',
        'effective_date',
        'expiry_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'rate' => 'decimal:2',
        'minimum_charge' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the business party that owns this billing rate.
     */
    public function businessParty()
    {
        return $this->belongsTo(BusinessParty::class);
    }
}