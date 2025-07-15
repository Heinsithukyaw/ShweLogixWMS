<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowDefinition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'entity_type',
        'workflow_schema',
        'is_active',
        'version',
    ];

    protected $casts = [
        'workflow_schema' => 'json',
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function instances()
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function startStep()
    {
        return $this->steps()->where('is_start_step', true)->first();
    }

    public function endSteps()
    {
        return $this->steps()->where('is_end_step', true)->get();
    }
}