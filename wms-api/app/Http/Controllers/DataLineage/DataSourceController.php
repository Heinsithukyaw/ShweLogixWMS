<?php

namespace App\Http\Controllers\DataLineage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataLineage\DataSource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DataSourceController extends Controller
{
    /**
     * Display a listing of data sources.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = DataSource::query();
            
            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('source_type')) {
                $query->where('source_type', $request->source_type);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('source_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $sources = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $sources,
                'message' => 'Data sources retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving data sources: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data sources',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created data source.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'source_code' => 'required|string|max:50|unique:data_sources',
                'description' => 'nullable|string',
                'source_type' => 'required|string|max:50',
                'connection_details' => 'required|json',
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
            
            $source = DataSource::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $source,
                'message' => 'Data source created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating data source: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create data source',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified data source.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $source = DataSource::with(['dataEntities', 'dataFlows'])
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $source,
                'message' => 'Data source retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving data source: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Data source not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified data source.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $source = DataSource::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'source_code' => 'string|max:50|unique:data_sources,source_code,' . $id,
                'description' => 'nullable|string',
                'source_type' => 'string|max:50',
                'connection_details' => 'json',
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
            
            $source->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $source,
                'message' => 'Data source updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating data source: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update data source',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified data source.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $source = DataSource::findOrFail($id);
            
            // Check if there are related entities or flows
            if ($source->dataEntities()->count() > 0 || $source->dataFlows()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete data source with associated entities or flows'
                ], 422);
            }
            
            $source->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data source deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting data source: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete data source',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test connection to data source.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function testConnection($id)
    {
        try {
            $source = DataSource::findOrFail($id);
            
            // Implement connection test based on source type
            $sourceType = $source->source_type;
            $connectionDetails = $source->connection_details;
            $testResult = false;
            $testMessage = '';
            
            switch ($sourceType) {
                case 'mysql':
                case 'postgresql':
                case 'sqlserver':
                case 'oracle':
                    // Test database connection
                    $testResult = $this->testDatabaseConnection($sourceType, $connectionDetails);
                    $testMessage = $testResult ? 'Database connection successful' : 'Database connection failed';
                    break;
                    
                case 'rest_api':
                case 'soap_api':
                case 'graphql':
                    // Test API connection
                    $testResult = $this->testApiConnection($sourceType, $connectionDetails);
                    $testMessage = $testResult ? 'API connection successful' : 'API connection failed';
                    break;
                    
                case 'csv':
                case 'excel':
                case 'json':
                case 'xml':
                    // Test file access
                    $testResult = $this->testFileAccess($sourceType, $connectionDetails);
                    $testMessage = $testResult ? 'File access successful' : 'File access failed';
                    break;
                    
                case 'sap':
                case 'salesforce':
                case 'shopify':
                case 'woocommerce':
                    // Test integration connection
                    $testResult = $this->testIntegrationConnection($sourceType, $connectionDetails);
                    $testMessage = $testResult ? 'Integration connection successful' : 'Integration connection failed';
                    break;
                    
                default:
                    $testMessage = 'Unsupported source type';
                    break;
            }
            
            return response()->json([
                'status' => $testResult ? 'success' : 'error',
                'data' => [
                    'source_type' => $sourceType,
                    'test_result' => $testResult,
                    'test_message' => $testMessage
                ],
                'message' => $testMessage
            ]);
        } catch (\Exception $e) {
            Log::error('Error testing connection to data source: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to test connection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test database connection.
     *
     * @param  string  $sourceType
     * @param  array  $connectionDetails
     * @return bool
     */
    private function testDatabaseConnection($sourceType, $connectionDetails)
    {
        try {
            $host = $connectionDetails['host'] ?? '';
            $port = $connectionDetails['port'] ?? '';
            $database = $connectionDetails['database'] ?? '';
            $username = $connectionDetails['username'] ?? '';
            $password = $connectionDetails['password'] ?? '';
            
            if (empty($host) || empty($database) || empty($username)) {
                return false;
            }
            
            // This is a placeholder - actual implementation would use appropriate database connection
            return true;
        } catch (\Exception $e) {
            Log::error('Database connection test error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test API connection.
     *
     * @param  string  $sourceType
     * @param  array  $connectionDetails
     * @return bool
     */
    private function testApiConnection($sourceType, $connectionDetails)
    {
        try {
            $url = $connectionDetails['url'] ?? '';
            
            if (empty($url)) {
                return false;
            }
            
            // This is a placeholder - actual implementation would use HTTP client
            return true;
        } catch (\Exception $e) {
            Log::error('API connection test error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test file access.
     *
     * @param  string  $sourceType
     * @param  array  $connectionDetails
     * @return bool
     */
    private function testFileAccess($sourceType, $connectionDetails)
    {
        try {
            $path = $connectionDetails['path'] ?? '';
            
            if (empty($path)) {
                return false;
            }
            
            // This is a placeholder - actual implementation would check file existence and permissions
            return true;
        } catch (\Exception $e) {
            Log::error('File access test error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test integration connection.
     *
     * @param  string  $sourceType
     * @param  array  $connectionDetails
     * @return bool
     */
    private function testIntegrationConnection($sourceType, $connectionDetails)
    {
        try {
            // This is a placeholder - actual implementation would use appropriate integration SDK
            return true;
        } catch (\Exception $e) {
            Log::error('Integration connection test error: ' . $e->getMessage());
            return false;
        }
    }
}