<?php

namespace App\Services\Integration\ERP\SAP;

use App\Services\Integration\ERP\BaseERPService;
use App\Services\EventLogService;
use App\Services\IdempotencyService;
use Exception;

class SAPIntegrationService extends BaseERPService
{
    protected $provider = 'sap';
    protected $accessToken;
    protected $baseUrl;
    protected $client;
    protected $username;
    protected $password;

    public function __construct(
        EventLogService $eventService,
        IdempotencyService $idempotencyService
    ) {
        parent::__construct($eventService, $idempotencyService);
        $this->initializeSAPConnection();
    }

    /**
     * Initialize SAP connection parameters
     */
    protected function initializeSAPConnection()
    {
        $this->baseUrl = $this->config['endpoint'] ?? '';
        $this->username = $this->config['username'] ?? '';
        $this->password = $this->config['password'] ?? '';
        $this->client = $this->config['client'] ?? '100';
    }

    /**
     * Authenticate with SAP system
     */
    public function authenticate(): bool
    {
        try {
            if (!$this->validateConfiguration(['endpoint', 'username', 'password'])) {
                return false;
            }

            // SAP OData authentication
            $response = $this->executeRequest('GET', $this->baseUrl . '/sap/opu/odata/sap/API_BUSINESS_PARTNER/A_BusinessPartner', [], [
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]);

            if ($response['success']) {
                $this->accessToken = base64_encode($this->username . ':' . $this->password);
                $this->cacheData('auth_token', $this->accessToken, 3600);
                
                $this->emitEvent('authentication_success', [
                    'provider' => $this->provider,
                    'timestamp' => now()
                ]);

                return true;
            }

            return false;

        } catch (Exception $e) {
            $this->logger->error('SAP authentication failed', [
                'error' => $e->getMessage(),
                'endpoint' => $this->baseUrl
            ]);
            return false;
        }
    }

    /**
     * Test SAP connection
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->executeRequest('GET', $this->baseUrl . '/sap/opu/odata/sap/$metadata', [], [
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password)
            ]);

            return $response['success'];

        } catch (Exception $e) {
            $this->logger->error('SAP connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get SAP integration status
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
     * Sync data with SAP
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
     * Sync master data with SAP
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
     * Sync transaction data with SAP
     */
    public function syncTransactionData(string $dataType, array $data): array
    {
        switch ($dataType) {
            case 'purchase_orders':
                return $this->syncPurchaseOrders($data);
            case 'sales_orders':
                return $this->syncSalesOrders($data);
            case 'goods_receipts':
                return $this->syncGoodsReceipts($data);
            case 'inventory_adjustments':
                return $this->syncInventoryAdjustments($data);
            default:
                throw new Exception("Unsupported transaction data type: {$dataType}");
        }
    }

    /**
     * Sync products with SAP
     */
    protected function syncProducts(array $products): array
    {
        $results = [];
        
        foreach ($products as $product) {
            try {
                $sapProduct = $this->transformERPData($product, 'product');
                
                // Check if product exists in SAP
                $existingProduct = $this->getProductFromSAP($sapProduct['Material']);
                
                if ($existingProduct) {
                    $result = $this->updateProductInSAP($sapProduct);
                } else {
                    $result = $this->createProductInSAP($sapProduct);
                }
                
                $results[] = [
                    'sku' => $product['sku'],
                    'success' => $result['success'],
                    'sap_material' => $result['data']['Material'] ?? null,
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
     * Get product from SAP
     */
    protected function getProductFromSAP(string $material): ?array
    {
        $response = $this->executeRequest(
            'GET',
            $this->baseUrl . "/sap/opu/odata/sap/API_PRODUCT_SRV/A_Product('{$material}')",
            [],
            $this->getAuthHeaders()
        );

        return $response['success'] ? $response['data'] : null;
    }

    /**
     * Create product in SAP
     */
    protected function createProductInSAP(array $productData): array
    {
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/sap/opu/odata/sap/API_PRODUCT_SRV/A_Product',
            $productData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Update product in SAP
     */
    protected function updateProductInSAP(array $productData): array
    {
        $material = $productData['Material'];
        
        return $this->executeRequest(
            'PATCH',
            $this->baseUrl . "/sap/opu/odata/sap/API_PRODUCT_SRV/A_Product('{$material}')",
            $productData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Sync customers with SAP
     */
    protected function syncCustomers(array $customers): array
    {
        $results = [];
        
        foreach ($customers as $customer) {
            try {
                $sapCustomer = $this->transformERPData($customer, 'customer');
                
                $result = $this->createBusinessPartnerInSAP($sapCustomer, 'Customer');
                
                $results[] = [
                    'customer_id' => $customer['customer_id'],
                    'success' => $result['success'],
                    'sap_bp' => $result['data']['BusinessPartner'] ?? null
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
     * Create business partner in SAP
     */
    protected function createBusinessPartnerInSAP(array $bpData, string $category): array
    {
        $bpData['BusinessPartnerCategory'] = $category;
        
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/sap/opu/odata/sap/API_BUSINESS_PARTNER/A_BusinessPartner',
            $bpData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Get inventory levels from SAP
     */
    public function getInventoryLevels(array $productIds = []): array
    {
        $filter = '';
        if (!empty($productIds)) {
            $materials = implode("','", $productIds);
            $filter = "?$filter=Material in ('{$materials}')";
        }

        $response = $this->executeRequest(
            'GET',
            $this->baseUrl . '/sap/opu/odata/sap/API_MATERIAL_STOCK_SRV/A_MaterialStock' . $filter,
            [],
            $this->getAuthHeaders()
        );

        if ($response['success']) {
            return [
                'success' => true,
                'inventory' => $this->transformSAPInventoryData($response['data']['d']['results'] ?? [])
            ];
        }

        return $response;
    }

    /**
     * Update inventory levels in SAP
     */
    public function updateInventoryLevels(array $inventoryData): array
    {
        $results = [];
        
        foreach ($inventoryData as $inventory) {
            try {
                $result = $this->postInventoryMovement($inventory);
                
                $results[] = [
                    'sku' => $inventory['sku'],
                    'success' => $result['success'],
                    'document_number' => $result['data']['MaterialDocument'] ?? null
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
     * Post inventory movement to SAP
     */
    protected function postInventoryMovement(array $inventoryData): array
    {
        $movementData = [
            'MaterialDocument' => '',
            'DocumentDate' => date('Y-m-d'),
            'PostingDate' => date('Y-m-d'),
            'MaterialDocumentItems' => [
                [
                    'Material' => $inventoryData['sku'],
                    'Plant' => $inventoryData['warehouse_id'] ?? '1000',
                    'StorageLocation' => $inventoryData['location'] ?? '0001',
                    'MovementType' => $inventoryData['movement_type'] ?? '561',
                    'QuantityInEntryUnit' => $inventoryData['quantity'],
                    'EntryUnit' => $inventoryData['uom'] ?? 'EA'
                ]
            ]
        ];

        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/sap/opu/odata/sap/API_MATERIAL_DOCUMENT_SRV/A_MaterialDocumentHeader',
            $movementData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create purchase order in SAP
     */
    public function createPurchaseOrder(array $orderData): array
    {
        $sapPO = $this->transformPurchaseOrderForSAP($orderData);
        
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/sap/opu/odata/sap/API_PURCHASEORDER_PROCESS_SRV/A_PurchaseOrder',
            $sapPO,
            $this->getAuthHeaders()
        );
    }

    /**
     * Transform purchase order data for SAP
     */
    protected function transformPurchaseOrderForSAP(array $orderData): array
    {
        return [
            'PurchaseOrder' => '',
            'CompanyCode' => $orderData['company_code'] ?? '1000',
            'PurchaseOrderType' => $orderData['order_type'] ?? 'NB',
            'Supplier' => $orderData['supplier_id'],
            'DocumentDate' => $orderData['order_date'] ?? date('Y-m-d'),
            'PurchaseOrderItems' => array_map(function($item) {
                return [
                    'PurchaseOrderItem' => $item['line_number'],
                    'Material' => $item['sku'],
                    'Plant' => $item['plant'] ?? '1000',
                    'OrderQuantity' => $item['quantity'],
                    'PurchaseOrderQuantityUnit' => $item['uom'] ?? 'EA',
                    'NetPriceAmount' => $item['unit_price'],
                    'NetPriceQuantity' => 1
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
            'PurchaseOrderStatus' => $this->mapStatusToSAP($status)
        ];

        return $this->executeRequest(
            'PATCH',
            $this->baseUrl . "/sap/opu/odata/sap/API_PURCHASEORDER_PROCESS_SRV/A_PurchaseOrder('{$orderId}')",
            $statusData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create goods receipt in SAP
     */
    public function createGoodsReceipt(array $receiptData): array
    {
        $grData = [
            'MaterialDocument' => '',
            'DocumentDate' => $receiptData['receipt_date'] ?? date('Y-m-d'),
            'PostingDate' => $receiptData['posting_date'] ?? date('Y-m-d'),
            'MaterialDocumentItems' => array_map(function($item) {
                return [
                    'Material' => $item['sku'],
                    'Plant' => $item['plant'] ?? '1000',
                    'StorageLocation' => $item['location'] ?? '0001',
                    'MovementType' => '101', // Goods receipt for purchase order
                    'PurchaseOrder' => $item['purchase_order'],
                    'PurchaseOrderItem' => $item['po_line_number'],
                    'QuantityInEntryUnit' => $item['quantity'],
                    'EntryUnit' => $item['uom'] ?? 'EA'
                ];
            }, $receiptData['items'] ?? [])
        ];

        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/sap/opu/odata/sap/API_MATERIAL_DOCUMENT_SRV/A_MaterialDocumentHeader',
            $grData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create sales order in SAP
     */
    public function createSalesOrder(array $orderData): array
    {
        $sapSO = $this->transformSalesOrderForSAP($orderData);
        
        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/sap/opu/odata/sap/API_SALES_ORDER_SRV/A_SalesOrder',
            $sapSO,
            $this->getAuthHeaders()
        );
    }

    /**
     * Transform sales order data for SAP
     */
    protected function transformSalesOrderForSAP(array $orderData): array
    {
        return [
            'SalesOrder' => '',
            'SalesOrderType' => $orderData['order_type'] ?? 'OR',
            'SoldToParty' => $orderData['customer_id'],
            'SalesOrganization' => $orderData['sales_org'] ?? '1000',
            'DistributionChannel' => $orderData['distribution_channel'] ?? '10',
            'Division' => $orderData['division'] ?? '00',
            'RequestedDeliveryDate' => $orderData['delivery_date'] ?? date('Y-m-d'),
            'SalesOrderItems' => array_map(function($item) {
                return [
                    'SalesOrderItem' => $item['line_number'],
                    'Material' => $item['sku'],
                    'RequestedQuantity' => $item['quantity'],
                    'RequestedQuantityUnit' => $item['uom'] ?? 'EA',
                    'ItemGrossWeight' => $item['weight'] ?? 0,
                    'ItemNetWeight' => $item['net_weight'] ?? 0,
                    'ItemWeightUnit' => $item['weight_uom'] ?? 'KG'
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
            'OverallSDProcessStatus' => $this->mapStatusToSAP($status)
        ];

        return $this->executeRequest(
            'PATCH',
            $this->baseUrl . "/sap/opu/odata/sap/API_SALES_ORDER_SRV/A_SalesOrder('{$orderId}')",
            $statusData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create shipment confirmation in SAP
     */
    public function createShipmentConfirmation(array $shipmentData): array
    {
        $deliveryData = [
            'DeliveryDocument' => '',
            'DeliveryDocumentType' => 'LF',
            'ActualGoodsMovementDate' => $shipmentData['ship_date'] ?? date('Y-m-d'),
            'DeliveryDocumentItems' => array_map(function($item) {
                return [
                    'DeliveryDocumentItem' => $item['line_number'],
                    'Material' => $item['sku'],
                    'ActualDeliveryQuantity' => $item['shipped_quantity'],
                    'DeliveryQuantityUnit' => $item['uom'] ?? 'EA',
                    'ReferenceSDDocument' => $item['sales_order'],
                    'ReferenceSDDocumentItem' => $item['so_line_number']
                ];
            }, $shipmentData['items'] ?? [])
        ];

        return $this->executeRequest(
            'POST',
            $this->baseUrl . '/sap/opu/odata/sap/API_OUTBOUND_DELIVERY_SRV/A_OutbDeliveryHeader',
            $deliveryData,
            $this->getAuthHeaders()
        );
    }

    /**
     * Create inventory adjustment in SAP
     */
    public function createInventoryAdjustment(array $adjustmentData): array
    {
        return $this->postInventoryMovement([
            'sku' => $adjustmentData['sku'],
            'warehouse_id' => $adjustmentData['warehouse_id'],
            'location' => $adjustmentData['location'],
            'quantity' => $adjustmentData['adjustment_quantity'],
            'movement_type' => $adjustmentData['adjustment_quantity'] > 0 ? '561' : '562', // Inventory increase/decrease
            'uom' => $adjustmentData['uom']
        ]);
    }

    /**
     * Get financial data from SAP
     */
    public function getFinancialData(string $dataType, array $filters = []): array
    {
        switch ($dataType) {
            case 'cost_centers':
                return $this->getCostCenters($filters);
            case 'gl_accounts':
                return $this->getGLAccounts($filters);
            case 'profit_centers':
                return $this->getProfitCenters($filters);
            default:
                throw new Exception("Unsupported financial data type: {$dataType}");
        }
    }

    /**
     * Get cost centers from SAP
     */
    protected function getCostCenters(array $filters = []): array
    {
        $response = $this->executeRequest(
            'GET',
            $this->baseUrl . '/sap/opu/odata/sap/API_COSTCENTER_SRV/A_CostCenter',
            [],
            $this->getAuthHeaders()
        );

        return $response;
    }

    /**
     * Transform SAP inventory data
     */
    protected function transformSAPInventoryData(array $sapInventory): array
    {
        return array_map(function($item) {
            return [
                'sku' => $item['Material'],
                'warehouse_id' => $item['Plant'],
                'location' => $item['StorageLocation'],
                'available_qty' => $item['MatlWrhsStkQtyInMatlBaseUnit'],
                'reserved_qty' => $item['ReservedStock'] ?? 0,
                'uom' => $item['MaterialBaseUnit'],
                'last_updated' => now()->toISOString()
            ];
        }, $sapInventory);
    }

    /**
     * Map WMS status to SAP status
     */
    protected function mapStatusToSAP(string $status): string
    {
        $statusMap = [
            'pending' => 'A',
            'confirmed' => 'B',
            'in_progress' => 'C',
            'completed' => 'D',
            'cancelled' => 'X'
        ];

        return $statusMap[$status] ?? $status;
    }

    /**
     * Get authentication headers for SAP requests
     */
    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest'
        ];
    }

    /**
     * Sync purchase orders with SAP
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
                    'sap_po' => $result['data']['PurchaseOrder'] ?? null
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
     * Sync sales orders with SAP
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
                    'sap_so' => $result['data']['SalesOrder'] ?? null
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
     * Sync suppliers with SAP
     */
    protected function syncSuppliers(array $suppliers): array
    {
        $results = [];
        
        foreach ($suppliers as $supplier) {
            try {
                $sapSupplier = $this->transformERPData($supplier, 'supplier');
                
                $result = $this->createBusinessPartnerInSAP($sapSupplier, 'Supplier');
                
                $results[] = [
                    'supplier_id' => $supplier['supplier_id'],
                    'success' => $result['success'],
                    'sap_bp' => $result['data']['BusinessPartner'] ?? null
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
     * Sync goods receipts with SAP
     */
    protected function syncGoodsReceipts(array $receipts): array
    {
        $results = [];
        
        foreach ($receipts as $receipt) {
            try {
                $result = $this->createGoodsReceipt($receipt);
                
                $results[] = [
                    'receipt_id' => $receipt['receipt_id'],
                    'success' => $result['success'],
                    'material_document' => $result['data']['MaterialDocument'] ?? null
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
     * Sync inventory adjustments with SAP
     */
    protected function syncInventoryAdjustments(array $adjustments): array
    {
        $results = [];
        
        foreach ($adjustments as $adjustment) {
            try {
                $result = $this->createInventoryAdjustment($adjustment);
                
                $results[] = [
                    'adjustment_id' => $adjustment['adjustment_id'],
                    'success' => $result['success'],
                    'material_document' => $result['data']['MaterialDocument'] ?? null
                ];

            } catch (Exception $e) {
                $results[] = [
                    'adjustment_id' => $adjustment['adjustment_id'],
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
     * Get GL accounts from SAP
     */
    protected function getGLAccounts(array $filters = []): array
    {
        $response = $this->executeRequest(
            'GET',
            $this->baseUrl . '/sap/opu/odata/sap/API_GLACCOUNT_SRV/A_GLAccount',
            [],
            $this->getAuthHeaders()
        );

        return $response;
    }

    /**
     * Get profit centers from SAP
     */
    protected function getProfitCenters(array $filters = []): array
    {
        $response = $this->executeRequest(
            'GET',
            $this->baseUrl . '/sap/opu/odata/sap/API_PROFITCENTER_SRV/A_ProfitCenter',
            [],
            $this->getAuthHeaders()
        );

        return $response;
    }
}