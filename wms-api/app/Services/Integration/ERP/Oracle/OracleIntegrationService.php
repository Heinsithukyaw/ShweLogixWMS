<?php

namespace App\Services\Integration\ERP\Oracle;

use App\Services\Integration\ERP\BaseERPService;
use App\Services\EventLogService;
use App\Services\IdempotencyService;
use Exception;

class OracleIntegrationService extends BaseERPService
{
    protected $provider = 'oracle';
    protected $accessToken;
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $instanceUrl;

    public function __construct(
        EventLogService $eventService,
        IdempotencyService $idempotencyService
    ) {
        parent::__construct($eventService, $idempotencyService);
        $this->initializeOracleConnection();
    }

    /**
     * Initialize Oracle connection parameters
     */
    protected function initializeOracleConnection()
    {
        $this->baseUrl = $this->config['endpoint'] ?? '';
        $this->clientId = $this->config['client_id'] ?? '';
        $this->clientSecret = $this->config['client_secret'] ?? '';
        $this->instanceUrl = $this->config['instance_url'] ?? '';
    }

    /**
     * Authenticate with Oracle ERP Cloud
     */
    public function authenticate(): bool
    {
        try {
            if (!$this->validateConfiguration(['endpoint', 'client_id', 'client_secret'])) {
                return false;
            }

            // Oracle OAuth2 authentication
            $response = $this->executeRequest('POST', $this->baseUrl . '/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'https://erp.oracle.com/scm/inventory'
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
            $this->logger->error('Oracle authentication failed', [
                'error' => $e->getMessage(),
                'endpoint' => $this->baseUrl
            ]);
            return false;
        }
    }

    /**
     * Test Oracle connection
     */
    public function testConnection(): bool
    {
        try {
            if (!$this->accessToken) {
                $this->authenticate();
            }

            $response = $this->executeRequest('GET', $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/items', [], [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json'
            ]);

            return $response['success'];

        } catch (Exception $e) {
            $this->logger->error('Oracle connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get Oracle integration status
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
     * Sync data with Oracle
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
     * Sync master data with Oracle
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
     * Sync transaction data with Oracle
     */
    public function syncTransactionData(string $dataType, array $data): array
    {
        switch ($dataType) {
            case 'purchase_orders':
                return $this->syncPurchaseOrders($data);
            case 'sales_orders':
                return $this->syncSalesOrders($data);
            case 'receipts':
                return $this->syncReceipts($data);
            case 'shipments':
                return $this->syncShipments($data);
            default:
                throw new Exception("Unsupported transaction data type: {$dataType}");
        }
    }

    /**
     * Sync products with Oracle
     */
    protected function syncProducts(array $products): array
    {
        $results = [];
        
        foreach ($products as $product) {
            try {
                $oracleProduct = $this->transformProductForOracle($product);
                
                // Check if item exists in Oracle
                $existingItem = $this->getItemFromOracle($oracleProduct['ItemNumber']);
                
                if ($existingItem) {
                    $result = $this->updateItemInOracle($oracleProduct);
                } else {
                    $result = $this->createItemInOracle($oracleProduct);
                }
                
                $results[] = [
                    'sku' => $product['sku'],
                    'success' => $result['success'],
                    'oracle_item' => $result['data']['ItemNumber'] ?? null,
                    'action' => $existingItem ? 'updated' : 'created'
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
     * Transform product data for Oracle
     */
    protected function transformProductForOracle(array $product): array
    {
        return [
            'ItemNumber' => $product['sku'],
            'ItemDescription' => $product['name'],
            'LongDescription' => $product['description'] ?? '',
            'PrimaryUOMCode' => $product['uom'] ?? 'EA',
            'ItemClass' => $product['category'] ?? 'FINISHED_GOOD',
            'ItemStatus' => 'Active',
            'InventoryItemFlag' => true,
            'StockEnabledFlag' => true,
            'TransactableFlag' => true,
            'PurchasableFlag' => true,
            'SellableFlag' => true,
            'ShippableFlag' => true,
            'ReceivableFlag' => true,
            'InternalOrderFlag' => true,
            'ServiceItemFlag' => false,
            'KitFlag' => false,
            'ConfigurableFlag' => false,
            'WeightUOMCode' => $product['weight_uom'] ?? 'KG',
            'UnitWeight' => $product['weight'] ?? 0,
            'VolumeUOMCode' => $product['volume_uom'] ?? 'L',
            'UnitVolume' => $product['volume'] ?? 0
        ];
    }

    /**
     * Get item from Oracle
     */
    protected function getItemFromOracle(string $itemNumber): ?array
    {
        $response = $this->executeRequest(
            'GET',
            $this->instanceUrl . "/fscmRestApi/resources/11.13.18.05/items/{$itemNumber}",
            [],
            $this->getAuthHeaders()
        );

        return $response['success'] ? $response['data'] : null;
    }

    /**
     * Create item in Oracle
     */
    protected function createItemInOracle(array $itemData): array
    {
        return $this->executeRequest(
            'POST',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/items',
            $itemData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Update item in Oracle
     */
    protected function updateItemInOracle(array $itemData): array
    {
        $itemNumber = $itemData['ItemNumber'];
        
        return $this->executeRequest(
            'PATCH',
            $this->instanceUrl . "/fscmRestApi/resources/11.13.18.05/items/{$itemNumber}",
            $itemData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Sync customers with Oracle
     */
    protected function syncCustomers(array $customers): array
    {
        $results = [];
        
        foreach ($customers as $customer) {
            try {
                $oracleCustomer = $this->transformCustomerForOracle($customer);
                
                $result = $this->createCustomerInOracle($oracleCustomer);
                
                $results[] = [
                    'customer_id' => $customer['customer_id'],
                    'success' => $result['success'],
                    'oracle_party' => $result['data']['PartyId'] ?? null
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
     * Transform customer data for Oracle
     */
    protected function transformCustomerForOracle(array $customer): array
    {
        return [
            'PartyNumber' => $customer['customer_id'],
            'PartyName' => $customer['name'],
            'PartyType' => 'ORGANIZATION',
            'PartyUsageCode' => 'CUSTOMER',
            'EmailAddress' => $customer['email'] ?? '',
            'PhoneNumber' => $customer['phone'] ?? '',
            'Address' => [
                'AddressLine1' => $customer['address'] ?? '',
                'City' => $customer['city'] ?? '',
                'State' => $customer['state'] ?? '',
                'PostalCode' => $customer['postal_code'] ?? '',
                'Country' => $customer['country'] ?? ''
            ]
        ];
    }

    /**
     * Create customer in Oracle
     */
    protected function createCustomerInOracle(array $customerData): array
    {
        return $this->executeRequest(
            'POST',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/tradingCommunityParties',
            $customerData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Get inventory levels from Oracle
     */
    public function getInventoryLevels(array $productIds = []): array
    {
        $queryParams = [];
        if (!empty($productIds)) {
            $items = implode(',', $productIds);
            $queryParams['q'] = "ItemNumber in ('{$items}')";
        }

        $response = $this->executeRequest(
            'GET',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/itemQuantities',
            $queryParams,
            $this->getAuthHeaders()
        );

        if ($response['success']) {
            return [
                'success' => true,
                'inventory' => $this->transformOracleInventoryData($response['data']['items'] ?? [])
            ];
        }

        return $response;
    }

    /**
     * Transform Oracle inventory data
     */
    protected function transformOracleInventoryData(array $oracleInventory): array
    {
        return array_map(function($item) {
            return [
                'sku' => $item['ItemNumber'],
                'warehouse_id' => $item['OrganizationCode'],
                'location' => $item['SubinventoryCode'],
                'available_qty' => $item['OnhandQuantity'],
                'reserved_qty' => $item['ReservedQuantity'] ?? 0,
                'uom' => $item['PrimaryUOMCode'],
                'last_updated' => now()->toISOString()
            ];
        }, $oracleInventory);
    }

    /**
     * Update inventory levels in Oracle
     */
    public function updateInventoryLevels(array $inventoryData): array
    {
        $results = [];
        
        foreach ($inventoryData as $inventory) {
            try {
                $result = $this->createInventoryTransaction($inventory);
                
                $results[] = [
                    'sku' => $inventory['sku'],
                    'success' => $result['success'],
                    'transaction_id' => $result['data']['TransactionId'] ?? null
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
     * Create inventory transaction in Oracle
     */
    protected function createInventoryTransaction(array $inventoryData): array
    {
        $transactionData = [
            'ItemNumber' => $inventoryData['sku'],
            'OrganizationCode' => $inventoryData['warehouse_id'] ?? 'M1',
            'SubinventoryCode' => $inventoryData['location'] ?? 'Stores',
            'TransactionTypeCode' => $inventoryData['transaction_type'] ?? 'MISC_ISSUE',
            'TransactionQuantity' => $inventoryData['quantity'],
            'TransactionUOM' => $inventoryData['uom'] ?? 'EA',
            'TransactionDate' => $inventoryData['transaction_date'] ?? date('Y-m-d'),
            'ReasonCode' => $inventoryData['reason'] ?? 'CYCLE_COUNT'
        ];

        return $this->executeRequest(
            'POST',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/materialTransactions',
            $transactionData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create purchase order in Oracle
     */
    public function createPurchaseOrder(array $orderData): array
    {
        $oraclePO = $this->transformPurchaseOrderForOracle($orderData);
        
        return $this->executeRequest(
            'POST',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/purchaseOrders',
            $oraclePO,
            $this->getAuthHeaders()
        );
    }

    /**
     * Transform purchase order data for Oracle
     */
    protected function transformPurchaseOrderForOracle(array $orderData): array
    {
        return [
            'DocumentNumber' => $orderData['order_id'],
            'SupplierId' => $orderData['supplier_id'],
            'BuyerId' => $orderData['buyer_id'] ?? null,
            'CurrencyCode' => $orderData['currency'] ?? 'USD',
            'DocumentStatus' => 'INCOMPLETE',
            'CreationDate' => $orderData['order_date'] ?? date('Y-m-d'),
            'lines' => array_map(function($item) {
                return [
                    'LineNumber' => $item['line_number'],
                    'ItemNumber' => $item['sku'],
                    'ItemDescription' => $item['description'] ?? '',
                    'UOM' => $item['uom'] ?? 'EA',
                    'Quantity' => $item['quantity'],
                    'UnitPrice' => $item['unit_price'],
                    'NeedByDate' => $item['need_by_date'] ?? date('Y-m-d', strtotime('+7 days')),
                    'DestinationTypeCode' => 'INVENTORY'
                ];
            }, $orderData['items'] ?? [])
        ];
    }

    /**
     * Update purchase order status
     */
    public function updatePurchaseOrderStatus(string $orderId, string $status): array
    {
        $statusData = [
            'DocumentStatus' => $this->mapStatusToOracle($status)
        ];

        return $this->executeRequest(
            'PATCH',
            $this->instanceUrl . "/fscmRestApi/resources/11.13.18.05/purchaseOrders/{$orderId}",
            $statusData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create goods receipt in Oracle
     */
    public function createGoodsReceipt(array $receiptData): array
    {
        $oracleReceipt = [
            'ReceiptNumber' => $receiptData['receipt_id'],
            'ReceiptDate' => $receiptData['receipt_date'] ?? date('Y-m-d'),
            'ShipmentNumber' => $receiptData['shipment_number'] ?? null,
            'lines' => array_map(function($item) {
                return [
                    'ItemNumber' => $item['sku'],
                    'QuantityReceived' => $item['quantity'],
                    'UOM' => $item['uom'] ?? 'EA',
                    'PurchaseOrderNumber' => $item['purchase_order'],
                    'PurchaseOrderLineNumber' => $item['po_line_number'],
                    'DestinationTypeCode' => 'INVENTORY',
                    'SubinventoryCode' => $item['location'] ?? 'Stores'
                ];
            }, $receiptData['items'] ?? [])
        ];

        return $this->executeRequest(
            'POST',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/receipts',
            $oracleReceipt,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create sales order in Oracle
     */
    public function createSalesOrder(array $orderData): array
    {
        $oracleSO = $this->transformSalesOrderForOracle($orderData);
        
        return $this->executeRequest(
            'POST',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/salesOrders',
            $oracleSO,
            $this->getAuthHeaders()
        );
    }

    /**
     * Transform sales order data for Oracle
     */
    protected function transformSalesOrderForOracle(array $orderData): array
    {
        return [
            'OrderNumber' => $orderData['order_id'],
            'CustomerId' => $orderData['customer_id'],
            'OrderDate' => $orderData['order_date'] ?? date('Y-m-d'),
            'RequestedShipDate' => $orderData['delivery_date'] ?? date('Y-m-d', strtotime('+7 days')),
            'CurrencyCode' => $orderData['currency'] ?? 'USD',
            'OrderStatus' => 'ENTERED',
            'lines' => array_map(function($item) {
                return [
                    'LineNumber' => $item['line_number'],
                    'ItemNumber' => $item['sku'],
                    'OrderedQuantity' => $item['quantity'],
                    'OrderedUOM' => $item['uom'] ?? 'EA',
                    'UnitSellingPrice' => $item['unit_price'],
                    'RequestedShipDate' => $item['ship_date'] ?? date('Y-m-d', strtotime('+7 days'))
                ];
            }, $orderData['items'] ?? [])
        ];
    }

    /**
     * Update sales order status
     */
    public function updateSalesOrderStatus(string $orderId, string $status): array
    {
        $statusData = [
            'OrderStatus' => $this->mapStatusToOracle($status)
        ];

        return $this->executeRequest(
            'PATCH',
            $this->instanceUrl . "/fscmRestApi/resources/11.13.18.05/salesOrders/{$orderId}",
            $statusData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create shipment confirmation in Oracle
     */
    public function createShipmentConfirmation(array $shipmentData): array
    {
        $oracleShipment = [
            'ShipmentNumber' => $shipmentData['shipment_id'],
            'ShipDate' => $shipmentData['ship_date'] ?? date('Y-m-d'),
            'CarrierCode' => $shipmentData['carrier'] ?? null,
            'TrackingNumber' => $shipmentData['tracking_number'] ?? null,
            'lines' => array_map(function($item) {
                return [
                    'ItemNumber' => $item['sku'],
                    'ShippedQuantity' => $item['shipped_quantity'],
                    'UOM' => $item['uom'] ?? 'EA',
                    'SalesOrderNumber' => $item['sales_order'],
                    'SalesOrderLineNumber' => $item['so_line_number']
                ];
            }, $shipmentData['items'] ?? [])
        ];

        return $this->executeRequest(
            'POST',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/shipments',
            $oracleShipment,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create inventory adjustment in Oracle
     */
    public function createInventoryAdjustment(array $adjustmentData): array
    {
        return $this->createInventoryTransaction([
            'sku' => $adjustmentData['sku'],
            'warehouse_id' => $adjustmentData['warehouse_id'],
            'location' => $adjustmentData['location'],
            'quantity' => $adjustmentData['adjustment_quantity'],
            'transaction_type' => $adjustmentData['adjustment_quantity'] > 0 ? 'MISC_RECEIPT' : 'MISC_ISSUE',
            'uom' => $adjustmentData['uom'],
            'reason' => $adjustmentData['reason'] ?? 'CYCLE_COUNT'
        ]);
    }

    /**
     * Get financial data from Oracle
     */
    public function getFinancialData(string $dataType, array $filters = []): array
    {
        switch ($dataType) {
            case 'cost_centers':
                return $this->getCostCenters($filters);
            case 'gl_accounts':
                return $this->getGLAccounts($filters);
            case 'budgets':
                return $this->getBudgets($filters);
            default:
                throw new Exception("Unsupported financial data type: {$dataType}");
        }
    }

    /**
     * Get cost centers from Oracle
     */
    protected function getCostCenters(array $filters = []): array
    {
        $response = $this->executeRequest(
            'GET',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/costCenters',
            $filters,
            $this->getAuthHeaders()
        );

        return $response;
    }

    /**
     * Get GL accounts from Oracle
     */
    protected function getGLAccounts(array $filters = []): array
    {
        $response = $this->executeRequest(
            'GET',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/chartOfAccounts',
            $filters,
            $this->getAuthHeaders()
        );

        return $response;
    }

    /**
     * Get budgets from Oracle
     */
    protected function getBudgets(array $filters = []): array
    {
        $response = $this->executeRequest(
            'GET',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/budgets',
            $filters,
            $this->getAuthHeaders()
        );

        return $response;
    }

    /**
     * Map WMS status to Oracle status
     */
    protected function mapStatusToOracle(string $status): string
    {
        $statusMap = [
            'pending' => 'INCOMPLETE',
            'confirmed' => 'APPROVED',
            'in_progress' => 'OPEN',
            'completed' => 'CLOSED',
            'cancelled' => 'CANCELLED'
        ];

        return $statusMap[$status] ?? $status;
    }

    /**
     * Get authentication headers for Oracle requests
     */
    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Sync suppliers with Oracle
     */
    protected function syncSuppliers(array $suppliers): array
    {
        $results = [];
        
        foreach ($suppliers as $supplier) {
            try {
                $oracleSupplier = $this->transformSupplierForOracle($supplier);
                
                $result = $this->createSupplierInOracle($oracleSupplier);
                
                $results[] = [
                    'supplier_id' => $supplier['supplier_id'],
                    'success' => $result['success'],
                    'oracle_supplier' => $result['data']['SupplierId'] ?? null
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
     * Transform supplier data for Oracle
     */
    protected function transformSupplierForOracle(array $supplier): array
    {
        return [
            'SupplierNumber' => $supplier['supplier_id'],
            'SupplierName' => $supplier['name'],
            'SupplierType' => 'SUPPLIER',
            'TaxOrganizationType' => 'CORPORATION',
            'EmailAddress' => $supplier['email'] ?? '',
            'PhoneNumber' => $supplier['phone'] ?? '',
            'Address' => [
                'AddressLine1' => $supplier['address'] ?? '',
                'City' => $supplier['city'] ?? '',
                'State' => $supplier['state'] ?? '',
                'PostalCode' => $supplier['postal_code'] ?? '',
                'Country' => $supplier['country'] ?? ''
            ]
        ];
    }

    /**
     * Create supplier in Oracle
     */
    protected function createSupplierInOracle(array $supplierData): array
    {
        return $this->executeRequest(
            'POST',
            $this->instanceUrl . '/fscmRestApi/resources/11.13.18.05/suppliers',
            $supplierData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Sync purchase orders with Oracle
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
                    'oracle_po' => $result['data']['DocumentNumber'] ?? null
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
     * Sync sales orders with Oracle
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
                    'oracle_so' => $result['data']['OrderNumber'] ?? null
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
     * Sync receipts with Oracle
     */
    protected function syncReceipts(array $receipts): array
    {
        $results = [];
        
        foreach ($receipts as $receipt) {
            try {
                $result = $this->createGoodsReceipt($receipt);
                
                $results[] = [
                    'receipt_id' => $receipt['receipt_id'],
                    'success' => $result['success'],
                    'oracle_receipt' => $result['data']['ReceiptNumber'] ?? null
                ];

            } catch (Exception $e) {
                $results[] = [
                    'receipt_id' => $receipt['receipt_id'],
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
     * Sync shipments with Oracle
     */
    protected function syncShipments(array $shipments): array
    {
        $results = [];
        
        foreach ($shipments as $shipment) {
            try {
                $result = $this->createShipmentConfirmation($shipment);
                
                $results[] = [
                    'shipment_id' => $shipment['shipment_id'],
                    'success' => $result['success'],
                    'oracle_shipment' => $result['data']['ShipmentNumber'] ?? null
                ];

            } catch (Exception $e) {
                $results[] = [
                    'shipment_id' => $shipment['shipment_id'],
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
}