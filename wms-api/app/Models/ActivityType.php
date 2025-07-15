<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_type_code',
        'activity_type_name',
        'category',
        'default_duration',
        'description',
        'created_by',
        'last_modified_by',
        'ai_insight_flag',
        'status'
    ];
}
