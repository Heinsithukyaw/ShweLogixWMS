<?php

namespace App\Http\Controllers\Admin\api\v1\notification;

use App\Models\Notification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Notification::query();
        
        // Filter by user if specified
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        } else {
            // Default to current user
            $query->where('user_id', Auth::id());
        }
        
        // Filter by read status if specified
        if ($request->has('is_read')) {
            $query->where('is_read', $request->is_read);
        }
        
        // Filter by type if specified
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Order by created_at desc
        $query->orderBy('created_at', 'desc');
        
        // Paginate results
        $notifications = $query->paginate($request->per_page ?? 15);
        
        return response()->json([
            'status' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        // Check if the notification belongs to the current user
        if ($notification->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        
        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read',
            'data' => $notification,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $query = Notification::where('user_id', Auth::id())
            ->where('is_read', false);
        
        // Filter by type if specified
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        $count = $query->count();
        
        $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        
        return response()->json([
            'status' => true,
            'message' => "{$count} notifications marked as read",
        ]);
    }

    /**
     * Get unread notification count.
     */
    public function getUnreadCount()
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();
        
        return response()->json([
            'status' => true,
            'data' => [
                'count' => $count,
            ],
        ]);
    }
}