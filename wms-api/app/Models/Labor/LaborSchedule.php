<?php

namespace App\Models\Labor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Employee;
use App\Models\Warehouse;

class LaborSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'warehouse_id',
        'schedule_date',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'status',
        'scheduled_hours',
        'actual_hours',
        'overtime_hours',
        'break_minutes',
        'notes',
        'supervisor_id'
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'scheduled_start' => 'datetime:H:i',
        'scheduled_end' => 'datetime:H:i',
        'actual_start' => 'datetime:H:i',
        'actual_end' => 'datetime:H:i'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(LaborShift::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function timeTracking(): HasMany
    {
        return $this->hasMany(LaborTimeTracking::class, 'schedule_id');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('schedule_date', $date);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function isPresent(): bool
    {
        return in_array($this->status, ['checked_in', 'on_break', 'checked_out']);
    }

    public function isLate(): bool
    {
        return $this->status === 'late' || 
               ($this->actual_start && $this->actual_start > $this->scheduled_start);
    }

    public function hasOvertime(): bool
    {
        return $this->overtime_hours > 0;
    }

    public function getAttendanceRateAttribute()
    {
        return $this->scheduled_hours > 0 
            ? ($this->actual_hours / $this->scheduled_hours) * 100 
            : 0;
    }

    public function getProductivityHoursAttribute()
    {
        return $this->actual_hours - ($this->break_minutes / 60);
    }
}