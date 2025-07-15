<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\UserManagement\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query();

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');
            });
        }

        $tenants = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $tenants
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:tenants',
            'subscription_plan' => 'nullable|string|max:100',
            'user_limit' => 'nullable|integer|min:1',
            'storage_limit' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tenant = Tenant::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'domain' => $request->domain,
                'subscription_plan' => $request->subscription_plan,
                'user_limit' => $request->user_limit,
                'storage_limit' => $request->storage_limit,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tenant created successfully',
                'data' => $tenant
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $tenant = Tenant::with(['users'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $tenant
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'domain' => 'nullable|string|max:255|unique:tenants,domain,' . $id,
            'subscription_plan' => 'nullable|string|max:100',
            'user_limit' => 'nullable|integer|min:1',
            'storage_limit' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tenant = Tenant::findOrFail($id);

            $updateData = $request->only(['name', 'domain', 'subscription_plan', 'user_limit', 'storage_limit']);
            
            if ($request->has('name')) {
                $updateData['slug'] = Str::slug($request->name);
            }

            $tenant->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Tenant updated successfully',
                'data' => $tenant
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($id);
            $tenant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tenant deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activate($id): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($id);
            $tenant->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Tenant activated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivate($id): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($id);
            $tenant->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Tenant deactivated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUsers($id): JsonResponse
    {
        try {
            $tenant = Tenant::with('users')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $tenant->users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found'
            ], 404);
        }
    }

    public function getStatistics($id): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($id);

            $statistics = [
                'total_users' => $tenant->users()->count(),
                'active_users' => $tenant->users()->where('is_active', true)->count(),
                'storage_used' => 0, // This would be calculated based on actual usage
                'storage_limit' => $tenant->storage_limit,
                'subscription_status' => $tenant->subscription_expires_at > now() ? 'active' : 'expired'
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found'
            ], 404);
        }
    }
}