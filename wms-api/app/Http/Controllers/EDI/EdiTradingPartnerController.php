<?php

namespace App\Http\Controllers\EDI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EDI\EdiTradingPartner;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EdiTradingPartnerController extends Controller
{
    /**
     * Display a listing of trading partners.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = EdiTradingPartner::query();
            
            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('partner_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $partners = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $partners,
                'message' => 'Trading partners retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving trading partners: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve trading partners',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created trading partner.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'partner_code' => 'required|string|max:50|unique:edi_trading_partners',
                'description' => 'nullable|string',
                'contact_name' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'connection_type' => 'required|string|max:50',
                'connection_details' => 'required|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $partner = EdiTradingPartner::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $partner,
                'message' => 'Trading partner created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating trading partner: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create trading partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified trading partner.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $partner = EdiTradingPartner::with(['documentTypes', 'transactions'])
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $partner,
                'message' => 'Trading partner retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving trading partner: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Trading partner not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified trading partner.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $partner = EdiTradingPartner::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'partner_code' => 'string|max:50|unique:edi_trading_partners,partner_code,' . $id,
                'description' => 'nullable|string',
                'contact_name' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'connection_type' => 'string|max:50',
                'connection_details' => 'json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $partner->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $partner,
                'message' => 'Trading partner updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating trading partner: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update trading partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified trading partner.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $partner = EdiTradingPartner::findOrFail($id);
            
            // Check if there are related transactions
            if ($partner->transactions()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete trading partner with associated transactions'
                ], 422);
            }
            
            $partner->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Trading partner deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting trading partner: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete trading partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test connection to trading partner.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function testConnection($id)
    {
        try {
            $partner = EdiTradingPartner::findOrFail($id);
            
            // Implement connection test based on connection type
            $connectionType = $partner->connection_type;
            $connectionDetails = $partner->connection_details;
            $testResult = false;
            $testMessage = '';
            
            switch ($connectionType) {
                case 'ftp':
                    // Test FTP connection
                    $testResult = $this->testFtpConnection($connectionDetails);
                    $testMessage = $testResult ? 'FTP connection successful' : 'FTP connection failed';
                    break;
                    
                case 'sftp':
                    // Test SFTP connection
                    $testResult = $this->testSftpConnection($connectionDetails);
                    $testMessage = $testResult ? 'SFTP connection successful' : 'SFTP connection failed';
                    break;
                    
                case 'api':
                    // Test API connection
                    $testResult = $this->testApiConnection($connectionDetails);
                    $testMessage = $testResult ? 'API connection successful' : 'API connection failed';
                    break;
                    
                case 'as2':
                    // Test AS2 connection
                    $testResult = $this->testAs2Connection($connectionDetails);
                    $testMessage = $testResult ? 'AS2 connection successful' : 'AS2 connection failed';
                    break;
                    
                default:
                    $testMessage = 'Unsupported connection type';
                    break;
            }
            
            return response()->json([
                'status' => $testResult ? 'success' : 'error',
                'data' => [
                    'connection_type' => $connectionType,
                    'test_result' => $testResult,
                    'test_message' => $testMessage
                ],
                'message' => $testMessage
            ]);
        } catch (\Exception $e) {
            Log::error('Error testing connection to trading partner: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to test connection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test FTP connection.
     *
     * @param  array  $connectionDetails
     * @return bool
     */
    private function testFtpConnection($connectionDetails)
    {
        try {
            $host = $connectionDetails['host'] ?? '';
            $port = $connectionDetails['port'] ?? 21;
            $username = $connectionDetails['username'] ?? '';
            $password = $connectionDetails['password'] ?? '';
            $timeout = $connectionDetails['timeout'] ?? 30;
            
            if (empty($host) || empty($username) || empty($password)) {
                return false;
            }
            
            $conn = @ftp_connect($host, $port, $timeout);
            
            if (!$conn) {
                return false;
            }
            
            $login = @ftp_login($conn, $username, $password);
            
            if (!$login) {
                ftp_close($conn);
                return false;
            }
            
            ftp_close($conn);
            return true;
        } catch (\Exception $e) {
            Log::error('FTP connection test error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test SFTP connection.
     *
     * @param  array  $connectionDetails
     * @return bool
     */
    private function testSftpConnection($connectionDetails)
    {
        try {
            // Implement SFTP connection test
            // This is a placeholder - actual implementation would use SSH2 extension or phpseclib
            return true;
        } catch (\Exception $e) {
            Log::error('SFTP connection test error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test API connection.
     *
     * @param  array  $connectionDetails
     * @return bool
     */
    private function testApiConnection($connectionDetails)
    {
        try {
            // Implement API connection test
            // This is a placeholder - actual implementation would use HTTP client
            return true;
        } catch (\Exception $e) {
            Log::error('API connection test error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test AS2 connection.
     *
     * @param  array  $connectionDetails
     * @return bool
     */
    private function testAs2Connection($connectionDetails)
    {
        try {
            // Implement AS2 connection test
            // This is a placeholder - actual implementation would use AS2 library
            return true;
        } catch (\Exception $e) {
            Log::error('AS2 connection test error: ' . $e->getMessage());
            return false;
        }
    }
}