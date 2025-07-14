<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Warehouse;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database_name',
        'settings',
        'is_active',
        'subscription_plan',
        'subscription_expires_at',
        'storage_limit',
        'user_limit',
        'created_by'
    ];

    protected $casts = [
        'settings' => 'json',
        'is_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'storage_limit' => 'integer',
        'user_limit' => 'integer'
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenantSettings()
    {
        return $this->hasMany(TenantSetting::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    // Methods
    public function isActive()
    {
        return $this->is_active && 
               (!$this->subscription_expires_at || $this->subscription_expires_at > now());
    }

    public function canAddUser()
    {
        return $this->user_limit === null || $this->users()->count() < $this->user_limit;
    }

    public function getRemainingUsers()
    {
        if ($this->user_limit === null) {
            return null; // Unlimited
        }

        return max(0, $this->user_limit - $this->users()->count());
    }

    public function getStorageUsage()
    {
        // This would calculate actual storage usage
        // For now, return a placeholder
        return 0;
    }

    public function getRemainingStorage()
    {
        if ($this->storage_limit === null) {
            return null; // Unlimited
        }

        return max(0, $this->storage_limit - $this->getStorageUsage());
    }

    public function getSetting($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting($key, $value)
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        $this->save();
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    public function extendSubscription($days)
    {
        $currentExpiry = $this->subscription_expires_at ?? now();
        $this->subscription_expires_at = $currentExpiry->addDays($days);
        $this->save();
    }
}