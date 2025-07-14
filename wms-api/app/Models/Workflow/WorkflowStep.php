<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id',
        'step_code',
        'name',
        'description',
        'step_type',
        'step_configuration',
        'transition_rules',
        'is_start_step',
        'is_end_step',
        'timeout_minutes',
        'timeout_action',
    ];

    protected $casts = [
        'step_configuration' => 'json',
        'transition_rules' => 'json',
        'is_start_step' => 'boolean',
        'is_end_step' => 'boolean',
        'timeout_minutes' => 'integer',
    ];

    public function workflowDefinition()
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function stepInstances()
    {
        return $this->hasMany(WorkflowStepInstance::class);
    }

    public function getNextSteps($data = [])
    {
        if (!$this->transition_rules) {
            return [];
        }

        $nextSteps = [];
        foreach ($this->transition_rules as $rule) {
            if ($this->evaluateRule($rule, $data)) {
                $nextSteps[] = $rule['next_step'];
            }
        }

        return $nextSteps;
    }

    private function evaluateRule($rule, $data)
    {
        // Simple rule evaluation logic
        if (!isset($rule['condition']) || empty($rule['condition'])) {
            return true; // No condition means always true
        }

        // Implement rule evaluation logic based on your requirements
        // This is a simplified example
        $condition = $rule['condition'];
        
        if (isset($condition['field']) && isset($condition['operator']) && isset($condition['value'])) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];
            
            if (!isset($data[$field])) {
                return false;
            }
            
            $fieldValue = $data[$field];
            
            switch ($operator) {
                case '==':
                    return $fieldValue == $value;
                case '!=':
                    return $fieldValue != $value;
                case '>':
                    return $fieldValue > $value;
                case '>=':
                    return $fieldValue >= $value;
                case '<':
                    return $fieldValue < $value;
                case '<=':
                    return $fieldValue <= $value;
                case 'in':
                    return in_array($fieldValue, (array) $value);
                case 'not_in':
                    return !in_array($fieldValue, (array) $value);
                case 'contains':
                    return strpos($fieldValue, $value) !== false;
                default:
                    return false;
            }
        }
        
        return false;
    }
}