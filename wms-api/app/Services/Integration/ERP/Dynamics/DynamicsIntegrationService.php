<?php

namespace App\Services\Integration\ERP\Dynamics;

use App\Services\Integration\ERP\BaseERPService;
use App\Services\EventLogService;
use App\Services\IdempotencyService;
use Exception;

class DynamicsIntegrationService extends BaseERPService
{
    protected $provider = 'dynamics';
    protected $accessToken;
    protected $baseUrl;
    protected $tenantId;
    protected $clientId;
    protected $clientSecret;
    protected $resource;

    public function __construct(
        EventLogService $eventService,
        IdempotencyService $idempotencyService
    ) {
        parent::__construct($eventService, $idempotencyService);
        $this->initializeDynamicsConnection();
    }

    /**
     * Initialize Dynamics connection parameters
     */
    protected function initializeDynamicsConnection()
    {
        $this->baseUrl = $this->config['endpoint'] ?? '';
        $this->tenantId = $this->config['tenant_id'] ?? '';
        $this->clientId = $this->config['client_id'] ?? '';
        $this->clientSecret = $this->config['client_secret'] ?? '';
        $this->resource = $this->config['resource'] ?? 'https://dynamics.microsoft.com/';
    }

    /**
     * Authenticate with Microsoft Dynamics 365
     */
    public function authenticate(): bool
    {
        try {
            if (!$this->validateConfiguration(['endpoint', 'tenant_id', 'client_id', 'client_secret'])) {
                return false;
            }

            // Microsoft Azure AD OAuth2 authentication
            $authUrl = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/token";
            
            $response = $this->executeRequest('POST', $authUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'resource' => $this->resource
            ], [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]);

            if ($response['success'] && isset($response['data']['access_token'])) {
                $this->accessToken = $response['data']['access_token'];
                $this->cacheData('auth_token', $this->accessToken, $response['data']['expires_in'] ?? 3600);
                
                $this->emitEvent('authentication_success', [
                    'provider' => $this->provider,
                    'timestamp' => now()
                ]);

                return true;
            }

            return false;

        } catch (Exception $e) {
            $this->logger->error('Dynamics authentication failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantId
            ]);
            return false;
        }
    }

    /**
     * Test Dynamics connection
     */
    public function testConnection(): bool
    {
        try {
            if (!$this->accessToken) {
                $this->authenticate();
            }

            $response = $this->executeRequest('GET', $this->baseUrl . '/api/data/v9.0/products', [], [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
                'OData-MaxVersion' => '4.0',
                'OData-Version' => '4.0'
            ]);

            return $response['success'];

        } catch (Exception $e) {
            $this->logger->error('Dynamics connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get Dynamics integration status
     */
    public function getStatus(): array
    {
        return [
            'provider' => $this->provider,
            'enabled' => $this->isEnabled(),
            'authenticated' => !empty($this->accessToken),
            'connection_status' => $this->testConnection() ? 'connected' : 'disconnected',
            'last_sync' => $this->getLastSyncTime(),
            'metrics' => $this->getMetrics()
        ];
    }

    /**
     * Sync data with Dynamics
     */
    public function syncData(string $dataType, array $data): array
    {
        return $this->processWithIdempotency("sync_{$dataType}", $data, function() use ($dataType, $data) {
            switch ($dataType) {
                case 'products':
                    return $this->syncProducts($data);
                case 'customers':
                    return $this->syncCustomers($data);
                case 'suppliers':
                    return $this->syncSuppliers($data);
                case 'purchase_orders':
                    return $this->syncPurchaseOrders($data);
                case 'sales_orders':
                    return $this->syncSalesOrders($data);
                default:
                    throw new Exception("Unsupported data type: {$dataType}");
            }
        });
    }

    /**
     * Sync master data with Dynamics
     */
    public function syncMasterData(string $dataType, array $data): array
    {
        switch ($dataType) {
            case 'products':
                return $this->syncProducts($data);
            case 'customers':
                return $this->syncCustomers($data);
            case 'suppliers':
                return $this->syncSuppliers($data);
            default:
                throw new Exception("Unsupported master data type: {$dataType}");
        }
    }

    /**
     * Sync transaction data with Dynamics
     */
    public function syncTransactionData(string $dataType, array $data): array
    {
        switch ($dataType) {
            case 'purchase_orders':
                return $this->syncPurchaseOrders($data);
            case 'sales_orders':
                return $this->syncSalesOrders($data);
            case 'invoices':
                return $this->syncInvoices($data);
            case 'payments':
                return $this->syncPayments($data);
            default:
                throw new Exception("Unsupported transaction data type: {$dataType}");
        }
    }

    /**
     * Sync products with Dynamics
     */
    protected function syncProducts(array $products): array
    {
        $results = [];
        
        foreach ($products as $product) {
            try {
                $dynamicsProduct = $this->transformProductForDynamics($product);
                
                // Check if product exists in Dynamics
                $existingProduct = $this->getProductFromDynamics($dynamicsProduct['productnumber']);
                
                if ($existingProduct) {
                    $result = $this->updateProductInDynamics($existingProduct['productid'], $dynamicsProduct);
                } else {
                    $result = $this->createProductInDynamics($dynamicsProduct);
                }
                
                $results[] = [
                    'sku' => $product['sku'],
                    'success' => $result['success'],
                    'dynamics_product' => $result['data']['productid'] ?? null,
                    'action' => $existingProduct ? 'updated' : 'created'
                ];

            } catch (Exception $e) {
                $results[] = [
                    'sku' => $product['sku'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'processed' => count($results),
            'results' => $results
        ];
    }

    /**
     * Transform product data for Dynamics
     */
    protected function transformProductForDynamics(array $product): array
    {
        return [
            'productnumber' => $product['sku'],
            'name' => $product['name'],
            'description' => $product['description'] ?? '',
            'productstructure' => 1, // Product
            'producttypecode' => 1, // Sales Inventory
            'quantitydecimal' => 2,
            'defaultuomid@odata.bind' => '/uoms(' . $this->getUOMId($product['uom'] ?? 'EA') . ')',
            'defaultuomscheduleid@odata.bind' => '/uomschedules(' . $this->getUOMScheduleId() . ')',
            'price' => $product['price'] ?? 0,
            'standardcost' => $product['cost'] ?? 0,
            'currentcost' => $product['cost'] ?? 0,
            'statecode' => 0, // Active
            'statuscode' => 1 // Active
        ];
    }

    /**
     * Get product from Dynamics
     */
    protected function getProductFromDynamics(string $productNumber): ?array
    {
        $filter = "\$filter=productnumber eq '{$productNumber}'";
        
        $response = $this->executeRequest(
            'GET',
            $this->baseUrl . "/api/data/v9.0/products?{$filter}",
            [],
            $this->getAuthHeaders()
        );

        if ($response['success'] && !empty($response['data']['value'])) {
            return $response['data']['value'][0];
        }

        return null;
    }

    /**
     * Create product in Dynamics
     */
    protected function createProductInDynamics(array $productData): array
    {
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/api/data/v9.0/products',
            $productData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Update product in Dynamics
     */
    protected function updateProductInDynamics(string $productId, array $productData): array
    {
        return $this->executeRequest(
            'PATCH',
            $this->baseUrl . "/api/data/v9.0/products({$productId})",
            $productData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Sync customers with Dynamics
     */
    protected function syncCustomers(array $customers): array
    {
        $results = [];
        
        foreach ($customers as $customer) {
            try {
                $dynamicsCustomer = $this->transformCustomerForDynamics($customer);
                
                $result = $this->createCustomerInDynamics($dynamicsCustomer);
                
                $results[] = [
                    'customer_id' => $customer['customer_id'],
                    'success' => $result['success'],
                    'dynamics_account' => $result['data']['accountid'] ?? null
                ];

            } catch (Exception $e) {
                $results[] = [
                    'customer_id' => $customer['customer_id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'processed' => count($results),
            'results' => $results
        ];
    }

    /**
     * Transform customer data for Dynamics
     */
    protected function transformCustomerForDynamics(array $customer): array
    {
        return [
            'accountnumber' => $customer['customer_id'],
            'name' => $customer['name'],
            'customertypecode' => 3, // Customer
            'accountcategorycode' => 1, // Preferred Customer
            'emailaddress1' => $customer['email'] ?? '',
            'telephone1' => $customer['phone'] ?? '',
            'address1_line1' => $customer['address'] ?? '',
            'address1_city' => $customer['city'] ?? '',
            'address1_stateorprovince' => $customer['state'] ?? '',
            'address1_postalcode' => $customer['postal_code'] ?? '',
            'address1_country' => $customer['country'] ?? '',
            'statecode' => 0, // Active
            'statuscode' => 1 // Active
        ];
    }

    /**
     * Create customer in Dynamics
     */
    protected function createCustomerInDynamics(array $customerData): array
    {
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/api/data/v9.0/accounts',
            $customerData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Get inventory levels from Dynamics
     */
    public function getInventoryLevels(array $productIds = []): array
    {
        $filter = '';
        if (!empty($productIds)) {
            $products = implode("','", $productIds);
            $filter = "?\$filter=_productid_value in ('{$products}')";
        }

        $response = $this->executeRequest(
            'GET',
            $this->baseUrl . '/api/data/v9.0/msdyn_inventorylevels' . $filter,
            [],
            $this->getAuthHeaders()
        );

        if ($response['success']) {
            return [
                'success' => true,
                'inventory' => $this->transformDynamicsInventoryData($response['data']['value'] ?? [])
            ];
        }

        return $response;
    }

    /**
     * Transform Dynamics inventory data
     */
    protected function transformDynamicsInventoryData(array $dynamicsInventory): array
    {
        return array_map(function($item) {
            return [
                'sku' => $item['_productid_value'],
                'warehouse_id' => $item['_msdyn_warehouse_value'],
                'available_qty' => $item['msdyn_availableonhand'] ?? 0,
                'reserved_qty' => $item['msdyn_reservedonhand'] ?? 0,
                'on_order_qty' => $item['msdyn_onorder'] ?? 0,
                'last_updated' => now()->toISOString()
            ];
        }, $dynamicsInventory);
    }

    /**
     * Update inventory levels in Dynamics
     */
    public function updateInventoryLevels(array $inventoryData): array
    {
        $results = [];
        
        foreach ($inventoryData as $inventory) {
            try {
                $result = $this->createInventoryJournal($inventory);
                
                $results[] = [
                    'sku' => $inventory['sku'],
                    'success' => $result['success'],
                    'journal_id' => $result['data']['msdyn_inventoryjournalid'] ?? null
                ];

            } catch (Exception $e) {
                $results[] = [
                    'sku' => $inventory['sku'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'processed' => count($results),
            'results' => $results
        ];
    }

    /**
     * Create inventory journal in Dynamics
     */
    protected function createInventoryJournal(array $inventoryData): array
    {
        $journalData = [
            'msdyn_name' => 'WMS Adjustment - ' . date('Y-m-d H:i:s'),
            'msdyn_journaltype' => 192350000, // Adjustment
            'msdyn_productid@odata.bind' => '/products(' . $this->getProductId($inventoryData['sku']) . ')',
            'msdyn_warehouse@odata.bind' => '/msdyn_warehouses(' . $this->getWarehouseId($inventoryData['warehouse_id']) . ')',
            'msdyn_quantity' => $inventoryData['quantity'],
            'msdyn_transactiondate' => $inventoryData['transaction_date'] ?? date('Y-m-d'),
            'statecode' => 0,
            'statuscode' => 1
        ];

        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/api/data/v9.0/msdyn_inventoryjournals',
            $journalData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create purchase order in Dynamics
     */
    public function createPurchaseOrder(array $orderData): array
    {
        $dynamicsPO = $this->transformPurchaseOrderForDynamics($orderData);
        
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/api/data/v9.0/msdyn_purchaseorders',
            $dynamicsPO,
            $this->getAuthHeaders()
        );
    }

    /**
     * Transform purchase order data for Dynamics
     */
    protected function transformPurchaseOrderForDynamics(array $orderData): array
    {
        return [
            'msdyn_name' => $orderData['order_id'],
            'msdyn_orderdate' => $orderData['order_date'] ?? date('Y-m-d'),
            'msdyn_vendor@odata.bind' => '/accounts(' . $this->getVendorId($orderData['supplier_id']) . ')',
            'msdyn_totalamount' => $orderData['total'] ?? 0,
            'transactioncurrencyid@odata.bind' => '/transactioncurrencies(' . $this->getCurrencyId($orderData['currency'] ?? 'USD') . ')',
            'msdyn_orderstatus' => 192350000, // Draft
            'statecode' => 0,
            'statuscode' => 1
        ];
    }

    /**
     * Update purchase order status
     */
    public function updatePurchaseOrderStatus(string $orderId, string $status): array
    {
        $statusData = [
            'msdyn_orderstatus' => $this->mapStatusToDynamics($status)
        ];

        return $this->executeRequest(
            'PATCH',
            $this->baseUrl . "/api/data/v9.0/msdyn_purchaseorders({$orderId})",
            $statusData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create goods receipt in Dynamics
     */
    public function createGoodsReceipt(array $receiptData): array
    {
        $dynamicsReceipt = [
            'msdyn_name' => $receiptData['receipt_id'],
            'msdyn_receiptdate' => $receiptData['receipt_date'] ?? date('Y-m-d'),
            'msdyn_purchaseorder@odata.bind' => '/msdyn_purchaseorders(' . $this->getPurchaseOrderId($receiptData['purchase_order']) . ')',
            'statecode' => 0,
            'statuscode' => 1
        ];

        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/api/data/v9.0/msdyn_purchaseorderreceipts',
            $dynamicsReceipt,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create sales order in Dynamics
     */
    public function createSalesOrder(array $orderData): array
    {
        $dynamicsSO = $this->transformSalesOrderForDynamics($orderData);
        
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/api/data/v9.0/salesorders',
            $dynamicsSO,
            $this->getAuthHeaders()
        );
    }

    /**
     * Transform sales order data for Dynamics
     */
    protected function transformSalesOrderForDynamics(array $orderData): array
    {
        return [
            'ordernumber' => $orderData['order_id'],
            'customerid_account@odata.bind' => '/accounts(' . $this->getCustomerId($orderData['customer_id']) . ')',
            'datefulfilled' => $orderData['delivery_date'] ?? date('Y-m-d', strtotime('+7 days')),
            'totalamount' => $orderData['total'] ?? 0,
            'transactioncurrencyid@odata.bind' => '/transactioncurrencies(' . $this->getCurrencyId($orderData['currency'] ?? 'USD') . ')',
            'statecode' => 0, // Active
            'statuscode' => 1 // New
        ];
    }

    /**
     * Update sales order status
     */
    public function updateSalesOrderStatus(string $orderId, string $status): array
    {
        $statusData = [
            'statuscode' => $this->mapSalesOrderStatusToDynamics($status)
        ];

        return $this->executeRequest(
            'PATCH',
            $this->baseUrl . "/api/data/v9.0/salesorders({$orderId})",
            $statusData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create shipment confirmation in Dynamics
     */
    public function createShipmentConfirmation(array $shipmentData): array
    {
        $dynamicsShipment = [
            'msdyn_name' => $shipmentData['shipment_id'],
            'msdyn_shipdate' => $shipmentData['ship_date'] ?? date('Y-m-d'),
            'msdyn_salesorder@odata.bind' => '/salesorders(' . $this->getSalesOrderId($shipmentData['sales_order']) . ')',
            'msdyn_trackingnumber' => $shipmentData['tracking_number'] ?? '',
            'statecode' => 0,
            'statuscode' => 1
        ];

        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/api/data/v9.0/msdyn_shipments',
            $dynamicsShipment,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create inventory adjustment in Dynamics
     */
    public function createInventoryAdjustment(array $adjustmentData): array
    {
        return $this->createInventoryJournal([
            'sku' => $adjustmentData['sku'],
            'warehouse_id' => $adjustmentData['warehouse_id'],
            'quantity' => $adjustmentData['adjustment_quantity'],
            'transaction_date' => $adjustmentData['adjustment_date'] ?? date('Y-m-d')
        ]);
    }

    /**
     * Get financial data from Dynamics
     */
    public function getFinancialData(string $dataType, array $filters = []): array
    {
        switch ($dataType) {
            case 'accounts':
                return $this->getChartOfAccounts($filters);
            case 'budgets':
                return $this->getBudgets($filters);
            case 'transactions':
                return $this->getTransactions($filters);
            default:
                throw new Exception("Unsupported financial data type: {$dataType}");
        }
    }

    /**
     * Get chart of accounts from Dynamics
     */
    protected function getChartOfAccounts(array $filters = []): array
    {
        $response = $this->executeRequest(
            'GET',
            $this->baseUrl . '/api/data/v9.0/msdyn_accounts',
            $filters,
            $this->getAuthHeaders()
        );

        return $response;
    }

    /**
     * Get budgets from Dynamics
     */
    protected function getBudgets(array $filters = []): array
    {
        $response = $this->executeRequest(
            'GET',
            $this->baseUrl . '/api/data/v9.0/msdyn_budgets',
            $filters,
            $this->getAuthHeaders()
        );

        return $response;
    }

    /**
     * Get transactions from Dynamics
     */
    protected function getTransactions(array $filters = []): array
    {
        $response = $this->executeRequest(
            'GET',
            $this->baseUrl . '/api/data/v9.0/msdyn_transactions',
            $filters,
            $this->getAuthHeaders()
        );

        return $response;
    }

    /**
     * Map WMS status to Dynamics status
     */
    protected function mapStatusToDynamics(string $status): int
    {
        $statusMap = [
            'pending' => 192350000, // Draft
            'confirmed' => 192350001, // Confirmed
            'in_progress' => 192350002, // In Progress
            'completed' => 192350003, // Completed
            'cancelled' => 192350004 // Cancelled
        ];

        return $statusMap[$status] ?? 192350000;
    }

    /**
     * Map sales order status to Dynamics
     */
    protected function mapSalesOrderStatusToDynamics(string $status): int
    {
        $statusMap = [
            'pending' => 1, // New
            'confirmed' => 100001, // In Progress
            'in_progress' => 100002, // Invoiced
            'completed' => 100003, // Fulfilled
            'cancelled' => 100004 // Cancelled
        ];

        return $statusMap[$status] ?? 1;
    }

    /**
     * Get authentication headers for Dynamics requests
     */
    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'OData-MaxVersion' => '4.0',
            'OData-Version' => '4.0',
            'Prefer' => 'return=representation'
        ];
    }

    /**
     * Helper methods to get entity IDs
     */
    protected function getUOMId(string $uom): string
    {
        // This would typically query Dynamics to get the UOM ID
        // For now, return a placeholder
        return '00000000-0000-0000-0000-000000000000';
    }

    protected function getUOMScheduleId(): string
    {
        // This would typically query Dynamics to get the UOM Schedule ID
        return '00000000-0000-0000-0000-000000000000';
    }

    protected function getProductId(string $sku): string
    {
        // This would typically query Dynamics to get the Product ID by SKU
        return '00000000-0000-0000-0000-000000000000';
    }

    protected function getWarehouseId(string $warehouseCode): string
    {
        // This would typically query Dynamics to get the Warehouse ID
        return '00000000-0000-0000-0000-000000000000';
    }

    protected function getVendorId(string $vendorCode): string
    {
        // This would typically query Dynamics to get the Vendor ID
        return '00000000-0000-0000-0000-000000000000';
    }

    protected function getCurrencyId(string $currencyCode): string
    {
        // This would typically query Dynamics to get the Currency ID
        return '00000000-0000-0000-0000-000000000000';
    }

    protected function getCustomerId(string $customerCode): string
    {
        // This would typically query Dynamics to get the Customer ID
        return '00000000-0000-0000-0000-000000000000';
    }

    protected function getPurchaseOrderId(string $poNumber): string
    {
        // This would typically query Dynamics to get the PO ID
        return '00000000-0000-0000-0000-000000000000';
    }

    protected function getSalesOrderId(string $soNumber): string
    {
        // This would typically query Dynamics to get the SO ID
        return '00000000-0000-0000-0000-000000000000';
    }

    /**
     * Sync suppliers with Dynamics
     */
    protected function syncSuppliers(array $suppliers): array
    {
        $results = [];
        
        foreach ($suppliers as $supplier) {
            try {
                $dynamicsSupplier = $this->transformSupplierForDynamics($supplier);
                
                $result = $this->createSupplierInDynamics($dynamicsSupplier);
                
                $results[] = [
                    'supplier_id' => $supplier['supplier_id'],
                    'success' => $result['success'],
                    'dynamics_account' => $result['data']['accountid'] ?? null
                ];

            } catch (Exception $e) {
                $results[] = [
                    'supplier_id' => $supplier['supplier_id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'processed' => count($results),
            'results' => $results
        ];
    }

    /**
     * Transform supplier data for Dynamics
     */
    protected function transformSupplierForDynamics(array $supplier): array
    {
        return [
            'accountnumber' => $supplier['supplier_id'],
            'name' => $supplier['name'],
            'customertypecode' => 1, // Vendor
            'accountcategorycode' => 2, // Standard
            'emailaddress1' => $supplier['email'] ?? '',
            'telephone1' => $supplier['phone'] ?? '',
            'address1_line1' => $supplier['address'] ?? '',
            'address1_city' => $supplier['city'] ?? '',
            'address1_stateorprovince' => $supplier['state'] ?? '',
            'address1_postalcode' => $supplier['postal_code'] ?? '',
            'address1_country' => $supplier['country'] ?? '',
            'statecode' => 0, // Active
            'statuscode' => 1 // Active
        ];
    }

    /**
     * Create supplier in Dynamics
     */
    protected function createSupplierInDynamics(array $supplierData): array
    {
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/api/data/v9.0/accounts',
            $supplierData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Sync purchase orders with Dynamics
     */
    protected function syncPurchaseOrders(array $orders): array
    {
        $results = [];
        
        foreach ($orders as $order) {
            try {
                $result = $this->createPurchaseOrder($order);
                
                $results[] = [
                    'order_id' => $order['order_id'],
                    'success' => $result['success'],
                    'dynamics_po' => $result['data']['msdyn_purchaseorderid'] ?? null
                ];

            } catch (Exception $e) {
                $results[] = [
                    'order_id' => $order['order_id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'processed' => count($results),
            'results' => $results
        ];
    }

    /**
     * Sync sales orders with Dynamics
     */
    protected function syncSalesOrders(array $orders): array
    {
        $results = [];
        
        foreach ($orders as $order) {
            try {
                $result = $this->createSalesOrder($order);
                
                $results[] = [
                    'order_id' => $order['order_id'],
                    'success' => $result['success'],
                    'dynamics_so' => $result['data']['salesorderid'] ?? null
                ];

            } catch (Exception $e) {
                $results[] = [
                    'order_id' => $order['order_id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'processed' => count($results),
            'results' => $results
        ];
    }

    /**
     * Sync invoices with Dynamics
     */
    protected function syncInvoices(array $invoices): array
    {
        $results = [];
        
        foreach ($invoices as $invoice) {
            try {
                $dynamicsInvoice = $this->transformInvoiceForDynamics($invoice);
                $result = $this->createInvoiceInDynamics($dynamicsInvoice);
                
                $results[] = [
                    'invoice_id' => $invoice['invoice_id'],
                    'success' => $result['success'],
                    'dynamics_invoice' => $result['data']['invoiceid'] ?? null
                ];

            } catch (Exception $e) {
                $results[] = [
                    'invoice_id' => $invoice['invoice_id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'processed' => count($results),
            'results' => $results
        ];
    }

    /**
     * Transform invoice data for Dynamics
     */
    protected function transformInvoiceForDynamics(array $invoice): array
    {
        return [
            'invoicenumber' => $invoice['invoice_id'],
            'customerid_account@odata.bind' => '/accounts(' . $this->getCustomerId($invoice['customer_id']) . ')',
            'datedelivered' => $invoice['invoice_date'] ?? date('Y-m-d'),
            'duedate' => $invoice['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'totalamount' => $invoice['total'] ?? 0,
            'transactioncurrencyid@odata.bind' => '/transactioncurrencies(' . $this->getCurrencyId($invoice['currency'] ?? 'USD') . ')',
            'statecode' => 0, // Active
            'statuscode' => 1 // New
        ];
    }

    /**
     * Create invoice in Dynamics
     */
    protected function createInvoiceInDynamics(array $invoiceData): array
    {
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/api/data/v9.0/invoices',
            $invoiceData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Sync payments with Dynamics
     */
    protected function syncPayments(array $payments): array
    {
        $results = [];
        
        foreach ($payments as $payment) {
            try {
                $dynamicsPayment = $this->transformPaymentForDynamics($payment);
                $result = $this->createPaymentInDynamics($dynamicsPayment);
                
                $results[] = [
                    'payment_id' => $payment['payment_id'],
                    'success' => $result['success'],
                    'dynamics_payment' => $result['data']['msdyn_paymentid'] ?? null
                ];

            } catch (Exception $e) {
                $results[] = [
                    'payment_id' => $payment['payment_id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'processed' => count($results),
            'results' => $results
        ];
    }

    /**
     * Transform payment data for Dynamics
     */
    protected function transformPaymentForDynamics(array $payment): array
    {
        return [
            'msdyn_name' => $payment['payment_id'],
            'msdyn_paymentdate' => $payment['payment_date'] ?? date('Y-m-d'),
            'msdyn_amount' => $payment['amount'] ?? 0,
            'msdyn_paymentmethod' => $payment['payment_method'] ?? 'Cash',
            'msdyn_invoice@odata.bind' => '/invoices(' . $this->getInvoiceId($payment['invoice_id']) . ')',
            'transactioncurrencyid@odata.bind' => '/transactioncurrencies(' . $this->getCurrencyId($payment['currency'] ?? 'USD') . ')',
            'statecode' => 0,
            'statuscode' => 1
        ];
    }

    /**
     * Create payment in Dynamics
     */
    protected function createPaymentInDynamics(array $paymentData): array
    {
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/api/data/v9.0/msdyn_payments',
            $paymentData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Get invoice ID helper
     */
    protected function getInvoiceId(string $invoiceNumber): string
    {
        // This would typically query Dynamics to get the Invoice ID
        return '00000000-0000-0000-0000-000000000000';
    }
}