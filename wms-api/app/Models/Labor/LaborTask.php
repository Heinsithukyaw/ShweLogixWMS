<?php

namespace App\Models\Labor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Employee;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Models\Location;

class LaborTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_number',
        'task_type',
        'priority',
        'status',
        'assigned_to',
        'created_by',
        'warehouse_id',
        'zone_id',
        'location_id',
        'description',
        'instructions',
        'estimated_minutes',
        'actual_minutes',
        'estimated_cost',
        'actual_cost',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'required_skills',
        'required_equipment',
        'completion_notes',
        'quality_score'
    ];

    protected $casts = [
        'required_skills' => 'array',
        'required_equipment' => 'array',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime'
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByTaskType($query, $taskType)
    {
        return $query->where('task_type', $taskType);
    }

    public function scopeAssignedTo($query, $employeeId)
    {
        return $query->where('assigned_to', $employeeId);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isOverdue(): bool
    {
        return $this->scheduled_end && 
               $this->scheduled_end < now() && 
               !$this->isCompleted();
    }

    public function getEfficiencyRateAttribute()
    {
        return $this->estimated_minutes > 0 && $this->actual_minutes > 0
            ? ($this->estimated_minutes / $this->actual_minutes) * 100
            : 0;
    }

    public function getCostVarianceAttribute()
    {
        return $this->actual_cost - $this->estimated_cost;
    }

    public function getDurationMinutesAttribute()
    {
        if ($this->actual_start && $this->actual_end) {
            return $this->actual_start->diffInMinutes($this->actual_end);
        }
        return 0;
    }

    public function hasRequiredSkills(array $employeeSkills): bool
    {
        if (empty($this->required_skills)) {
            return true;
        }
        
        return !array_diff($this->required_skills, $employeeSkills);
    }
}