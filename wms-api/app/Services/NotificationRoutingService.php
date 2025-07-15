<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotificationRoutingService
{
    /**
     * Get users by role.
     *
     * @param array $roles
     * @return array
     */
    public function getUsersByRole(array $roles)
    {
        try {
            // Cache the result for 5 minutes to avoid frequent database queries
            return Cache::remember('users_by_role:' . implode(',', $roles), 300, function () use ($roles) {
                return User::whereHas('roles', function ($query) use ($roles) {
                    $query->whereIn('name', $roles);
                })->pluck('id')->toArray();
            });
        } catch (\Exception $e) {
            Log::error('Failed to get users by role', [
                'roles' => $roles,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Get users by department.
     *
     * @param array $departments
     * @return array
     */
    public function getUsersByDepartment(array $departments)
    {
        try {
            // Cache the result for 5 minutes to avoid frequent database queries
            return Cache::remember('users_by_department:' . implode(',', $departments), 300, function () use ($departments) {
                return User::whereIn('department', $departments)->pluck('id')->toArray();
            });
        } catch (\Exception $e) {
            Log::error('Failed to get users by department', [
                'departments' => $departments,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Get users by warehouse.
     *
     * @param array $warehouseIds
     * @return array
     */
    public function getUsersByWarehouse(array $warehouseIds)
    {
        try {
            // Cache the result for 5 minutes to avoid frequent database queries
            return Cache::remember('users_by_warehouse:' . implode(',', $warehouseIds), 300, function () use ($warehouseIds) {
                return User::whereIn('warehouse_id', $warehouseIds)->pluck('id')->toArray();
            });
        } catch (\Exception $e) {
            Log::error('Failed to get users by warehouse', [
                'warehouse_ids' => $warehouseIds,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Get users responsible for a product.
     *
     * @param int $productId
     * @return array
     */
    public function getUsersForProduct($productId)
    {
        try {
            // This would typically involve looking up product owners, category managers, etc.
            // For now, we'll return an empty array as the actual implementation would depend on your data model
            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get users for product', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Get users responsible for a location.
     *
     * @param int $locationId
     * @return array
     */
    public function getUsersForLocation($locationId)
    {
        try {
            // This would typically involve looking up location managers, zone supervisors, etc.
            // For now, we'll return an empty array as the actual implementation would depend on your data model
            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get users for location', [
                'location_id' => $locationId,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Get users subscribed to a notification type.
     *
     * @param string $notificationType
     * @return array
     */
    public function getUsersSubscribedToType($notificationType)
    {
        try {
            // This would typically involve looking up user notification preferences
            // For now, we'll return an empty array as the actual implementation would depend on your data model
            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get users subscribed to notification type', [
                'notification_type' => $notificationType,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Get all system administrators.
     *
     * @return array
     */
    public function getSystemAdministrators()
    {
        return $this->getUsersByRole(['admin']);
    }

    /**
     * Get users based on a custom filter.
     *
     * @param callable $filter
     * @return array
     */
    public function getUsersByCustomFilter(callable $filter)
    {
        try {
            return User::all()->filter($filter)->pluck('id')->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get users by custom filter', [
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
}