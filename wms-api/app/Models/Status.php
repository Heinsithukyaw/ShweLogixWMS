<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;
    protected $fillable = [
        'status_code',
        'status_name',
        'entity_type',
        'category',
        'description',
        'created_by',
        'last_modified_by',
        'analytics_flag',
        'status'
    ];
}
