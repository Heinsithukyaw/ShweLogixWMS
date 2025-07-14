<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\UserManagement\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query();

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('display_name', 'like', '%' . $request->search . '%');
            });
        }

        $permissions = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module' => 'required|string|max:100',
            'category' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $permission = Permission::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data' => $permission
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $permission = Permission::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:permissions,name,' . $id,
            'display_name' => 'string|max:255',
            'description' => 'nullable|string',
            'module' => 'string|max:100',
            'category' => 'string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $permission = Permission::findOrFail($id);

            if ($permission->is_system_permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update system permission'
                ], 403);
            }

            $permission->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $permission = Permission::findOrFail($id);

            if ($permission->is_system_permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete system permission'
                ], 403);
            }

            $permission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getModules(): JsonResponse
    {
        $modules = Permission::distinct()->pluck('module');

        return response()->json([
            'success' => true,
            'data' => $modules
        ]);
    }

    public function getCategories(): JsonResponse
    {
        $categories = Permission::distinct()->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}