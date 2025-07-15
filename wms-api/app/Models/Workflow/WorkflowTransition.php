<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class WorkflowTransition extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_instance_id',
        'from_step_code',
        'to_step_code',
        'transition_type',
        'triggered_by',
        'transition_reason',
        'transition_data',
    ];

    protected $casts = [
        'transition_data' => 'json',
    ];

    public function workflowInstance()
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function fromStep()
    {
        return WorkflowStep::where('step_code', $this->from_step_code)
            ->where('workflow_definition_id', $this->workflowInstance->workflow_definition_id)
            ->first();
    }

    public function toStep()
    {
        return WorkflowStep::where('step_code', $this->to_step_code)
            ->where('workflow_definition_id', $this->workflowInstance->workflow_definition_id)
            ->first();
    }

    public function isNormal()
    {
        return $this->transition_type === 'normal';
    }

    public function isSkip()
    {
        return $this->transition_type === 'skip';
    }

    public function isRollback()
    {
        return $this->transition_type === 'rollback';
    }

    public function isError()
    {
        return $this->transition_type === 'error';
    }
}