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
                ->with(['transaction' => function($query) {
                    $query->with(['fromOffice', 'toOffice']);
                }])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Format notifications for better display and return as a simple array
            $formattedNotifications = $notifications->through(function ($notification) {
                $request = $notification->transaction;
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $notification->created_at->diffForHumans(),
                    'request' => $request ? [
                        'id' => $request->id,
                        'transaction_id' => $request->transaction_id,
                        'from_office' => $request->fromOffice->office_name . ' (' . $request->fromOffice->office_acronym . ')',
                        'to_office' => $request->toOffice->office_name . ' (' . $request->toOffice->office_acronym . ')',
                        'status' => $request->status,
                    ] : null,
                ];
            })->items();
            
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
    public function markAsRead(Request $request, $id)
    {
        try {
            $user = $request->user();

            $notification = InternalRequestNotification::findOrFail($id);

            // Check if user owns this notification using the same guard as index/unreadCount
            if (!$user || $notification->user_id !== $user->id) {
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