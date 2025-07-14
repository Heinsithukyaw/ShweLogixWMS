<?php

namespace App\Http\Controllers\EDI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EDI\IdocConfiguration;
use App\Models\EDI\IdocTransaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IdocController extends Controller
{
    /**
     * Display a listing of IDoc configurations.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexConfigurations(Request $request)
    {
        try {
            $query = IdocConfiguration::query();
            
            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('idoc_type', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $configurations = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $configurations,
                'message' => 'IDoc configurations retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving IDoc configurations: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve IDoc configurations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created IDoc configuration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeConfiguration(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'idoc_type' => 'required|string|max:50',
                'description' => 'nullable|string',
                'sap_system' => 'required|string|max:50',
                'connection_details' => 'required|json',
                'schema_definition' => 'nullable|json',
                'mapping_rules' => 'nullable|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $configuration = IdocConfiguration::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $configuration,
                'message' => 'IDoc configuration created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating IDoc configuration: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create IDoc configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified IDoc configuration.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showConfiguration($id)
    {
        try {
            $configuration = IdocConfiguration::with('transactions')
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $configuration,
                'message' => 'IDoc configuration retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving IDoc configuration: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'IDoc configuration not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified IDoc configuration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateConfiguration(Request $request, $id)
    {
        try {
            $configuration = IdocConfiguration::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'idoc_type' => 'string|max:50',
                'description' => 'nullable|string',
                'sap_system' => 'string|max:50',
                'connection_details' => 'json',
                'schema_definition' => 'nullable|json',
                'mapping_rules' => 'nullable|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $configuration->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $configuration,
                'message' => 'IDoc configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating IDoc configuration: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update IDoc configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified IDoc configuration.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyConfiguration($id)
    {
        try {
            $configuration = IdocConfiguration::findOrFail($id);
            
            // Check if there are related transactions
            if ($configuration->transactions()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete configuration with associated transactions'
                ], 422);
            }
            
            $configuration->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'IDoc configuration deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting IDoc configuration: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete IDoc configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of IDoc transactions.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexTransactions(Request $request)
    {
        try {
            $query = IdocTransaction::with('configuration');
            
            // Apply filters
            if ($request->has('configuration_id')) {
                $query->where('configuration_id', $request->configuration_id);
            }
            
            if ($request->has('direction')) {
                $query->where('direction', $request->direction);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('idoc_number')) {
                $query->where('idoc_number', 'like', "%{$request->idoc_number}%");
            }
            
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $transactions = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $transactions,
                'message' => 'IDoc transactions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving IDoc transactions: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve IDoc transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created IDoc transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeTransaction(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'configuration_id' => 'required|exists:idoc_configurations,id',
                'idoc_number' => 'nullable|string|max:100',
                'direction' => 'required|string|in:inbound,outbound',
                'idoc_data' => 'required|json',
                'original_data' => 'nullable|string',
                'status' => 'required|string|max:50',
                'processing_notes' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Generate IDoc number if not provided
            if (!$request->has('idoc_number') || empty($request->idoc_number)) {
                $request->merge(['idoc_number' => 'IDOC' . date('YmdHis')]);
            }
            
            // Store original data file if provided
            $originalDataPath = null;
            if ($request->has('original_data') && !empty($request->original_data)) {
                $originalDataPath = 'idoc/transactions/' . date('Y/m/d') . '/' . $request->idoc_number . '.dat';
                Storage::put($originalDataPath, $request->original_data);
            }
            
            // Create transaction
            $transaction = new IdocTransaction([
                'configuration_id' => $request->configuration_id,
                'idoc_number' => $request->idoc_number,
                'direction' => $request->direction,
                'idoc_data' => $request->idoc_data,
                'original_data_path' => $originalDataPath,
                'status' => $request->status,
                'processing_notes' => $request->processing_notes,
            ]);
            
            $transaction->save();
            
            return response()->json([
                'status' => 'success',
                'data' => $transaction->load('configuration'),
                'message' => 'IDoc transaction created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating IDoc transaction: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create IDoc transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified IDoc transaction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showTransaction($id)
    {
        try {
            $transaction = IdocTransaction::with('configuration')
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $transaction,
                'message' => 'IDoc transaction retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving IDoc transaction: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'IDoc transaction not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified IDoc transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateTransaction(Request $request, $id)
    {
        try {
            $transaction = IdocTransaction::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'status' => 'string|max:50',
                'processing_notes' => 'nullable|string',
                'error_message' => 'nullable|string',
                'processed_at' => 'nullable|date',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update only allowed fields
            $transaction->update($request->only([
                'status',
                'processing_notes',
                'error_message',
                'processed_at'
            ]));
            
            return response()->json([
                'status' => 'success',
                'data' => $transaction->load('configuration'),
                'message' => 'IDoc transaction updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating IDoc transaction: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update IDoc transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process an inbound IDoc.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processInbound(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'configuration_id' => 'required|exists:idoc_configurations,id',
                'idoc_data' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Get configuration
            $configuration = IdocConfiguration::findOrFail($request->configuration_id);
            
            // Generate IDoc number
            $idocNumber = 'IDOC' . date('YmdHis');
            
            // Store original IDoc data
            $originalDataPath = 'idoc/transactions/' . date('Y/m/d') . '/' . $idocNumber . '.dat';
            Storage::put($originalDataPath, $request->idoc_data);
            
            // Process IDoc data using configuration
            // This is a placeholder - actual implementation would use appropriate IDoc parser and mapping engine
            $processedData = $this->processIdocData($request->idoc_data, $configuration);
            
            // Create transaction record
            $transaction = new IdocTransaction([
                'configuration_id' => $request->configuration_id,
                'idoc_number' => $idocNumber,
                'direction' => 'inbound',
                'idoc_data' => $processedData['data'],
                'original_data_path' => $originalDataPath,
                'status' => $processedData['success'] ? 'processed' : 'error',
                'processing_notes' => $processedData['notes'] ?? null,
                'error_message' => $processedData['error'] ?? null,
                'processed_at' => now(),
            ]);
            
            $transaction->save();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'transaction' => $transaction->load('configuration'),
                    'processed_data' => $processedData['data']
                ],
                'message' => $processedData['success'] ? 'IDoc processed successfully' : 'IDoc processed with errors'
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing inbound IDoc: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process IDoc',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate an outbound IDoc.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateOutbound(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'configuration_id' => 'required|exists:idoc_configurations,id',
                'source_data' => 'required|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Get configuration
            $configuration = IdocConfiguration::findOrFail($request->configuration_id);
            
            // Generate IDoc number
            $idocNumber = 'IDOC' . date('YmdHis');
            
            // Generate IDoc data using configuration
            // This is a placeholder - actual implementation would use appropriate mapping engine and IDoc generator
            $sourceData = json_decode($request->source_data, true);
            $generatedIdoc = $this->generateIdocData($sourceData, $configuration);
            
            // Store generated IDoc data
            $idocDataPath = 'idoc/transactions/' . date('Y/m/d') . '/' . $idocNumber . '.dat';
            Storage::put($idocDataPath, $generatedIdoc['idoc_data']);
            
            // Create transaction record
            $transaction = new IdocTransaction([
                'configuration_id' => $request->configuration_id,
                'idoc_number' => $idocNumber,
                'direction' => 'outbound',
                'idoc_data' => $sourceData,
                'original_data_path' => $idocDataPath,
                'status' => $generatedIdoc['success'] ? 'generated' : 'error',
                'processing_notes' => $generatedIdoc['notes'] ?? null,
                'error_message' => $generatedIdoc['error'] ?? null,
                'processed_at' => now(),
            ]);
            
            $transaction->save();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'transaction' => $transaction->load('configuration'),
                    'idoc_data' => $generatedIdoc['idoc_data']
                ],
                'message' => $generatedIdoc['success'] ? 'IDoc generated successfully' : 'IDoc generated with errors'
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating outbound IDoc: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate IDoc',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the original IDoc data for a transaction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getOriginalData($id)
    {
        try {
            $transaction = IdocTransaction::findOrFail($id);
            
            if (!$transaction->original_data_path || !Storage::exists($transaction->original_data_path)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Original data not found'
                ], 404);
            }
            
            $originalData = Storage::get($transaction->original_data_path);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'idoc_number' => $transaction->idoc_number,
                    'original_data' => $originalData
                ],
                'message' => 'Original data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving original IDoc data: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve original data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process IDoc data using configuration.
     *
     * @param  string  $idocData
     * @param  \App\Models\EDI\IdocConfiguration  $configuration
     * @return array
     */
    private function processIdocData($idocData, $configuration)
    {
        // This is a placeholder - actual implementation would use appropriate IDoc parser and mapping engine
        $result = [
            'success' => true,
            'notes' => 'Processed successfully',
            'data' => [
                'order_number' => 'PO' . date('YmdHis'),
                'order_date' => date('Y-m-d'),
                'customer' => [
                    'name' => 'Sample Customer',
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
        
        return $result;
    }

    /**
     * Generate IDoc data from source data using configuration.
     *
     * @param  array  $sourceData
     * @param  \App\Models\EDI\IdocConfiguration  $configuration
     * @return array
     */
    private function generateIdocData($sourceData, $configuration)
    {
        // This is a placeholder - actual implementation would use appropriate mapping engine and IDoc generator
        $result = [
            'success' => true,
            'notes' => 'Generated successfully',
            'idoc_data' => "EDI_DC40 TABNAM EDIDC40                  MANDT 100     DOCNUM 0000000012345678 DOCREL 740     STATUS 30      DIRECT 2       OUTMOD U       IDOCTYP ORDERS01  MESTYP ORDERS   MESCOD        MESFCT 016     STD    X       STDVRS 2021    STDMES ORDERS   SNDPOR SAPWMS   SNDPRT LS      SNDPFC        SNDPRN SAPWMS   SNDSAD        SNDLAD        RCVPOR SAPERP   RCVPRT LS      RCVPFC        RCVPRN SAPERP   RCVSAD        RCVLAD\n" .
                         "E1EDK01 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000001 PSGNUM 000000 HLEVEL 01\n" .
                         "CURCY USD\n" .
                         "HWAER USD\n" .
                         "WKURS 1.00000\n" .
                         "ZTERM 0001\n" .
                         "EIGENUINR " . ($sourceData['order_number'] ?? ('PO' . date('YmdHis'))) . "\n" .
                         "BSART NB\n" .
                         "BELNR " . ($sourceData['order_number'] ?? ('PO' . date('YmdHis'))) . "\n" .
                         "NTGEW 0.000\n" .
                         "BRGEW 0.000\n" .
                         "GEWEI KG\n" .
                         "FKART FL\n" .
                         "ABLAD\n" .
                         "E1EDK03 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000002 PSGNUM 000001 HLEVEL 02\n" .
                         "IDDAT 012\n" .
                         "DATUM " . date('Ymd') . "\n" .
                         "E1EDK04 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000003 PSGNUM 000001 HLEVEL 02\n" .
                         "MWSKZ A1\n" .
                         "E1EDK17 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000004 PSGNUM 000001 HLEVEL 02\n" .
                         "QUALF 002\n" .
                         "LKOND\n" .
                         "LWERT 0.00\n" .
                         "E1EDK14 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000005 PSGNUM 000001 HLEVEL 02\n" .
                         "QUALF 006\n" .
                         "ORGID " . ($sourceData['customer']['id'] ?? 'CUST001') . "\n" .
                         "E1EDK14 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000006 PSGNUM 000001 HLEVEL 02\n" .
                         "QUALF 007\n" .
                         "ORGID " . ($sourceData['customer']['id'] ?? 'CUST001') . "\n" .
                         "E1EDK14 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000007 PSGNUM 000001 HLEVEL 02\n" .
                         "QUALF 008\n" .
                         "ORGID " . ($sourceData['customer']['id'] ?? 'CUST001') . "\n" .
                         "E1EDK14 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000008 PSGNUM 000001 HLEVEL 02\n" .
                         "QUALF 012\n" .
                         "ORGID " . ($sourceData['customer']['id'] ?? 'CUST001') . "\n" .
                         "E1EDKA1 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000009 PSGNUM 000001 HLEVEL 02\n" .
                         "PARVW AG\n" .
                         "PARTN " . ($sourceData['customer']['id'] ?? 'CUST001') . "\n" .
                         "NAME1 " . ($sourceData['customer']['name'] ?? 'Sample Customer') . "\n" .
                         "STRAS\n" .
                         "ORT01\n" .
                         "PSTLZ\n" .
                         "LAND1 US\n" .
                         "SPRAS E\n" .
                         "BNAME\n" .
                         "PAORG\n" .
                         "E1EDP01 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000010 PSGNUM 000001 HLEVEL 02\n" .
                         "POSEX 000010\n" .
                         "MENGE " . ($sourceData['items'][0]['quantity'] ?? 5) . "\n" .
                         "MENEE EA\n" .
                         "NTGEW 0.000\n" .
                         "GEWEI KG\n" .
                         "BRGEW 0.000\n" .
                         "WERKS 1000\n" .
                         "LGORT\n" .
                         "LFIMG " . ($sourceData['items'][0]['quantity'] ?? 5) . "\n" .
                         "VRKME EA\n" .
                         "MEINS EA\n" .
                         "NETWR " . (($sourceData['items'][0]['quantity'] ?? 5) * ($sourceData['items'][0]['price'] ?? 10.99)) . "\n" .
                         "VBELV\n" .
                         "POSNV\n" .
                         "E1EDP19 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000011 PSGNUM 000010 HLEVEL 03\n" .
                         "QUALF 002\n" .
                         "IDTNR " . ($sourceData['items'][0]['sku'] ?? 'ITEM001') . "\n" .
                         "KZAUS\n" .
                         "E1EDP19 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000012 PSGNUM 000010 HLEVEL 03\n" .
                         "QUALF 001\n" .
                         "IDTNR " . ($sourceData['items'][0]['sku'] ?? 'ITEM001') . "\n" .
                         "KZAUS\n" .
                         "E1EDP26 SEGMENT 1      DOCNUM 0000000012345678 SEGNUM 000013 PSGNUM 000010 HLEVEL 03\n" .
                         "QUALF 001\n" .
                         "BETRG " . ($sourceData['items'][0]['price'] ?? 10.99) . "\n" .
                         "ISOCD USD\n"
        ];
        
        return $result;
    }
}