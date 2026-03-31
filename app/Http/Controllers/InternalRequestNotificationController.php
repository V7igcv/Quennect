<?php

namespace App\Http\Controllers;

use App\Models\InternalRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InternalRequestNotificationController extends Controller
{
    /**
     * Get all notifications for the logged-in user
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->per_page ?? 20;
            
            $notifications = InternalRequestNotification::where('user_id', $user->id)
                ->with(['request' => function($query) {
                    $query->with(['fromOffice', 'toOffice']);
                }])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            // Format notifications for better display
            $formattedNotifications = $notifications->through(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $notification->created_at->diffForHumans(),
                    'request' => $notification->request ? [
                        'id' => $notification->request->id,
                        'transaction_id' => $notification->request->transaction_id,
                        'from_office' => $notification->request->fromOffice->office_name . ' (' . $notification->request->fromOffice->office_acronym . ')',
                        'to_office' => $notification->request->toOffice->office_name . ' (' . $notification->request->toOffice->office_acronym . ')',
                        'status' => $notification->request->status,
                    ] : null,
                ];
            });
            
            $unreadCount = InternalRequestNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $formattedNotifications,
                    'unread_count' => $unreadCount,
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'last_page' => $notifications->lastPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications'
            ], 500);
        }
    }
    
    /**
     * Get unread notification count
     */
    public function unreadCount(Request $request)
    {
        try {
            $user = $request->user();
            
            $count = InternalRequestNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'count' => $count
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get unread count: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count'
            ], 500);
        }
    }
    
    /**
     * Mark a single notification as read
     */
    public function markAsRead($id)
    {
        try {
            $notification = InternalRequestNotification::findOrFail($id);
            
            // Check if user owns this notification
            if ($notification->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            $notification->markAsRead();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'data' => [
                    'id' => $notification->id,
                    'is_read' => $notification->is_read,
                    'read_at' => $notification->read_at
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }
    
    /**
     * Mark all notifications as read for the logged-in user
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = $request->user();
            
            $updated = InternalRequestNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
            
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
                'data' => [
                    'updated_count' => $updated
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read'
            ], 500);
        }
    }
}