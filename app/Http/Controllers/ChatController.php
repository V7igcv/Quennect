<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Get all offices except the current office
     * GET /api/chat/offices
     */
    public function getOffices(Request $request)
    {
        try {
            $currentOfficeId = $request->user()->office_id;
            
            if (!$currentOfficeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No office assigned to this user'
                ], 400);
            }
            
            $offices = Office::where('id', '!=', $currentOfficeId)
                ->where('is_active', true)
                ->select('id', 'office_name as name', 'office_description', 'office_acronym', 'logo')
                ->get()
                ->map(function ($office) use ($currentOfficeId) {
                    // Get last message
                    $lastMessage = ChatMessage::betweenOffices($currentOfficeId, $office->id)
                        ->latest()
                        ->first();
                    
                    // Get unread count
                    $unreadCount = ChatMessage::where('sender_office_id', $office->id)
                        ->where('receiver_office_id', $currentOfficeId)
                        ->where('is_read', false)
                        ->count();
                    
                    return [
                        'id' => $office->id,
                        'name' => $office->name,
                        'description' => $office->office_description,
                        'acronym' => $office->office_acronym,
                        'logo_url' => $office->logo ? $office->logo_url : null,
                        'isOnline' => $office->is_active,
                        'lastMessage' => $lastMessage?->content ?? null,
                        'lastMessageTime' => $lastMessage?->created_at?->diffForHumans() ?? null,
                        'lastMessageTimestamp' => $lastMessage?->created_at?->timestamp ?? 0, // Add this for sorting
                        'unreadCount' => $unreadCount
                    ];
                });
            
            // Sort offices by last message timestamp (newest first)
            // Offices with messages come first, sorted by latest message
            $offices = $offices->sortByDesc(function ($office) {
                return $office['lastMessageTimestamp'];
            })->values();
            
            return response()->json([
                'success' => true,
                'data' => $offices
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching offices: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch offices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages between current office and another office
     * GET /api/chat/messages/{officeId}
     */
    public function getMessages(Request $request, $officeId)
    {
        try {
            $currentOfficeId = $request->user()->office_id;
            
            if (!$currentOfficeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Verify office exists
            $office = Office::find($officeId);
            if (!$office) {
                return response()->json([
                    'success' => false,
                    'message' => 'Office not found'
                ], 404);
            }
            
            $messages = ChatMessage::betweenOffices($currentOfficeId, $officeId)
                ->orderBy('created_at', 'asc') // Oldest first, newest at bottom
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'sender_id' => $message->sender_office_id,
                        'receiver_id' => $message->receiver_office_id,
                        'type' => $message->type,
                        'content' => $message->type === 'file' && $message->file_path 
                            ? asset('storage/' . $message->file_path) 
                            : $message->content,
                        'file_name' => $message->file_name,
                        'file_size' => $message->file_size,
                        'formatted_file_size' => $message->getFormattedFileSize(),
                        'is_read' => $message->is_read,
                        'read_at' => $message->read_at,
                        'created_at' => $message->created_at->toISOString(),
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a text message
     * POST /api/chat/send
     */
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'receiver_id' => 'required|exists:offices,id',
                'content' => 'required|string|max:5000'
            ]);
            
            $currentOfficeId = $request->user()->office_id;
            
            if (!$currentOfficeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Don't allow sending to self
            if ($currentOfficeId == $request->receiver_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send message to yourself'
                ], 400);
            }
            
            $message = ChatMessage::create([
                'sender_office_id' => $currentOfficeId,
                'receiver_office_id' => $request->receiver_id,
                'type' => 'text',
                'content' => $request->content,
                'is_read' => false
            ]);

            event(new ChatMessageSent($message));
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_office_id,
                    'receiver_id' => $message->receiver_office_id,
                    'type' => $message->type,
                    'content' => $message->content,
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at->toISOString(),
                ]
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload and send a file
     * POST /api/chat/upload
     */
    public function uploadFile(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // Max 10MB
                'receiver_id' => 'required|exists:offices,id'
            ]);
            
            $currentOfficeId = $request->user()->office_id;
            
            if (!$currentOfficeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Don't allow sending to self
            if ($currentOfficeId == $request->receiver_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send message to yourself'
                ], 400);
            }
            
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
            
            // Store file
            $path = $file->store('chat_files/' . date('Y/m/d'), 'public');
            
            // Determine type
            $type = str_starts_with($mimeType, 'image/') ? 'image' : 'file';
            
            $message = ChatMessage::create([
                'sender_office_id' => $currentOfficeId,
                'receiver_office_id' => $request->receiver_id,
                'type' => $type,
                'content' => $path,
                'file_name' => $originalName,
                'file_path' => $path,
                'file_mime_type' => $mimeType,
                'file_size' => $fileSize,
                'is_read' => false
            ]);

            event(new ChatMessageSent($message));
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_office_id,
                    'receiver_id' => $message->receiver_office_id,
                    'type' => $message->type,
                    'content' => asset('storage/' . $path),
                    'file_name' => $message->file_name,
                    'file_size' => $message->file_size,
                    'formatted_file_size' => $message->getFormattedFileSize(),
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at->toISOString(),
                ]
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Error uploading file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark messages as read
     * POST /api/chat/read/{senderId}
     */
    public function markAsRead(Request $request, $senderId)
    {
        try {
            $currentOfficeId = $request->user()->office_id;
            
            if (!$currentOfficeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            $updated = ChatMessage::where('sender_office_id', $senderId)
                ->where('receiver_office_id', $currentOfficeId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
                'updated_count' => $updated
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error marking messages as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read'
            ], 500);
        }
    }

    /**
     * Get unread message count for current office
     * GET /api/chat/unread-count
     */
    public function getUnreadCount(Request $request)
    {
        try {
            $currentOfficeId = $request->user()->office_id;
            
            if (!$currentOfficeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            $count = ChatMessage::where('receiver_office_id', $currentOfficeId)
                ->where('is_read', false)
                ->count();
            
            return response()->json([
                'success' => true,
                'unread_count' => $count
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting unread count: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count'
            ], 500);
        }
    }

    /**
     * Delete a message
     * DELETE /api/chat/message/{messageId}
     */
    public function deleteMessage(Request $request, $messageId)
    {
        try {
            $currentOfficeId = $request->user()->office_id;
            
            $message = ChatMessage::where('id', $messageId)
                ->where(function ($q) use ($currentOfficeId) {
                    $q->where('sender_office_id', $currentOfficeId)
                      ->orWhere('receiver_office_id', $currentOfficeId);
                })
                ->first();
            
            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found'
                ], 404);
            }
            
            // Delete file if exists
            if ($message->file_path && Storage::disk('public')->exists($message->file_path)) {
                Storage::disk('public')->delete($message->file_path);
            }
            
            $message->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message'
            ], 500);
        }
    }
}