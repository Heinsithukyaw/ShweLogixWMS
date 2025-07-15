<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EventIdempotencyKey extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'idempotency_key',
        'event_name',
        'event_source',
        'event_payload',
        'processing_status',
        'processing_result',
        'error_message',
        'processed_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_payload' => 'array',
        'processing_result' => 'array',
        'processed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope a query to only include non-expired keys.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired keys.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include keys with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('processing_status', $status);
    }

    /**
     * Check if the key is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the key is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->processing_status === 'completed';
    }

    /**
     * Check if the key is failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->processing_status === 'failed';
    }

    /**
     * Mark the key as processing.
     *
     * @return bool
     */
    public function markAsProcessing(): bool
    {
        $this->processing_status = 'processing';
        return $this->save();
    }

    /**
     * Mark the key as completed.
     *
     * @param  array|null  $result
     * @return bool
     */
    public function markAsCompleted(array $result = null): bool
    {
        $this->processing_status = 'completed';
        $this->processing_result = $result;
        $this->processed_at = now();
        return $this->save();
    }

    /**
     * Mark the key as failed.
     *
     * @param  string  $errorMessage
     * @return bool
     */
    public function markAsFailed(string $errorMessage): bool
    {
        $this->processing_status = 'failed';
        $this->error_message = $errorMessage;
        $this->processed_at = now();
        return $this->save();
    }

    /**
     * Create a new idempotency key.
     *
     * @param  string  $key
     * @param  string  $eventName
     * @param  string  $eventSource
     * @param  array  $payload
     * @param  int  $ttlHours
     * @return static
     */
    public static function createKey(
        string $key,
        string $eventName,
        string $eventSource,
        array $payload,
        int $ttlHours = 24
    ): self {
        return static::create([
            'idempotency_key' => $key,
            'event_name' => $eventName,
            'event_source' => $eventSource,
            'event_payload' => $payload,
            'processing_status' => 'pending',
            'expires_at' => now()->addHours($ttlHours),
        ]);
    }
}