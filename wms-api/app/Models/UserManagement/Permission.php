<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'category',
        'is_system_permission'
    ];

    protected $casts = [
        'is_system_permission' => 'boolean'
    ];

    // Relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot(['granted_by', 'granted_at'])
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot(['granted_by', 'granted_at'])
            ->withTimestamps();
    }

    // Scopes
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSystemPermissions($query)
    {
        return $query->where('is_system_permission', true);
    }

    public function scopeCustomPermissions($query)
    {
        return $query->where('is_system_permission', false);
    }

    // Methods
    public static function getModules()
    {
        return self::distinct('module')->pluck('module')->toArray();
    }

    public static function getCategories()
    {
        return self::distinct('category')->pluck('category')->toArray();
    }

    public static function getByModule($module)
    {
        return self::where('module', $module)->get();
    }
}