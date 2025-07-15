<?php

namespace App\Models\ECommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ReturnOrderHistory extends Model
{
    use HasFactory;

    protected $table = 'return_order_history';

    protected $fillable = [
        'return_order_id',
        'status',
        'notes',
        'changed_by',
        'changed_at'
    ];

    protected $casts = [
        'changed_at' => 'datetime'
    ];

    // Relationships
    public function returnOrder()
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}