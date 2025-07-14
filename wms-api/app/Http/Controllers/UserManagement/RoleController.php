<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Role::with(['permissions', 'users']);

        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('display_name', 'like', '%' . $request->search . '%');
            });
        }

        $roles = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tenant_id' => 'nullable|exists:tenants,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'tenant_id' => $request->tenant_id,
                'created_by' => auth()->id()
            ]);

            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            $role->load(['permissions', 'users']);

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $role = Role::with(['permissions', 'users'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $role
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'display_name' => 'string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::findOrFail($id);

            $role->update([
                'name' => $request->get('name', $role->name),
                'display_name' => $request->get('display_name', $role->display_name),
                'description' => $request->get('description', $role->description),
                'updated_by' => auth()->id()
            ]);

            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            $role->load(['permissions', 'users']);

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => $role
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            if ($role->is_system_role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete system role'
                ], 403);
            }

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assignPermissions(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::findOrFail($id);
            $role->permissions()->sync($request->permissions);

            return response()->json([
                'success' => true,
                'message' => 'Permissions assigned successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function revokePermission($id, $permissionId): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);
            $role->permissions()->detach($permissionId);

            return response()->json([
                'success' => true,
                'message' => 'Permission revoked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUsers($id): JsonResponse
    {
        try {
            $role = Role::with('users')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $role->users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }
    }
}