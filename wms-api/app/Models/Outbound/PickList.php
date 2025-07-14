<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PickWave;
use App\Models\Employee;
use App\Models\User;

class PickList extends Model
{
    use HasFactory;

    protected $fillable = [
        'pick_list_number',
        'pick_wave_id',
        'assigned_to',
        'pick_type',
        'pick_method',
        'pick_status',
        'total_picks',
        'completed_picks',
        'estimated_time',
        'actual_time',
        'pick_sequence',
        'assigned_at',
        'started_at',
        'completed_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'estimated_time' => 'decimal:2',
        'actual_time' => 'decimal:2',
        'pick_sequence' => 'array',
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function pickWave()
    {
        return $this->belongsTo(PickWave::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pickListItems()
    {
        return $this->hasMany(PickListItem::class);
    }

    public function pickConfirmations()
    {
        return $this->hasManyThrough(PickConfirmation::class, PickListItem::class);
    }

    public function pickExceptions()
    {
        return $this->hasManyThrough(PickException::class, PickListItem::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('pick_status', $status);
    }

    public function scopeAssigned($query)
    {
        return $query->whereNotNull('assigned_to');
    }

    public function scopeInProgress($query)
    {
        return $query->where('pick_status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('pick_status', 'completed');
    }

    public function scopeByPicker($query, $employeeId)
    {
        return $query->where('assigned_to', $employeeId);
    }

    // Methods
    public function getCompletionPercentage()
    {
        return $this->total_picks > 0 ? 
            round(($this->completed_picks / $this->total_picks) * 100, 2) : 0;
    }

    public function isOverdue()
    {
        if (!$this->assigned_at || !$this->estimated_time) {
            return false;
        }
        
        $expectedCompletion = $this->assigned_at->addHours($this->estimated_time);
        return now() > $expectedCompletion && $this->pick_status !== 'completed';
    }

    public function getActualEfficiency()
    {
        if (!$this->estimated_time || !$this->actual_time || $this->actual_time == 0) {
            return null;
        }
        
        return round(($this->estimated_time / $this->actual_time) * 100, 2);
    }

    public function assign($employeeId)
    {
        $this->assigned_to = $employeeId;
        $this->pick_status = 'assigned';
        $this->assigned_at = now();
        $this->save();
    }

    public function start()
    {
        $this->pick_status = 'in_progress';
        $this->started_at = now();
        $this->save();
    }

    public function complete()
    {
        $this->pick_status = 'completed';
        $this->completed_at = now();
        
        if ($this->started_at) {
            $this->actual_time = $this->started_at->diffInMinutes($this->completed_at) / 60;
        }
        
        $this->save();
    }

    public function updateProgress()
    {
        $this->completed_picks = $this->pickListItems()
            ->whereIn('pick_status', ['picked', 'short_picked'])
            ->count();
            
        if ($this->completed_picks >= $this->total_picks) {
            $this->complete();
        }
        
        $this->save();
    }

    public function generateOptimizedSequence()
    {
        // Get all pick locations
        $locations = $this->pickListItems()
            ->with('location')
            ->get()
            ->pluck('location')
            ->unique('id');
            
        // Simple optimization - sort by aisle, then by position
        $optimizedSequence = $locations->sortBy([
            ['aisle', 'asc'],
            ['position', 'asc']
        ])->pluck('id')->toArray();
        
        $this->pick_sequence = $optimizedSequence;
        $this->save();
        
        return $optimizedSequence;
    }
}