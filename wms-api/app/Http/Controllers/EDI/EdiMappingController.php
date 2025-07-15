<?php

namespace App\Http\Controllers\EDI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EDI\EdiMapping;
use App\Models\EDI\EdiTradingPartner;
use App\Models\EDI\EdiDocumentType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EdiMappingController extends Controller
{
    /**
     * Display a listing of mappings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = EdiMapping::with(['tradingPartner', 'documentType']);
            
            // Apply filters
            if ($request->has('trading_partner_id')) {
                $query->where('trading_partner_id', $request->trading_partner_id);
            }
            
            if ($request->has('document_type_id')) {
                $query->where('document_type_id', $request->document_type_id);
            }
            
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('mapping_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $mappings = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $mappings,
                'message' => 'Mappings retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving mappings: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve mappings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created mapping.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'mapping_code' => 'required|string|max:50|unique:edi_mappings',
                'description' => 'nullable|string',
                'trading_partner_id' => 'required|exists:edi_trading_partners,id',
                'document_type_id' => 'required|exists:edi_document_types,id',
                'direction' => 'required|string|in:inbound,outbound',
                'mapping_type' => 'required|string|max:50',
                'mapping_rules' => 'required|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if trading partner and document type exist
            $tradingPartner = EdiTradingPartner::findOrFail($request->trading_partner_id);
            $documentType = EdiDocumentType::findOrFail($request->document_type_id);
            
            // Check if document type supports the specified direction
            if ($documentType->direction !== 'both' && $documentType->direction !== $request->direction) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document type does not support the specified direction'
                ], 422);
            }
            
            // Create mapping
            $mapping = EdiMapping::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $mapping->load(['tradingPartner', 'documentType']),
                'message' => 'Mapping created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating mapping: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified mapping.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $mapping = EdiMapping::with(['tradingPartner', 'documentType', 'transactions'])
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $mapping,
                'message' => 'Mapping retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving mapping: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Mapping not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified mapping.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $mapping = EdiMapping::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'mapping_code' => 'string|max:50|unique:edi_mappings,mapping_code,' . $id,
                'description' => 'nullable|string',
                'trading_partner_id' => 'exists:edi_trading_partners,id',
                'document_type_id' => 'exists:edi_document_types,id',
                'direction' => 'string|in:inbound,outbound',
                'mapping_type' => 'string|max:50',
                'mapping_rules' => 'json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if document type supports the specified direction
            if ($request->has('document_type_id') || $request->has('direction')) {
                $documentTypeId = $request->document_type_id ?? $mapping->document_type_id;
                $direction = $request->direction ?? $mapping->direction;
                
                $documentType = EdiDocumentType::findOrFail($documentTypeId);
                
                if ($documentType->direction !== 'both' && $documentType->direction !== $direction) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Document type does not support the specified direction'
                    ], 422);
                }
            }
            
            $mapping->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $mapping->load(['tradingPartner', 'documentType']),
                'message' => 'Mapping updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating mapping: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified mapping.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $mapping = EdiMapping::findOrFail($id);
            
            // Check if there are related transactions
            if ($mapping->transactions()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete mapping with associated transactions'
                ], 422);
            }
            
            $mapping->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Mapping deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting mapping: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test mapping with sample data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function testMapping(Request $request, $id)
    {
        try {
            $mapping = EdiMapping::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'sample_data' => 'required|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Implement mapping test
            $sampleData = json_decode($request->sample_data, true);
            $mappingRules = $mapping->mapping_rules;
            $mappingType = $mapping->mapping_type;
            
            // This is a placeholder - actual implementation would use appropriate mapping engine
            $mappedData = [];
            $mappingErrors = [];
            
            // Simulate mapping process
            if ($mapping->direction === 'inbound') {
                // Convert EDI to internal format
                $mappedData = $this->simulateInboundMapping($sampleData, $mappingRules, $mappingType);
            } else {
                // Convert internal format to EDI
                $mappedData = $this->simulateOutboundMapping($sampleData, $mappingRules, $mappingType);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'mapped_data' => $mappedData,
                    'mapping_errors' => $mappingErrors
                ],
                'message' => 'Mapping test completed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error testing mapping: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to test mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simulate inbound mapping (EDI to internal format).
     *
     * @param  array  $sampleData
     * @param  array  $mappingRules
     * @param  string  $mappingType
     * @return array
     */
    private function simulateInboundMapping($sampleData, $mappingRules, $mappingType)
    {
        // This is a placeholder - actual implementation would use appropriate mapping engine
        $mappedData = [
            'mapped' => true,
            'direction' => 'inbound',
            'mapping_type' => $mappingType,
            'result' => [
                'order_number' => 'TEST123',
                'order_date' => '2023-01-01',
                'customer' => [
                    'name' => 'Test Customer',
                    'id' => 'CUST001'
                ],
                'items' => [
                    [
                        'sku' => 'ITEM001',
                        'quantity' => 5,
                        'price' => 10.99
                    ]
                ]
            ]
        ];
        
        return $mappedData;
    }

    /**
     * Simulate outbound mapping (internal format to EDI).
     *
     * @param  array  $sampleData
     * @param  array  $mappingRules
     * @param  string  $mappingType
     * @return array
     */
    private function simulateOutboundMapping($sampleData, $mappingRules, $mappingType)
    {
        // This is a placeholder - actual implementation would use appropriate mapping engine
        $mappedData = [
            'mapped' => true,
            'direction' => 'outbound',
            'mapping_type' => $mappingType,
            'result' => [
                'header' => [
                    'document_type' => '850',
                    'sender_id' => 'SENDER001',
                    'receiver_id' => 'RECEIVER001',
                    'date' => '20230101',
                    'time' => '120000'
                ],
                'detail' => [
                    [
                        'line_number' => '1',
                        'item_id' => 'ITEM001',
                        'quantity' => '5',
                        'unit_price' => '10.99'
                    ]
                ]
            ]
        ];
        
        return $mappedData;
    }
}