<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id',
        'entity_type',
        'entity_id',
        'current_step_code',
        'status',
        'initiated_by',
        'completed_at',
        'cancellation_reason',
        'workflow_data',
    ];

    protected $casts = [
        'workflow_data' => 'json',
        'completed_at' => 'datetime',
    ];

    public function workflowDefinition()
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function stepInstances()
    {
        return $this->hasMany(WorkflowStepInstance::class);
    }

    public function transitions()
    {
        return $this->hasMany(WorkflowTransition::class);
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function currentStep()
    {
        if (!$this->current_step_code) {
            return null;
        }

        return WorkflowStep::where('workflow_definition_id', $this->workflow_definition_id)
            ->where('step_code', $this->current_step_code)
            ->first();
    }

    public function currentStepInstance()
    {
        return $this->stepInstances()
            ->where('status', 'in_progress')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function hasError()
    {
        return $this->status === 'error';
    }
}