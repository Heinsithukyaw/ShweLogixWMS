<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\IdempotencyService;

class TransactionalEventService
{
    /**
     * The idempotency service.
     *
     * @var \App\Services\IdempotencyService
     */
    protected $idempotencyService;

    /**
     * Create a new transactional event service instance.
     *
     * @param  \App\Services\IdempotencyService  $idempotencyService
     * @return void
     */
    public function __construct(IdempotencyService $idempotencyService)
    {
        $this->idempotencyService = $idempotencyService;
    }

    /**
     * Execute a critical operation with database transaction and idempotency protection.
     *
     * @param  string  $operationName
     * @param  array  $payload
     * @param  callable  $operation
     * @param  string|null  $idempotencyKey
     * @param  array  $options
     * @return array
     * @throws \Exception
     */
    public function executeWithTransaction(
        string $operationName,
        array $payload,
        callable $operation,
        ?string $idempotencyKey = null,
        array $options = []
    ): array {
        $startTime = microtime(true);
        $transactionId = uniqid('txn_');
        
        // Default options
        $options = array_merge([
            'max_retries' => 3,
            'retry_delay' => 1000, // milliseconds
            'timeout' => 30, // seconds
            'isolation_level' => null,
            'use_idempotency' => true,
            'idempotency_ttl_hours' => 24,
        ], $options);

        Log::info('Starting transactional operation', [
            'operation_name' => $operationName,
            'transaction_id' => $transactionId,
            'idempotency_key' => $idempotencyKey,
            'options' => $options,
        ]);

        // Generate idempotency key if not provided and idempotency is enabled
        if ($options['use_idempotency'] && !$idempotencyKey) {
            $idempotencyKey = $this->idempotencyService->generateIdempotencyKey(
                $operationName,
                $payload,
                'transactional_service'
            );
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $options['max_retries']) {
            $attempt++;
            
            try {
                if ($options['use_idempotency']) {
                    return $this->executeWithIdempotency(
                        $operationName,
                        $payload,
                        $operation,
                        $idempotencyKey,
                        $options,
                        $transactionId,
                        $startTime
                    );
                } else {
                    return $this->executeWithoutIdempotency(
                        $operationName,
                        $payload,
                        $operation,
                        $options,
                        $transactionId,
                        $startTime
                    );
                }
            } catch (\Exception $e) {
                $lastException = $e;
                
                Log::warning('Transactional operation attempt failed', [
                    'operation_name' => $operationName,
                    'transaction_id' => $transactionId,
                    'attempt' => $attempt,
                    'max_retries' => $options['max_retries'],
                    'error' => $e->getMessage(),
                ]);

                // Don't retry for certain types of exceptions
                if ($this->shouldNotRetry($e)) {
                    break;
                }

                // Wait before retrying
                if ($attempt < $options['max_retries']) {
                    usleep($options['retry_delay'] * 1000);
                }
            }
        }

        // All attempts failed
        Log::error('Transactional operation failed after all retries', [
            'operation_name' => $operationName,
            'transaction_id' => $transactionId,
            'attempts' => $attempt,
            'final_error' => $lastException->getMessage(),
        ]);

        throw $lastException;
    }

    /**
     * Execute operation with idempotency protection.
     *
     * @param  string  $operationName
     * @param  array  $payload
     * @param  callable  $operation
     * @param  string  $idempotencyKey
     * @param  array  $options
     * @param  string  $transactionId
     * @param  float  $startTime
     * @return array
     */
    protected function executeWithIdempotency(
        string $operationName,
        array $payload,
        callable $operation,
        string $idempotencyKey,
        array $options,
        string $transactionId,
        float $startTime
    ): array {
        return $this->idempotencyService->processWithIdempotency(
            $idempotencyKey,
            $operationName,
            'transactional_service',
            $payload,
            function ($payload) use ($operation, $options, $transactionId, $operationName, $startTime) {
                return $this->executeInTransaction($operation, $payload, $options, $transactionId, $operationName, $startTime);
            },
            $options['idempotency_ttl_hours']
        );
    }

    /**
     * Execute operation without idempotency protection.
     *
     * @param  string  $operationName
     * @param  array  $payload
     * @param  callable  $operation
     * @param  array  $options
     * @param  string  $transactionId
     * @param  float  $startTime
     * @return array
     */
    protected function executeWithoutIdempotency(
        string $operationName,
        array $payload,
        callable $operation,
        array $options,
        string $transactionId,
        float $startTime
    ): array {
        $result = $this->executeInTransaction($operation, $payload, $options, $transactionId, $operationName, $startTime);
        
        return [
            'success' => true,
            'result' => $result,
            'was_duplicate' => false,
        ];
    }

    /**
     * Execute operation within a database transaction.
     *
     * @param  callable  $operation
     * @param  array  $payload
     * @param  array  $options
     * @param  string  $transactionId
     * @param  string  $operationName
     * @param  float  $startTime
     * @return mixed
     */
    protected function executeInTransaction(
        callable $operation,
        array $payload,
        array $options,
        string $transactionId,
        string $operationName,
        float $startTime
    ) {
        return DB::transaction(function () use ($operation, $payload, $transactionId, $operationName, $startTime) {
            Log::debug('Executing operation within transaction', [
                'operation_name' => $operationName,
                'transaction_id' => $transactionId,
            ]);

            $result = $operation($payload);

            $executionTime = microtime(true) - $startTime;
            
            Log::info('Transactional operation completed successfully', [
                'operation_name' => $operationName,
                'transaction_id' => $transactionId,
                'execution_time_ms' => round($executionTime * 1000, 2),
            ]);

            return $result;
        }, $options['max_retries']);
    }

    /**
     * Determine if an exception should not be retried.
     *
     * @param  \Exception  $exception
     * @return bool
     */
    protected function shouldNotRetry(\Exception $exception): bool
    {
        // Don't retry for validation errors, authorization errors, etc.
        $nonRetryableExceptions = [
            \Illuminate\Validation\ValidationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \InvalidArgumentException::class,
        ];

        foreach ($nonRetryableExceptions as $exceptionClass) {
            if ($exception instanceof $exceptionClass) {
                return true;
            }
        }

        // Don't retry for certain error messages
        $nonRetryableMessages = [
            'duplicate',
            'already exists',
            'validation failed',
            'unauthorized',
            'forbidden',
        ];

        $message = strtolower($exception->getMessage());
        foreach ($nonRetryableMessages as $pattern) {
            if (strpos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute multiple operations in a single transaction.
     *
     * @param  array  $operations
     * @param  array  $options
     * @return array
     * @throws \Exception
     */
    public function executeBatch(array $operations, array $options = []): array
    {
        $startTime = microtime(true);
        $transactionId = uniqid('batch_txn_');
        
        Log::info('Starting batch transactional operations', [
            'transaction_id' => $transactionId,
            'operation_count' => count($operations),
        ]);

        return DB::transaction(function () use ($operations, $transactionId, $startTime) {
            $results = [];
            
            foreach ($operations as $index => $operation) {
                $operationName = $operation['name'] ?? "operation_{$index}";
                $payload = $operation['payload'] ?? [];
                $callback = $operation['callback'];
                
                Log::debug('Executing batch operation', [
                    'transaction_id' => $transactionId,
                    'operation_name' => $operationName,
                    'operation_index' => $index,
                ]);

                try {
                    $result = $callback($payload);
                    $results[$operationName] = [
                        'success' => true,
                        'result' => $result,
                    ];
                } catch (\Exception $e) {
                    Log::error('Batch operation failed', [
                        'transaction_id' => $transactionId,
                        'operation_name' => $operationName,
                        'operation_index' => $index,
                        'error' => $e->getMessage(),
                    ]);

                    $results[$operationName] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];

                    // Re-throw to rollback the entire transaction
                    throw $e;
                }
            }

            $executionTime = microtime(true) - $startTime;
            
            Log::info('Batch transactional operations completed successfully', [
                'transaction_id' => $transactionId,
                'operation_count' => count($operations),
                'execution_time_ms' => round($executionTime * 1000, 2),
            ]);

            return $results;
        });
    }
}