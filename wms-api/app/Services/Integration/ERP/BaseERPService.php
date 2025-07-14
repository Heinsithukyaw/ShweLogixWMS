<?php

namespace App\Services\Integration\ERP;

use App\Services\Integration\BaseIntegrationService;
use App\Services\EventLogService;
use App\Services\IdempotencyService;

abstract class BaseERPService extends BaseIntegrationService
{
    protected $integrationName = 'erp';

    public function __construct(
        EventLogService $eventService,
        IdempotencyService $idempotencyService
    ) {
        parent::__construct($eventService, $idempotencyService);
    }

    /**
     * Sync master data (products, customers, suppliers)
     */
    abstract public function syncMasterData(string $dataType, array $data): array;

    /**
     * Sync transaction data (orders, receipts, adjustments)
     */
    abstract public function syncTransactionData(string $dataType, array $data): array;

    /**
     * Get inventory levels from ERP
     */
    abstract public function getInventoryLevels(array $productIds = []): array;

    /**
     * Send inventory updates to ERP
     */
    abstract public function updateInventoryLevels(array $inventoryData): array;

    /**
     * Create purchase order in ERP
     */
    abstract public function createPurchaseOrder(array $orderData): array;

    /**
     * Update purchase order status
     */
    abstract public function updatePurchaseOrderStatus(string $orderId, string $status): array;

    /**
     * Create goods receipt in ERP
     */
    abstract public function createGoodsReceipt(array $receiptData): array;

    /**
     * Create sales order in ERP
     */
    abstract public function createSalesOrder(array $orderData): array;

    /**
     * Update sales order status
     */
    abstract public function updateSalesOrderStatus(string $orderId, string $status): array;

    /**
     * Create shipment confirmation in ERP
     */
    abstract public function createShipmentConfirmation(array $shipmentData): array;

    /**
     * Create inventory adjustment in ERP
     */
    abstract public function createInventoryAdjustment(array $adjustmentData): array;

    /**
     * Get financial data from ERP
     */
    abstract public function getFinancialData(string $dataType, array $filters = []): array;

    /**
     * Common ERP data transformation
     */
    protected function transformERPData(array $data, string $dataType): array
    {
        $mappings = $this->getDataMappings();
        
        if (!isset($mappings[$dataType])) {
            return $data;
        }

        return $this->transformData($data, $mappings[$dataType]);
    }

    /**
     * Get data field mappings for ERP
     */
    protected function getDataMappings(): array
    {
        return [
            'product' => [
                'product_code' => 'sku',
                'product_name' => 'name',
                'product_description' => 'description',
                'unit_price' => 'price',
                'unit_of_measure' => 'uom',
                'product_category' => 'category',
                'supplier_code' => 'supplier_id'
            ],
            'customer' => [
                'customer_code' => 'customer_id',
                'customer_name' => 'name',
                'customer_address' => 'address',
                'customer_phone' => 'phone',
                'customer_email' => 'email',
                'payment_terms' => 'payment_terms'
            ],
            'supplier' => [
                'supplier_code' => 'supplier_id',
                'supplier_name' => 'name',
                'supplier_address' => 'address',
                'supplier_phone' => 'phone',
                'supplier_email' => 'email',
                'payment_terms' => 'payment_terms'
            ],
            'purchase_order' => [
                'po_number' => 'order_id',
                'supplier_code' => 'supplier_id',
                'order_date' => 'created_at',
                'delivery_date' => 'expected_delivery',
                'total_amount' => 'total',
                'currency' => 'currency',
                'status' => 'status'
            ],
            'sales_order' => [
                'so_number' => 'order_id',
                'customer_code' => 'customer_id',
                'order_date' => 'created_at',
                'delivery_date' => 'expected_delivery',
                'total_amount' => 'total',
                'currency' => 'currency',
                'status' => 'status'
            ],
            'inventory' => [
                'product_code' => 'sku',
                'warehouse_code' => 'warehouse_id',
                'available_quantity' => 'available_qty',
                'reserved_quantity' => 'reserved_qty',
                'on_order_quantity' => 'on_order_qty',
                'unit_cost' => 'cost',
                'last_updated' => 'updated_at'
            ]
        ];
    }

    /**
     * Validate ERP data structure
     */
    protected function validateERPData(array $data, string $dataType): array
    {
        $validationRules = $this->getValidationRules();
        
        if (!isset($validationRules[$dataType])) {
            return ['valid' => true, 'errors' => []];
        }

        $errors = [];
        $rules = $validationRules[$dataType];

        foreach ($rules as $field => $rule) {
            if ($rule['required'] && !isset($data[$field])) {
                $errors[] = "Required field '{$field}' is missing";
            }

            if (isset($data[$field]) && isset($rule['type'])) {
                if (!$this->validateFieldType($data[$field], $rule['type'])) {
                    $errors[] = "Field '{$field}' has invalid type, expected {$rule['type']}";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get validation rules for ERP data
     */
    protected function getValidationRules(): array
    {
        return [
            'product' => [
                'sku' => ['required' => true, 'type' => 'string'],
                'name' => ['required' => true, 'type' => 'string'],
                'price' => ['required' => false, 'type' => 'numeric'],
                'uom' => ['required' => true, 'type' => 'string']
            ],
            'customer' => [
                'customer_id' => ['required' => true, 'type' => 'string'],
                'name' => ['required' => true, 'type' => 'string'],
                'email' => ['required' => false, 'type' => 'email']
            ],
            'purchase_order' => [
                'order_id' => ['required' => true, 'type' => 'string'],
                'supplier_id' => ['required' => true, 'type' => 'string'],
                'total' => ['required' => true, 'type' => 'numeric'],
                'items' => ['required' => true, 'type' => 'array']
            ],
            'sales_order' => [
                'order_id' => ['required' => true, 'type' => 'string'],
                'customer_id' => ['required' => true, 'type' => 'string'],
                'total' => ['required' => true, 'type' => 'numeric'],
                'items' => ['required' => true, 'type' => 'array']
            ]
        ];
    }

    /**
     * Validate field type
     */
    protected function validateFieldType($value, string $type): bool
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'numeric':
                return is_numeric($value);
            case 'array':
                return is_array($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'date':
                return strtotime($value) !== false;
            default:
                return true;
        }
    }

    /**
     * Process ERP webhook
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $eventType = $payload['event_type'] ?? 'unknown';
            $data = $payload['data'] ?? [];

            $this->logger->info("Processing ERP webhook", [
                'provider' => $this->provider,
                'event_type' => $eventType,
                'data_keys' => array_keys($data)
            ]);

            switch ($eventType) {
                case 'master_data_updated':
                    return $this->processMasterDataUpdate($data);
                case 'order_status_changed':
                    return $this->processOrderStatusChange($data);
                case 'inventory_updated':
                    return $this->processInventoryUpdate($data);
                default:
                    return $this->processGenericWebhook($eventType, $data);
            }

        } catch (\Exception $e) {
            return $this->handleError($e, 'webhook_processing', $payload);
        }
    }

    /**
     * Process master data update webhook
     */
    protected function processMasterDataUpdate(array $data): array
    {
        $dataType = $data['type'] ?? 'unknown';
        $records = $data['records'] ?? [];

        foreach ($records as $record) {
            $this->emitEvent('master_data_updated', [
                'type' => $dataType,
                'record' => $record,
                'provider' => $this->provider
            ]);
        }

        return ['success' => true, 'processed' => count($records)];
    }

    /**
     * Process order status change webhook
     */
    protected function processOrderStatusChange(array $data): array
    {
        $orderId = $data['order_id'] ?? null;
        $status = $data['status'] ?? null;
        $orderType = $data['order_type'] ?? 'unknown';

        if (!$orderId || !$status) {
            throw new \Exception('Missing required fields: order_id or status');
        }

        $this->emitEvent('order_status_changed', [
            'order_id' => $orderId,
            'status' => $status,
            'order_type' => $orderType,
            'provider' => $this->provider
        ]);

        return ['success' => true, 'order_id' => $orderId, 'status' => $status];
    }

    /**
     * Process inventory update webhook
     */
    protected function processInventoryUpdate(array $data): array
    {
        $inventoryUpdates = $data['inventory'] ?? [];

        foreach ($inventoryUpdates as $update) {
            $this->emitEvent('inventory_updated', [
                'sku' => $update['sku'] ?? null,
                'warehouse_id' => $update['warehouse_id'] ?? null,
                'quantity' => $update['quantity'] ?? 0,
                'provider' => $this->provider
            ]);
        }

        return ['success' => true, 'processed' => count($inventoryUpdates)];
    }

    /**
     * Process generic webhook
     */
    protected function processGenericWebhook(string $eventType, array $data): array
    {
        $this->emitEvent('webhook_received', [
            'event_type' => $eventType,
            'data' => $data,
            'provider' => $this->provider
        ]);

        return ['success' => true, 'event_type' => $eventType];
    }
}