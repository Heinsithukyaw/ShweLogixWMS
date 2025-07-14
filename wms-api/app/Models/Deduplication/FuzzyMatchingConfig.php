<?php

namespace App\Models\Deduplication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuzzyMatchingConfig extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'config_code',
        'description',
        'entity_type',
        'algorithm_type',
        'algorithm_config',
        'field_weights',
        'preprocessing_steps',
        'is_active',
    ];

    protected $casts = [
        'algorithm_config' => 'json',
        'field_weights' => 'json',
        'preprocessing_steps' => 'json',
        'is_active' => 'boolean',
    ];

    public function getAlgorithmConfig()
    {
        return $this->algorithm_config ?? [];
    }

    public function getFieldWeights()
    {
        return $this->field_weights ?? [];
    }

    public function getPreprocessingSteps()
    {
        return $this->preprocessing_steps ?? [];
    }

    public function isLevenshtein()
    {
        return $this->algorithm_type === 'levenshtein';
    }

    public function isJaroWinkler()
    {
        return $this->algorithm_type === 'jaro_winkler';
    }

    public function isSoundex()
    {
        return $this->algorithm_type === 'soundex';
    }

    public function isMetaphone()
    {
        return $this->algorithm_type === 'metaphone';
    }

    public function isNGram()
    {
        return $this->algorithm_type === 'ngram';
    }

    public function isCustom()
    {
        return $this->algorithm_type === 'custom';
    }

    public function getThreshold()
    {
        return $this->algorithm_config['threshold'] ?? 0.8;
    }

    public function getWeightForField($fieldName)
    {
        if (!isset($this->field_weights['fields'])) {
            return 1.0;
        }
        
        foreach ($this->field_weights['fields'] as $field) {
            if ($field['field_name'] === $fieldName) {
                return $field['weight'] ?? 1.0;
            }
        }
        
        return 1.0;
    }
}