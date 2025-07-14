<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PredictiveModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'model_type',
        'description',
        'model_parameters',
        'training_metrics',
        'accuracy',
        'is_active',
        'last_trained_at',
    ];

    protected $casts = [
        'model_parameters' => 'array',
        'training_metrics' => 'array',
        'accuracy' => 'float',
        'is_active' => 'boolean',
        'last_trained_at' => 'datetime',
    ];
}