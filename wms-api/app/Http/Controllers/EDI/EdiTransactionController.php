<?php

namespace App\Http\Controllers\EDI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EDI\EdiTransaction;
use App\Models\EDI\EdiAcknowledgment;
use App\Models\EDI\EdiMapping;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EdiTransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = EdiTransaction::with(['tradingPartner', 'documentType', 'mapping']);
            
            // Apply filters
            if ($request->has('trading_partner_id')) {
                $query->where('trading_partner_id', $request->trading_partner_id);
            }
            
            if ($request->has('document_type_id')) {
                $query->where('document_type_id', $request->document_type_id);
            }
            
            if ($request->has('direction')) {
                $query->where('direction', $request->direction);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('reference_number')) {
                $query->where('reference_number', 'like', "%{$request->reference_number}%");
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
                'message' => 'Transactions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving transactions: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'trading_partner_id' => 'required|exists:edi_trading_partners,id',
                'document_type_id' => 'required|exists:edi_document_types,id',
                'mapping_id' => 'nullable|exists:edi_mappings,id',
                'direction' => 'required|string|in:inbound,outbound',
                'reference_number' => 'nullable|string|max:100',
                'transaction_data' => 'required|json',
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
            
            // Generate transaction ID if not provided
            if (!$request->has('transaction_id')) {
                $request->merge(['transaction_id' => Str::uuid()]);
            }
            
            // Store original data file if provided
            $originalDataPath = null;
            if ($request->has('original_data') && !empty($request->original_data)) {
                $originalDataPath = 'edi/transactions/' . date('Y/m/d') . '/' . $request->transaction_id . '.dat';
                Storage::put($originalDataPath, $request->original_data);
            }
            
            // Create transaction
            $transaction = new EdiTransaction([
                'transaction_id' => $request->transaction_id,
                'trading_partner_id' => $request->trading_partner_id,
                'document_type_id' => $request->document_type_id,
                'mapping_id' => $request->mapping_id,
                'direction' => $request->direction,
                'reference_number' => $request->reference_number,
                'transaction_data' => $request->transaction_data,
                'original_data_path' => $originalDataPath,
                'status' => $request->status,
                'processing_notes' => $request->processing_notes,
            ]);
            
            $transaction->save();
            
            return response()->json([
                'status' => 'success',
                'data' => $transaction->load(['tradingPartner', 'documentType', 'mapping']),
                'message' => 'Transaction created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating transaction: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified transaction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $transaction = EdiTransaction::with([
                'tradingPartner',
                'documentType',
                'mapping',
                'acknowledgments'
            ])->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $transaction,
                'message' => 'Transaction retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving transaction: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $transaction = EdiTransaction::findOrFail($id);
            
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
                'data' => $transaction->load(['tradingPartner', 'documentType', 'mapping']),
                'message' => 'Transaction updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating transaction: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process an inbound EDI transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processInbound(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'trading_partner_id' => 'required|exists:edi_trading_partners,id',
                'document_type_id' => 'required|exists:edi_document_types,id',
                'edi_data' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Find appropriate mapping
            $mapping = EdiMapping::where('trading_partner_id', $request->trading_partner_id)
                ->where('document_type_id', $request->document_type_id)
                ->where('direction', 'inbound')
                ->where('is_active', true)
                ->first();
                
            if (!$mapping) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active inbound mapping found for this trading partner and document type'
                ], 422);
            }
            
            // Generate transaction ID
            $transactionId = Str::uuid();
            
            // Store original EDI data
            $originalDataPath = 'edi/transactions/' . date('Y/m/d') . '/' . $transactionId . '.dat';
            Storage::put($originalDataPath, $request->edi_data);
            
            // Process EDI data using mapping
            // This is a placeholder - actual implementation would use appropriate EDI parser and mapping engine
            $processedData = $this->processEdiData($request->edi_data, $mapping);
            
            // Create transaction record
            $transaction = new EdiTransaction([
                'transaction_id' => $transactionId,
                'trading_partner_id' => $request->trading_partner_id,
                'document_type_id' => $request->document_type_id,
                'mapping_id' => $mapping->id,
                'direction' => 'inbound',
                'reference_number' => $processedData['reference_number'] ?? null,
                'transaction_data' => $processedData['data'],
                'original_data_path' => $originalDataPath,
                'status' => $processedData['success'] ? 'processed' : 'error',
                'processing_notes' => $processedData['notes'] ?? null,
                'error_message' => $processedData['error'] ?? null,
                'processed_at' => now(),
            ]);
            
            $transaction->save();
            
            // Create acknowledgment if needed
            if ($request->has('requires_acknowledgment') && $request->requires_acknowledgment) {
                $acknowledgment = new EdiAcknowledgment([
                    'transaction_id' => $transaction->id,
                    'acknowledgment_type' => '997', // Default to 997 functional acknowledgment
                    'status' => 'pending',
                    'acknowledgment_data' => null,
                ]);
                
                $acknowledgment->save();
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'transaction' => $transaction->load(['tradingPartner', 'documentType', 'mapping']),
                    'processed_data' => $processedData['data']
                ],
                'message' => $processedData['success'] ? 'EDI transaction processed successfully' : 'EDI transaction processed with errors'
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing inbound EDI transaction: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process EDI transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate an outbound EDI transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateOutbound(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'trading_partner_id' => 'required|exists:edi_trading_partners,id',
                'document_type_id' => 'required|exists:edi_document_types,id',
                'source_data' => 'required|json',
                'reference_number' => 'nullable|string|max:100',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Find appropriate mapping
            $mapping = EdiMapping::where('trading_partner_id', $request->trading_partner_id)
                ->where('document_type_id', $request->document_type_id)
                ->where('direction', 'outbound')
                ->where('is_active', true)
                ->first();
                
            if (!$mapping) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active outbound mapping found for this trading partner and document type'
                ], 422);
            }
            
            // Generate transaction ID
            $transactionId = Str::uuid();
            
            // Generate EDI data using mapping
            // This is a placeholder - actual implementation would use appropriate mapping engine and EDI generator
            $sourceData = json_decode($request->source_data, true);
            $generatedEdi = $this->generateEdiData($sourceData, $mapping);
            
            // Store generated EDI data
            $ediDataPath = 'edi/transactions/' . date('Y/m/d') . '/' . $transactionId . '.dat';
            Storage::put($ediDataPath, $generatedEdi['edi_data']);
            
            // Create transaction record
            $transaction = new EdiTransaction([
                'transaction_id' => $transactionId,
                'trading_partner_id' => $request->trading_partner_id,
                'document_type_id' => $request->document_type_id,
                'mapping_id' => $mapping->id,
                'direction' => 'outbound',
                'reference_number' => $request->reference_number ?? ($generatedEdi['reference_number'] ?? null),
                'transaction_data' => $sourceData,
                'original_data_path' => $ediDataPath,
                'status' => $generatedEdi['success'] ? 'generated' : 'error',
                'processing_notes' => $generatedEdi['notes'] ?? null,
                'error_message' => $generatedEdi['error'] ?? null,
                'processed_at' => now(),
            ]);
            
            $transaction->save();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'transaction' => $transaction->load(['tradingPartner', 'documentType', 'mapping']),
                    'edi_data' => $generatedEdi['edi_data']
                ],
                'message' => $generatedEdi['success'] ? 'EDI transaction generated successfully' : 'EDI transaction generated with errors'
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating outbound EDI transaction: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate EDI transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the original EDI data for a transaction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getOriginalData($id)
    {
        try {
            $transaction = EdiTransaction::findOrFail($id);
            
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
                    'transaction_id' => $transaction->transaction_id,
                    'original_data' => $originalData
                ],
                'message' => 'Original data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving original data: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve original data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process EDI data using mapping.
     *
     * @param  string  $ediData
     * @param  \App\Models\EDI\EdiMapping  $mapping
     * @return array
     */
    private function processEdiData($ediData, $mapping)
    {
        // This is a placeholder - actual implementation would use appropriate EDI parser and mapping engine
        $result = [
            'success' => true,
            'reference_number' => 'PO' . date('YmdHis'),
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
     * Generate EDI data from source data using mapping.
     *
     * @param  array  $sourceData
     * @param  \App\Models\EDI\EdiMapping  $mapping
     * @return array
     */
    private function generateEdiData($sourceData, $mapping)
    {
        // This is a placeholder - actual implementation would use appropriate mapping engine and EDI generator
        $result = [
            'success' => true,
            'reference_number' => $sourceData['order_number'] ?? ('PO' . date('YmdHis')),
            'notes' => 'Generated successfully',
            'edi_data' => "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>\n" .
                         "GS*PO*SENDER*RECEIVER*20230101*1200*1*X*004010\n" .
                         "ST*850*0001\n" .
                         "BEG*00*SA*" . ($sourceData['order_number'] ?? ('PO' . date('YmdHis'))) . "*" . date('Ymd') . "\n" .
                         "N1*ST*" . ($sourceData['customer']['name'] ?? 'Sample Customer') . "\n" .
                         "PO1*1*" . ($sourceData['items'][0]['quantity'] ?? 5) . "*EA*" . ($sourceData['items'][0]['price'] ?? 10.99) . "**VC*" . ($sourceData['items'][0]['sku'] ?? 'ITEM001') . "\n" .
                         "CTT*1\n" .
                         "SE*6*0001\n" .
                         "GE*1*1\n" .
                         "IEA*1*000000001\n"
        ];
        
        return $result;
    }
}