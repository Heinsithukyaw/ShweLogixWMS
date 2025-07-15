<?php

namespace App\Models\ECommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class OrderFulfillmentHistory extends Model
{
    use HasFactory;

    protected $table = 'order_fulfillment_history';

    protected $fillable = [
        'order_fulfillment_id',
        'status',
        'notes',
        'changed_by',
        'changed_at'
    ];

    protected $casts = [
        'changed_at' => 'datetime'
    ];

    // Relationships
    public function orderFulfillment()
    {
        return $this->belongsTo(OrderFulfillment::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}