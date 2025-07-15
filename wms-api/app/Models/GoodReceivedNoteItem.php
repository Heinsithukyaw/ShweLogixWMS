<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodReceivedNoteItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'grn_id',
        'product_id',
        'expected_qty',
        'received_qty',
        'uom_id',
        'location_id',
        'notes',
        'condition_status',
        'notes',
        'condition_status',
    ];

    public function good_received_note()
    {
        return $this->belongsTo(GoodReceivedNote::class, 'grn_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit_of_measure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function stagingLocation()
    {
        return $this->belongsTo(stagingLocation::class, 'location_id');
    }
}
