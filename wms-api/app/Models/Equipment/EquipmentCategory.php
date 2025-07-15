<?php

namespace App\Models\Equipment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'description',
        'category_code',
        'requires_certification',
        'requires_inspection',
        'default_inspection_interval_days',
        'is_active'
    ];

    protected $casts = [
        'requires_certification' => 'boolean',
        'requires_inspection' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function equipment(): HasMany
    {
        return $this->hasMany(EquipmentRegistry::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequiresCertification($query)
    {
        return $query->where('requires_certification', true);
    }

    public function scopeRequiresInspection($query)
    {
        return $query->where('requires_inspection', true);
    }

    public function getEquipmentCountAttribute()
    {
        return $this->equipment()->count();
    }

    public function getActiveEquipmentCountAttribute()
    {
        return $this->equipment()->where('status', 'active')->count();
    }
}