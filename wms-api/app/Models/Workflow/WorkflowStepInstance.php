<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class WorkflowStepInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_instance_id',
        'workflow_step_id',
        'status',
        'started_at',
        'completed_at',
        'assigned_to',
        'completed_by',
        'step_data',
        'notes',
    ];

    protected $casts = [
        'step_data' => 'json',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflowInstance()
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function workflowStep()
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function approvals()
    {
        return $this->hasMany(WorkflowApproval::class);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isSkipped()
    {
        return $this->status === 'skipped';
    }

    public function hasError()
    {
        return $this->status === 'error';
    }

    public function isApprovalStep()
    {
        return $this->workflowStep->step_type === 'approval';
    }

    public function isApproved()
    {
        if (!$this->isApprovalStep()) {
            return false;
        }

        $approvals = $this->approvals;
        if ($approvals->isEmpty()) {
            return false;
        }

        $approvalType = $this->workflowStep->step_configuration['approval_type'] ?? 'all';

        if ($approvalType === 'any') {
            return $approvals->where('status', 'approved')->count() > 0;
        }

        return $approvals->where('status', 'rejected')->count() === 0 &&
               $approvals->where('status', 'approved')->count() === $approvals->count();
    }
}