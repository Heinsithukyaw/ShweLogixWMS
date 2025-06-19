<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingEquipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiving_equipment_code',
        'receiving_equipment_name',
        'receiving_equipment_type',
        'assigned_to_id',
        'last_maintenance_date',
        'notes',
        'days_since_maintenance',
        'version_control',
        'status'
    ];

    public function assigned_emp()
    {
        return $this->belongsTo(Employee::class, 'assigned_to_id');
    }

    
}
