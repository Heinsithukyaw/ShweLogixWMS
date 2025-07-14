<?php

namespace App\Services;

use App\Models\EventIdempotencyKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IdempotencyService
{
    /**
     * Process an event with idempotency protection.
     *
     * @param  string  $idempotencyKey
     * @param  string  $eventName
     * @param  string  $eventSource
     * @param  array  $payload
     * @param  callable  $processor
     * @param  int  $ttlHours
     * @return array
     * @throws \Exception
     */
    public function processWithIdempotency(
        string $idempotencyKey,
        string $eventName,
        string $eventSource,
        array $payload,
        callable $processor,
        int $ttlHours = 24
    ): array {
        return DB::transaction(function () use (
            $idempotencyKey,
            $eventName,
            $eventSource,
            $payload,
            $processor,
            $ttlHours
        ) {
            // Check if idempotency key already exists
            $existingKey = EventIdempotencyKey::where('idempotency_key', $idempotencyKey)
                ->notExpired()
                ->first();

            if ($existingKey) {
                return $this->handleExistingKey($existingKey);
            }

            // Create new idempotency key
            $key = EventIdempotencyKey::createKey(
                $idempotencyKey,
                $eventName,
                $eventSource,
                $payload,
                $ttlHours
            );

            // Mark as processing
            $key->markAsProcessing();

            try {
                // Process the event
                $result = $processor($payload);

                // Mark as completed
                $key->markAsCompleted($result);

                Log::info('Event processed successfully with idempotency', [
                    'idempotency_key' => $idempotencyKey,
                    'event_name' => $eventName,
                    'event_source' => $eventSource,
                ]);

                return [
                    'success' => true,
                    'result' => $result,
                    'was_duplicate' => false,
                ];
            } catch (\Exception $e) {
                // Mark as failed
                $key->markAsFailed($e->getMessage());

                Log::error('Event processing failed with idempotency', [
                    'idempotency_key' => $idempotencyKey,
                    'event_name' => $eventName,
                    'event_source' => $eventSource,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Handle existing idempotency key.
     *
     * @param  \App\Models\EventIdempotencyKey  $key
     * @return array
     * @throws \Exception
     */
    protected function handleExistingKey(EventIdempotencyKey $key): array
    {
        switch ($key->processing_status) {
            case 'completed':
                Log::info('Duplicate event detected, returning cached result', [
                    'idempotency_key' => $key->idempotency_key,
                    'event_name' => $key->event_name,
                ]);

                return [
                    'success' => true,
                    'result' => $key->processing_result,
                    'was_duplicate' => true,
                ];

            case 'failed':
                Log::warning('Duplicate event with previous failure detected', [
                    'idempotency_key' => $key->idempotency_key,
                    'event_name' => $key->event_name,
                    'error_message' => $key->error_message,
                ]);

                throw new \Exception("Previous processing failed: {$key->error_message}");

            case 'processing':
                Log::warning('Duplicate event currently being processed', [
                    'idempotency_key' => $key->idempotency_key,
                    'event_name' => $key->event_name,
                ]);

                throw new \Exception('Event is currently being processed');

            default:
                Log::warning('Duplicate event with unknown status', [
                    'idempotency_key' => $key->idempotency_key,
                    'event_name' => $key->event_name,
                    'status' => $key->processing_status,
                ]);

                throw new \Exception("Unknown processing status: {$key->processing_status}");
        }
    }

    /**
     * Generate an idempotency key for an event.
     *
     * @param  string  $eventName
     * @param  array  $payload
     * @param  string|null  $source
     * @return string
     */
    public function generateIdempotencyKey(string $eventName, array $payload, ?string $source = null): string
    {
        // Create a deterministic key based on event name, source, and payload
        $keyData = [
            'event_name' => $eventName,
            'source' => $source,
            'payload' => $this->normalizePayload($payload),
        ];

        return hash('sha256', json_encode($keyData, JSON_SORT_KEYS));
    }

    /**
     * Normalize payload for consistent key generation.
     *
     * @param  array  $payload
     * @return array
     */
    protected function normalizePayload(array $payload): array
    {
        // Remove timestamp fields that might vary between duplicate events
        $excludeFields = ['timestamp', 'created_at', 'updated_at', 'processed_at'];
        
        $normalized = [];
        foreach ($payload as $key => $value) {
            if (!in_array($key, $excludeFields)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Clean up expired idempotency keys.
     *
     * @return int Number of cleaned up keys
     */
    public function cleanupExpiredKeys(): int
    {
        $count = EventIdempotencyKey::expired()->count();
        
        if ($count > 0) {
            EventIdempotencyKey::expired()->delete();
            
            Log::info('Cleaned up expired idempotency keys', [
                'count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Get statistics about idempotency keys.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_keys' => EventIdempotencyKey::count(),
            'active_keys' => EventIdempotencyKey::notExpired()->count(),
            'expired_keys' => EventIdempotencyKey::expired()->count(),
            'completed_keys' => EventIdempotencyKey::withStatus('completed')->count(),
            'failed_keys' => EventIdempotencyKey::withStatus('failed')->count(),
            'processing_keys' => EventIdempotencyKey::withStatus('processing')->count(),
        ];
    }
}