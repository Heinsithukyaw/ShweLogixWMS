<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    use HasFactory;

    protected $fillable = [
        'uom_code',
        'uom_name',
        'base_uom_id',
        'conversion_factor',
        'description',
        'status',
    ];

    protected $with = ['baseUom'];

    public function baseUom()
    {
        return $this->belongsTo(BaseUom::class, 'base_uom_id');
    }
}
