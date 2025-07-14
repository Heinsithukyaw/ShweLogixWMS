<?php

namespace App\Models\Labor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaborShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_name',
        'start_time',
        'end_time',
        'duration_minutes',
        'is_overnight',
        'working_days',
        'base_hourly_rate',
        'overtime_multiplier',
        'is_active'
    ];

    protected $casts = [
        'working_days' => 'array',
        'is_overnight' => 'boolean',
        'is_active' => 'boolean',
        'base_hourly_rate' => 'decimal:2',
        'overtime_multiplier' => 'decimal:2',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i'
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(LaborSchedule::class, 'shift_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay($query, $day)
    {
        return $query->whereJsonContains('working_days', strtolower($day));
    }

    public function isWorkingDay($day): bool
    {
        return in_array(strtolower($day), $this->working_days ?? []);
    }

    public function getOvertimeRateAttribute()
    {
        return $this->base_hourly_rate * $this->overtime_multiplier;
    }

    public function getDurationHoursAttribute()
    {
        return $this->duration_minutes / 60;
    }
}