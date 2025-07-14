<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system_role',
        'tenant_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_system_role' => 'boolean'
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot(['granted_by', 'granted_at'])
            ->withTimestamps();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }

    public function scopeCustomRoles($query)
    {
        return $query->where('is_system_role', false);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Methods
    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions()->where('name', $permission)->exists();
        }

        return $this->permissions()->where('id', $permission->id)->exists();
    }

    public function givePermissionTo($permission, $grantedBy = null)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        if (!$this->hasPermission($permission)) {
            $this->permissions()->attach($permission->id, [
                'granted_by' => $grantedBy ?? auth()->id(),
                'granted_at' => now()
            ]);
        }
    }

    public function revokePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
    }

    public function syncPermissions(array $permissions, $grantedBy = null)
    {
        $permissionIds = [];
        
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permissionModel = Permission::where('name', $permission)->firstOrFail();
                $permissionIds[$permissionModel->id] = [
                    'granted_by' => $grantedBy ?? auth()->id(),
                    'granted_at' => now()
                ];
            } else {
                $permissionIds[$permission] = [
                    'granted_by' => $grantedBy ?? auth()->id(),
                    'granted_at' => now()
                ];
            }
        }

        $this->permissions()->sync($permissionIds);
    }

    public function getPermissionNames()
    {
        return $this->permissions->pluck('name')->toArray();
    }
}