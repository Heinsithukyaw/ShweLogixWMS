<?php

namespace App\Traits;

use App\Services\TransactionalEventService;
use Illuminate\Support\Facades\App;

trait UsesTransactionalEvents
{
    /**
     * The transactional event service instance.
     *
     * @var \App\Services\TransactionalEventService
     */
    protected $transactionalEventService;

    /**
     * Get the transactional event service instance.
     *
     * @return \App\Services\TransactionalEventService
     */
    protected function getTransactionalEventService(): TransactionalEventService
    {
        if (!$this->transactionalEventService) {
            $this->transactionalEventService = App::make(TransactionalEventService::class);
        }

        return $this->transactionalEventService;
    }

    /**
     * Execute a critical operation with transaction and idempotency protection.
     *
     * @param  string  $operationName
     * @param  array  $payload
     * @param  callable  $operation
     * @param  string|null  $idempotencyKey
     * @param  array  $options
     * @return array
     * @throws \Exception
     */
    protected function executeTransactionalOperation(
        string $operationName,
        array $payload,
        callable $operation,
        ?string $idempotencyKey = null,
        array $options = []
    ): array {
        return $this->getTransactionalEventService()->executeWithTransaction(
            $operationName,
            $payload,
            $operation,
            $idempotencyKey,
            $options
        );
    }

    /**
     * Execute multiple operations in a single transaction.
     *
     * @param  array  $operations
     * @param  array  $options
     * @return array
     * @throws \Exception
     */
    protected function executeBatchTransactionalOperations(array $operations, array $options = []): array
    {
        return $this->getTransactionalEventService()->executeBatch($operations, $options);
    }

    /**
     * Execute a critical inventory operation with transaction protection.
     *
     * @param  string  $operationName
     * @param  array  $payload
     * @param  callable  $operation
     * @param  string|null  $idempotencyKey
     * @return array
     * @throws \Exception
     */
    protected function executeInventoryOperation(
        string $operationName,
        array $payload,
        callable $operation,
        ?string $idempotencyKey = null
    ): array {
        $options = [
            'max_retries' => 5,
            'retry_delay' => 2000,
            'timeout' => 60,
            'use_idempotency' => true,
            'idempotency_ttl_hours' => 48,
        ];

        return $this->executeTransactionalOperation(
            "inventory.{$operationName}",
            $payload,
            $operation,
            $idempotencyKey,
            $options
        );
    }

    /**
     * Execute a critical order operation with transaction protection.
     *
     * @param  string  $operationName
     * @param  array  $payload
     * @param  callable  $operation
     * @param  string|null  $idempotencyKey
     * @return array
     * @throws \Exception
     */
    protected function executeOrderOperation(
        string $operationName,
        array $payload,
        callable $operation,
        ?string $idempotencyKey = null
    ): array {
        $options = [
            'max_retries' => 3,
            'retry_delay' => 1500,
            'timeout' => 45,
            'use_idempotency' => true,
            'idempotency_ttl_hours' => 24,
        ];

        return $this->executeTransactionalOperation(
            "order.{$operationName}",
            $payload,
            $operation,
            $idempotencyKey,
            $options
        );
    }

    /**
     * Execute a critical warehouse operation with transaction protection.
     *
     * @param  string  $operationName
     * @param  array  $payload
     * @param  callable  $operation
     * @param  string|null  $idempotencyKey
     * @return array
     * @throws \Exception
     */
    protected function executeWarehouseOperation(
        string $operationName,
        array $payload,
        callable $operation,
        ?string $idempotencyKey = null
    ): array {
        $options = [
            'max_retries' => 3,
            'retry_delay' => 1000,
            'timeout' => 30,
            'use_idempotency' => true,
            'idempotency_ttl_hours' => 12,
        ];

        return $this->executeTransactionalOperation(
            "warehouse.{$operationName}",
            $payload,
            $operation,
            $idempotencyKey,
            $options
        );
    }

    /**
     * Execute a financial operation with strict transaction protection.
     *
     * @param  string  $operationName
     * @param  array  $payload
     * @param  callable  $operation
     * @param  string|null  $idempotencyKey
     * @return array
     * @throws \Exception
     */
    protected function executeFinancialOperation(
        string $operationName,
        array $payload,
        callable $operation,
        ?string $idempotencyKey = null
    ): array {
        $options = [
            'max_retries' => 1, // Financial operations should not be retried automatically
            'retry_delay' => 0,
            'timeout' => 30,
            'use_idempotency' => true,
            'idempotency_ttl_hours' => 72, // Keep financial idempotency keys longer
        ];

        return $this->executeTransactionalOperation(
            "financial.{$operationName}",
            $payload,
            $operation,
            $idempotencyKey,
            $options
        );
    }

    /**
     * Generate a deterministic idempotency key for an operation.
     *
     * @param  string  $operationName
     * @param  array  $payload
     * @param  string|null  $context
     * @return string
     */
    protected function generateIdempotencyKey(string $operationName, array $payload, ?string $context = null): string
    {
        $keyData = [
            'operation' => $operationName,
            'context' => $context,
            'payload' => $this->normalizePayloadForKey($payload),
        ];

        return hash('sha256', json_encode($keyData, JSON_SORT_KEYS));
    }

    /**
     * Normalize payload for idempotency key generation.
     *
     * @param  array  $payload
     * @return array
     */
    protected function normalizePayloadForKey(array $payload): array
    {
        // Remove fields that shouldn't affect idempotency
        $excludeFields = [
            'timestamp',
            'created_at',
            'updated_at',
            'processed_at',
            'attempt_count',
            'retry_count',
        ];

        $normalized = [];
        foreach ($payload as $key => $value) {
            if (!in_array($key, $excludeFields)) {
                if (is_array($value)) {
                    $normalized[$key] = $this->normalizePayloadForKey($value);
                } else {
                    $normalized[$key] = $value;
                }
            }
        }

        return $normalized;
    }
}