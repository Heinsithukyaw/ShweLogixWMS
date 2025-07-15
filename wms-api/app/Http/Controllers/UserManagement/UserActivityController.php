<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\UserManagement\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UserActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserActivityLog::with(['user', 'tenant']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->has('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $activities = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    public function getUserActivity(Request $request, $userId): JsonResponse
    {
        $query = UserActivityLog::where('user_id', $userId);

        if ($request->has('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $activities = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30));
            $dateTo = $request->get('date_to', now());

            // Activity by type
            $activityByType = UserActivityLog::select('activity_type', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('activity_type')
                ->get();

            // Activity by module
            $activityByModule = UserActivityLog::select('module', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('module')
                ->get();

            // Daily activity trend
            $dailyActivity = UserActivityLog::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            // Top active users
            $topUsers = UserActivityLog::select('user_id', DB::raw('count(*) as activity_count'))
                ->with('user:id,name,email')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('user_id')
                ->orderBy('activity_count', 'desc')
                ->limit(10)
                ->get();

            // Error rate
            $totalActivities = UserActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])->count();
            $errorActivities = UserActivityLog::where('status', 'error')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count();
            
            $errorRate = $totalActivities > 0 ? ($errorActivities / $totalActivities) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'activity_by_type' => $activityByType,
                    'activity_by_module' => $activityByModule,
                    'daily_activity' => $dailyActivity,
                    'top_users' => $topUsers,
                    'total_activities' => $totalActivities,
                    'error_rate' => round($errorRate, 2)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request): JsonResponse
    {
        try {
            $query = UserActivityLog::with(['user', 'tenant']);

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $activities = $query->orderBy('created_at', 'desc')->get();

            // Convert to CSV format
            $csvData = [];
            $csvData[] = ['Date', 'User', 'Activity Type', 'Module', 'Description', 'IP Address', 'Status'];

            foreach ($activities as $activity) {
                $csvData[] = [
                    $activity->created_at->format('Y-m-d H:i:s'),
                    $activity->user ? $activity->user->name : 'N/A',
                    $activity->activity_type,
                    $activity->module,
                    $activity->activity_description,
                    $activity->ip_address,
                    $activity->status
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $csvData,
                'filename' => 'user_activity_' . now()->format('Y-m-d_H-i-s') . '.csv'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}