<?php

namespace App\Models\OLAP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\User;

class OlapFactInventoryMovement extends Model
{
    use HasFactory;

    protected $table = 'olap_fact_inventory_movements';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'location_id',
        'user_id',
        'movement_type',
        'quantity',
        'uom_code',
        'movement_date',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'movement_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dimensionProduct()
    {
        return $this->belongsTo(OlapDimProduct::class, 'product_id', 'product_id');
    }

    public function dimensionWarehouse()
    {
        return $this->belongsTo(OlapDimWarehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function dimensionTime()
    {
        return $this->belongsTo(OlapDimTime::class, 'movement_date', 'date');
    }
}