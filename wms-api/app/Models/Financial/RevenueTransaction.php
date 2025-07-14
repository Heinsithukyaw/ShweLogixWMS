<?php

namespace App\Models\Financial;

use App\Models\BusinessParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RevenueTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'revenue_category_id',
        'business_party_id',
        'transaction_type',
        'amount',
        'transaction_date',
        'invoice_number',
        'payment_status',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the revenue category that owns this revenue transaction.
     */
    public function revenueCategory()
    {
        return $this->belongsTo(RevenueCategory::class);
    }

    /**
     * Get the business party that owns this revenue transaction.
     */
    public function businessParty()
    {
        return $this->belongsTo(BusinessParty::class);
    }
}