<?php

namespace App\Http\Controllers\Deduplication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deduplication\FuzzyMatchingConfig;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FuzzyMatchingConfigController extends Controller
{
    /**
     * Display a listing of fuzzy matching configurations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = FuzzyMatchingConfig::query();
            
            // Apply filters
            if ($request->has('entity_type')) {
                $query->where('entity_type', $request->entity_type);
            }
            
            if ($request->has('algorithm_type')) {
                $query->where('algorithm_type', $request->algorithm_type);
            }
            
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('config_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $configs = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $configs,
                'message' => 'Fuzzy matching configurations retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving fuzzy matching configurations: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve fuzzy matching configurations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created fuzzy matching configuration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'config_code' => 'required|string|max:50|unique:fuzzy_matching_configs',
                'description' => 'nullable|string',
                'entity_type' => 'required|string|max:100',
                'algorithm_type' => 'required|string|in:levenshtein,jaro_winkler,soundex,metaphone,ngram,custom',
                'algorithm_config' => 'required|json',
                'field_weights' => 'required|json',
                'preprocessing_steps' => 'nullable|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Validate algorithm config
            $algorithmConfig = json_decode($request->algorithm_config, true);
            if (!isset($algorithmConfig['threshold']) || !is_numeric($algorithmConfig['threshold']) || $algorithmConfig['threshold'] < 0 || $algorithmConfig['threshold'] > 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Algorithm config must contain a valid threshold between 0 and 1',
                    'errors' => ['algorithm_config' => ['Threshold must be between 0 and 1']]
                ], 422);
            }
            
            // Validate field weights
            $fieldWeights = json_decode($request->field_weights, true);
            if (!isset($fieldWeights['fields']) || !is_array($fieldWeights['fields']) || count($fieldWeights['fields']) === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Field weights must contain a non-empty fields array',
                    'errors' => ['field_weights' => ['Fields array must be non-empty']]
                ], 422);
            }
            
            $config = FuzzyMatchingConfig::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $config,
                'message' => 'Fuzzy matching configuration created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating fuzzy matching configuration: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create fuzzy matching configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified fuzzy matching configuration.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $config = FuzzyMatchingConfig::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $config,
                'message' => 'Fuzzy matching configuration retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving fuzzy matching configuration: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Fuzzy matching configuration not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified fuzzy matching configuration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $config = FuzzyMatchingConfig::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'config_code' => 'string|max:50|unique:fuzzy_matching_configs,config_code,' . $id,
                'description' => 'nullable|string',
                'entity_type' => 'string|max:100',
                'algorithm_type' => 'string|in:levenshtein,jaro_winkler,soundex,metaphone,ngram,custom',
                'algorithm_config' => 'json',
                'field_weights' => 'json',
                'preprocessing_steps' => 'nullable|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Validate algorithm config if provided
            if ($request->has('algorithm_config')) {
                $algorithmConfig = json_decode($request->algorithm_config, true);
                if (!isset($algorithmConfig['threshold']) || !is_numeric($algorithmConfig['threshold']) || $algorithmConfig['threshold'] < 0 || $algorithmConfig['threshold'] > 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Algorithm config must contain a valid threshold between 0 and 1',
                        'errors' => ['algorithm_config' => ['Threshold must be between 0 and 1']]
                    ], 422);
                }
            }
            
            // Validate field weights if provided
            if ($request->has('field_weights')) {
                $fieldWeights = json_decode($request->field_weights, true);
                if (!isset($fieldWeights['fields']) || !is_array($fieldWeights['fields']) || count($fieldWeights['fields']) === 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Field weights must contain a non-empty fields array',
                        'errors' => ['field_weights' => ['Fields array must be non-empty']]
                    ], 422);
                }
            }
            
            $config->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $config,
                'message' => 'Fuzzy matching configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating fuzzy matching configuration: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update fuzzy matching configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified fuzzy matching configuration.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $config = FuzzyMatchingConfig::findOrFail($id);
            
            $config->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Fuzzy matching configuration deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting fuzzy matching configuration: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete fuzzy matching configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test fuzzy matching with sample data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function testMatching(Request $request, $id)
    {
        try {
            $config = FuzzyMatchingConfig::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'record1' => 'required|json',
                'record2' => 'required|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $record1 = json_decode($request->record1, true);
            $record2 = json_decode($request->record2, true);
            
            // Perform fuzzy matching
            $matchResult = $this->performFuzzyMatching($record1, $record2, $config);
            
            return response()->json([
                'status' => 'success',
                'data' => $matchResult,
                'message' => 'Fuzzy matching test completed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error testing fuzzy matching: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to test fuzzy matching',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform fuzzy matching between two records.
     *
     * @param  array  $record1
     * @param  array  $record2
     * @param  \App\Models\Deduplication\FuzzyMatchingConfig  $config
     * @return array
     */
    private function performFuzzyMatching($record1, $record2, $config)
    {
        // This is a placeholder - actual implementation would use appropriate fuzzy matching algorithms
        
        $algorithmType = $config->algorithm_type;
        $threshold = $config->getThreshold();
        $fieldWeights = $config->getFieldWeights()['fields'] ?? [];
        
        $fieldScores = [];
        $totalScore = 0;
        $totalWeight = 0;
        
        // Calculate field scores
        foreach ($fieldWeights as $fieldConfig) {
            $fieldName = $fieldConfig['field_name'];
            $weight = $fieldConfig['weight'] ?? 1.0;
            
            if (isset($record1[$fieldName]) && isset($record2[$fieldName])) {
                $value1 = $record1[$fieldName];
                $value2 = $record2[$fieldName];
                
                // Calculate similarity score based on algorithm type
                $score = $this->calculateSimilarity($value1, $value2, $algorithmType);
                
                $fieldScores[$fieldName] = [
                    'value1' => $value1,
                    'value2' => $value2,
                    'score' => $score,
                    'weight' => $weight,
                    'weighted_score' => $score * $weight,
                ];
                
                $totalScore += $score * $weight;
                $totalWeight += $weight;
            }
        }
        
        // Calculate overall match score
        $overallScore = $totalWeight > 0 ? $totalScore / $totalWeight : 0;
        $isMatch = $overallScore >= $threshold;
        
        return [
            'overall_score' => round($overallScore, 4),
            'threshold' => $threshold,
            'is_match' => $isMatch,
            'algorithm_type' => $algorithmType,
            'field_scores' => $fieldScores,
        ];
    }

    /**
     * Calculate similarity between two values using specified algorithm.
     *
     * @param  string  $value1
     * @param  string  $value2
     * @param  string  $algorithmType
     * @return float
     */
    private function calculateSimilarity($value1, $value2, $algorithmType)
    {
        // Convert values to strings
        $value1 = (string) $value1;
        $value2 = (string) $value2;
        
        // If values are identical, return perfect score
        if ($value1 === $value2) {
            return 1.0;
        }
        
        // If either value is empty, return zero score
        if (empty($value1) || empty($value2)) {
            return 0.0;
        }
        
        // Calculate similarity based on algorithm type
        switch ($algorithmType) {
            case 'levenshtein':
                $maxLength = max(strlen($value1), strlen($value2));
                if ($maxLength === 0) return 1.0;
                $levenshtein = levenshtein($value1, $value2);
                return 1.0 - ($levenshtein / $maxLength);
                
            case 'jaro_winkler':
                // Simplified Jaro-Winkler implementation
                similar_text($value1, $value2, $percent);
                return $percent / 100;
                
            case 'soundex':
                return soundex($value1) === soundex($value2) ? 1.0 : 0.0;
                
            case 'metaphone':
                return metaphone($value1) === metaphone($value2) ? 1.0 : 0.0;
                
            case 'ngram':
                // Simplified n-gram implementation
                similar_text($value1, $value2, $percent);
                return $percent / 100;
                
            case 'custom':
                // Fallback to simple similarity
                similar_text($value1, $value2, $percent);
                return $percent / 100;
                
            default:
                // Default to simple similarity
                similar_text($value1, $value2, $percent);
                return $percent / 100;
        }
    }
}