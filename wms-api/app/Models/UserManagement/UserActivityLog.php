<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserActivityLog extends Model
{
    use HasFactory;

    protected $table = 'user_activity_logs';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'activity_type',
        'activity_description',
        'module',
        'entity_type',
        'entity_id',
        'ip_address',
        'user_agent',
        'session_id',
        'request_data',
        'response_data',
        'duration_ms',
        'status',
        'created_at'
    ];

    protected $casts = [
        'request_data' => 'json',
        'response_data' => 'json',
        'duration_ms' => 'integer',
        'created_at' => 'datetime'
    ];

    public $timestamps = false; // We only need created_at

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByActivityType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Methods
    public static function logActivity(array $data)
    {
        return self::create(array_merge($data, [
            'created_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId()
        ]));
    }

    public static function logUserLogin($user, $successful = true)
    {
        return self::logActivity([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'activity_type' => 'login',
            'activity_description' => $successful ? 'User logged in successfully' : 'Failed login attempt',
            'module' => 'authentication',
            'status' => $successful ? 'success' : 'failed'
        ]);
    }

    public static function logUserLogout($user)
    {
        return self::logActivity([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'activity_type' => 'logout',
            'activity_description' => 'User logged out',
            'module' => 'authentication',
            'status' => 'success'
        ]);
    }

    public static function logDataAccess($user, $entityType, $entityId, $action = 'view')
    {
        return self::logActivity([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'activity_type' => 'data_access',
            'activity_description' => "User {$action}ed {$entityType} #{$entityId}",
            'module' => 'data_access',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => 'success'
        ]);
    }

    public static function logPermissionChange($user, $targetUser, $permission, $action)
    {
        return self::logActivity([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'activity_type' => 'permission_change',
            'activity_description' => "Permission '{$permission}' {$action} for user {$targetUser->name}",
            'module' => 'user_management',
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
            'status' => 'success'
        ]);
    }

    public static function logRoleChange($user, $targetUser, $role, $action)
    {
        return self::logActivity([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'activity_type' => 'role_change',
            'activity_description' => "Role '{$role}' {$action} for user {$targetUser->name}",
            'module' => 'user_management',
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
            'status' => 'success'
        ]);
    }

    public function getFormattedDuration()
    {
        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        } else {
            return round($this->duration_ms / 1000, 2) . 's';
        }
    }
}