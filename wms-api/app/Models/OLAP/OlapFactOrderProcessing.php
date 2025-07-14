<?php

namespace App\Models\OLAP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;

class OlapFactOrderProcessing extends Model
{
    use HasFactory;

    protected $table = 'olap_fact_order_processing';

    protected $fillable = [
        'order_id',
        'order_number',
        'customer_id',
        'warehouse_id',
        'order_type',
        'order_status',
        'line_count',
        'item_count',
        'total_value',
        'currency_code',
        'order_date',
        'processing_start',
        'processing_complete',
        'shipping_date',
        'delivery_date',
        'processing_time_minutes',
    ];

    protected $casts = [
        'line_count' => 'integer',
        'item_count' => 'integer',
        'total_value' => 'float',
        'processing_time_minutes' => 'float',
        'order_date' => 'datetime',
        'processing_start' => 'datetime',
        'processing_complete' => 'datetime',
        'shipping_date' => 'datetime',
        'delivery_date' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function dimensionWarehouse()
    {
        return $this->belongsTo(OlapDimWarehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function dimensionCustomer()
    {
        return $this->belongsTo(OlapDimCustomer::class, 'customer_id', 'customer_id');
    }

    public function dimensionTime()
    {
        return $this->belongsTo(OlapDimTime::class, 'order_date', 'date');
    }
}