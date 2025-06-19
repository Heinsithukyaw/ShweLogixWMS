<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingDock extends Model
{
    use HasFactory;

    protected $fillable = [
        'dock_code',
        'dock_number',
        'dock_type',
        'zone_id',
        'status',
        'features',
        'additional_features',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
    
}
