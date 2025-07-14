<?php

namespace App\Models\ECommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\ProductInventory;

class InventorySync extends Model
{
    use HasFactory;

    protected $table = 'inventory_syncs';

    protected $fillable = [
        'product_id',
        'platform',
        'platform_product_id',
        'wms_quantity',
        'platform_quantity',
        'sync_status',
        'last_sync_at',
        'sync_frequency',
        'sync_rules',
        'error_message',
        'retry_count',
        'next_sync_at'
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'next_sync_at' => 'datetime',
        'sync_rules' => 'json',
        'wms_quantity' => 'decimal:2',
        'platform_quantity' => 'decimal:2'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productInventory()
    {
        return $this->belongsTo(ProductInventory::class, 'product_id', 'product_id');
    }

    // Scopes
    public function scopePendingSync($query)
    {
        return $query->where('sync_status', 'pending')
                    ->where('next_sync_at', '<=', now());
    }

    public function scopeFailedSync($query)
    {
        return $query->where('sync_status', 'failed');
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    // Methods
    public function needsSync()
    {
        return $this->wms_quantity != $this->platform_quantity || 
               $this->sync_status === 'pending' ||
               ($this->next_sync_at && $this->next_sync_at <= now());
    }

    public function updateSyncStatus($status, $errorMessage = null)
    {
        $this->sync_status = $status;
        $this->last_sync_at = now();
        
        if ($errorMessage) {
            $this->error_message = $errorMessage;
            $this->retry_count++;
        } else {
            $this->error_message = null;
            $this->retry_count = 0;
        }

        // Calculate next sync time based on frequency
        $this->calculateNextSyncTime();
        $this->save();
    }

    public function calculateNextSyncTime()
    {
        $frequency = $this->sync_frequency ?? 'hourly';
        
        switch ($frequency) {
            case 'real_time':
                $this->next_sync_at = now()->addMinutes(1);
                break;
            case 'every_15_minutes':
                $this->next_sync_at = now()->addMinutes(15);
                break;
            case 'hourly':
                $this->next_sync_at = now()->addHour();
                break;
            case 'daily':
                $this->next_sync_at = now()->addDay();
                break;
            default:
                $this->next_sync_at = now()->addHour();
        }
    }

    public function shouldRetry()
    {
        return $this->retry_count < 3 && $this->sync_status === 'failed';
    }
}