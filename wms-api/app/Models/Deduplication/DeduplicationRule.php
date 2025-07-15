<?php

namespace App\Models\Deduplication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeduplicationRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'rule_code',
        'description',
        'entity_type',
        'match_fields',
        'match_threshold',
        'fuzzy_match_config',
        'action_on_match',
        'is_active',
    ];

    protected $casts = [
        'match_fields' => 'json',
        'match_threshold' => 'float',
        'fuzzy_match_config' => 'json',
        'action_on_match' => 'json',
        'is_active' => 'boolean',
    ];

    public function executions()
    {
        return $this->hasMany(DeduplicationExecution::class, 'rule_id');
    }

    public function getMatchFields()
    {
        return $this->match_fields ?? [];
    }

    public function getFuzzyMatchConfig()
    {
        return $this->fuzzy_match_config ?? [];
    }

    public function getActionOnMatch()
    {
        return $this->action_on_match ?? [];
    }

    public function isExactMatch()
    {
        return !isset($this->fuzzy_match_config['enabled']) || !$this->fuzzy_match_config['enabled'];
    }

    public function isFuzzyMatch()
    {
        return isset($this->fuzzy_match_config['enabled']) && $this->fuzzy_match_config['enabled'];
    }

    public function getMatchAlgorithm()
    {
        if (!$this->isFuzzyMatch()) {
            return 'exact';
        }
        
        return $this->fuzzy_match_config['algorithm'] ?? 'levenshtein';
    }

    public function getActionType()
    {
        return $this->action_on_match['action_type'] ?? 'flag';
    }

    public function isAutoMerge()
    {
        return $this->getActionType() === 'auto_merge';
    }

    public function isFlag()
    {
        return $this->getActionType() === 'flag';
    }

    public function isPrevent()
    {
        return $this->getActionType() === 'prevent';
    }
}