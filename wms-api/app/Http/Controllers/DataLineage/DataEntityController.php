<?php

namespace App\Http\Controllers\DataLineage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataLineage\DataEntity;
use App\Models\DataLineage\DataSource;
use App\Models\DataLineage\DataField;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataEntityController extends Controller
{
    /**
     * Display a listing of data entities.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = DataEntity::with('source');
            
            // Apply filters
            if ($request->has('source_id')) {
                $query->where('source_id', $request->source_id);
            }
            
            if ($request->has('entity_type')) {
                $query->where('entity_type', $request->entity_type);
            }
            
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('entity_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $entities = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $entities,
                'message' => 'Data entities retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving data entities: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data entities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created data entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'entity_code' => 'required|string|max:50|unique:data_entities',
                'description' => 'nullable|string',
                'source_id' => 'required|exists:data_sources,id',
                'entity_type' => 'required|string|max:50',
                'schema_definition' => 'nullable|json',
                'metadata' => 'nullable|json',
                'is_active' => 'boolean',
                'fields' => 'nullable|array',
                'fields.*.name' => 'required|string|max:255',
                'fields.*.field_code' => 'required|string|max:50',
                'fields.*.description' => 'nullable|string',
                'fields.*.data_type' => 'required|string|max:50',
                'fields.*.is_nullable' => 'boolean',
                'fields.*.is_primary_key' => 'boolean',
                'fields.*.is_foreign_key' => 'boolean',
                'fields.*.default_value' => 'nullable|string',
                'fields.*.field_constraints' => 'nullable|json',
                'fields.*.metadata' => 'nullable|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if source exists and is active
            $source = DataSource::findOrFail($request->source_id);
            
            if (!$source->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot create entity for inactive data source'
                ], 422);
            }
            
            // Create entity and fields in a transaction
            DB::beginTransaction();
            
            try {
                // Create entity
                $entity = DataEntity::create($request->except('fields'));
                
                // Create fields if provided
                if ($request->has('fields') && is_array($request->fields)) {
                    foreach ($request->fields as $fieldData) {
                        $field = new DataField(array_merge($fieldData, ['entity_id' => $entity->id]));
                        $field->save();
                    }
                }
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'data' => $entity->load(['source', 'fields']),
                    'message' => 'Data entity created successfully'
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error creating data entity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create data entity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified data entity.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $entity = DataEntity::with([
                'source',
                'fields',
                'sourceFlows',
                'targetFlows',
                'transformations'
            ])->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $entity,
                'message' => 'Data entity retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving data entity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Data entity not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified data entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $entity = DataEntity::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'entity_code' => 'string|max:50|unique:data_entities,entity_code,' . $id,
                'description' => 'nullable|string',
                'source_id' => 'exists:data_sources,id',
                'entity_type' => 'string|max:50',
                'schema_definition' => 'nullable|json',
                'metadata' => 'nullable|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if source is active if changing
            if ($request->has('source_id') && $request->source_id != $entity->source_id) {
                $source = DataSource::findOrFail($request->source_id);
                
                if (!$source->is_active) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot assign entity to inactive data source'
                    ], 422);
                }
            }
            
            $entity->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $entity->load(['source', 'fields']),
                'message' => 'Data entity updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating data entity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update data entity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified data entity.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $entity = DataEntity::findOrFail($id);
            
            // Check if there are related flows or transformations
            if ($entity->sourceFlows()->count() > 0 || $entity->targetFlows()->count() > 0 || $entity->transformations()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete entity with associated flows or transformations'
                ], 422);
            }
            
            // Delete entity and fields in a transaction
            DB::beginTransaction();
            
            try {
                // Delete fields
                $entity->fields()->delete();
                
                // Delete entity
                $entity->delete();
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data entity deleted successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error deleting data entity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete data entity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fields for a data entity.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getFields($id)
    {
        try {
            $entity = DataEntity::findOrFail($id);
            
            $fields = DataField::where('entity_id', $id)
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $fields,
                'message' => 'Data fields retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving data fields: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data fields',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a field to a data entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addField(Request $request, $id)
    {
        try {
            $entity = DataEntity::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'field_code' => 'required|string|max:50',
                'description' => 'nullable|string',
                'data_type' => 'required|string|max:50',
                'is_nullable' => 'boolean',
                'is_primary_key' => 'boolean',
                'is_foreign_key' => 'boolean',
                'default_value' => 'nullable|string',
                'field_constraints' => 'nullable|json',
                'metadata' => 'nullable|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if field code is unique for this entity
            $existingField = DataField::where('entity_id', $id)
                ->where('field_code', $request->field_code)
                ->first();
                
            if ($existingField) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Field code must be unique within the entity'
                ], 422);
            }
            
            // Create field
            $field = new DataField(array_merge($request->all(), ['entity_id' => $id]));
            $field->save();
            
            return response()->json([
                'status' => 'success',
                'data' => $field,
                'message' => 'Field added successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error adding field to data entity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add field',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a field in a data entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  int  $fieldId
     * @return \Illuminate\Http\Response
     */
    public function updateField(Request $request, $id, $fieldId)
    {
        try {
            $entity = DataEntity::findOrFail($id);
            
            $field = DataField::where('entity_id', $id)
                ->where('id', $fieldId)
                ->firstOrFail();
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'field_code' => 'string|max:50',
                'description' => 'nullable|string',
                'data_type' => 'string|max:50',
                'is_nullable' => 'boolean',
                'is_primary_key' => 'boolean',
                'is_foreign_key' => 'boolean',
                'default_value' => 'nullable|string',
                'field_constraints' => 'nullable|json',
                'metadata' => 'nullable|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if field code is unique for this entity if changing
            if ($request->has('field_code') && $request->field_code != $field->field_code) {
                $existingField = DataField::where('entity_id', $id)
                    ->where('field_code', $request->field_code)
                    ->where('id', '!=', $fieldId)
                    ->first();
                    
                if ($existingField) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Field code must be unique within the entity'
                    ], 422);
                }
            }
            
            // Update field
            $field->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $field,
                'message' => 'Field updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating field in data entity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update field',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a field from a data entity.
     *
     * @param  int  $id
     * @param  int  $fieldId
     * @return \Illuminate\Http\Response
     */
    public function removeField($id, $fieldId)
    {
        try {
            $entity = DataEntity::findOrFail($id);
            
            $field = DataField::where('entity_id', $id)
                ->where('id', $fieldId)
                ->firstOrFail();
            
            // Check if field is used in mappings or transformations
            $hasSourceMappings = $field->sourceFieldMappings()->count() > 0;
            $hasTargetMappings = $field->targetFieldMappings()->count() > 0;
            $hasTransformations = $field->transformations()->count() > 0;
            
            if ($hasSourceMappings || $hasTargetMappings || $hasTransformations) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete field that is used in mappings or transformations'
                ], 422);
            }
            
            // Delete field
            $field->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Field removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error removing field from data entity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove field',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}