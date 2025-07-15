<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class WorkflowApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_step_instance_id',
        'approval_type',
        'approver_id',
        'approver_role',
        'status',
        'comments',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function stepInstance()
    {
        return $this->belongsTo(WorkflowStepInstance::class, 'workflow_step_instance_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function hasResponded()
    {
        return $this->responded_at !== null;
    }
}