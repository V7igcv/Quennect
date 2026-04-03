<?php

namespace App\Http\Controllers;

use App\Events\InternalRequestNotificationCreated;
use App\Models\InternalTransaction;
use App\Models\Service;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InternalRequestController extends Controller
{
    /**
     * Get dashboard statistics for the logged-in user's office
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            $officeId = $user->office_id;
            
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Base queries
            $receivedQuery = InternalTransaction::where('to_office_id', $officeId);
            $sentQuery = InternalTransaction::where('from_office_id', $officeId);

            // Apply date filters if present
            if ($startDate && $endDate) {
                // Determine which date to filter by based on your business logic 
                // Using transaction_date as the primary date filter
                $receivedQuery->whereBetween('transaction_date', [$startDate, $endDate]);
                $sentQuery->whereBetween('transaction_date', [$startDate, $endDate]);
            }
            
            $stats = [
                'received' => [
                    'pending' => (clone $receivedQuery)->where('status', InternalTransaction::STATUS_PENDING)->count(),
                    'on_process' => (clone $receivedQuery)->where('status', InternalTransaction::STATUS_ON_PROCESS)->count(),
                    'completed' => (clone $receivedQuery)->where('status', InternalTransaction::STATUS_COMPLETED)->count(),
                    'denied' => (clone $receivedQuery)->where('status', InternalTransaction::STATUS_DENIED)->count(),
                    'overdue' => (clone $receivedQuery)->where('status', InternalTransaction::STATUS_OVERDUE)->count(),
                    'total' => (clone $receivedQuery)->count(),
                ],
                'sent' => [
                    'pending' => (clone $sentQuery)->where('status', InternalTransaction::STATUS_PENDING)->count(),
                    'on_process' => (clone $sentQuery)->where('status', InternalTransaction::STATUS_ON_PROCESS)->count(),
                    'completed' => (clone $sentQuery)->where('status', InternalTransaction::STATUS_COMPLETED)->count(),
                    'denied' => (clone $sentQuery)->where('status', InternalTransaction::STATUS_DENIED)->count(),
                    'overdue' => (clone $sentQuery)->where('status', InternalTransaction::STATUS_OVERDUE)->count(),
                    'total' => (clone $sentQuery)->count(),
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch dashboard: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data'
            ], 500);
        }
    }
    
    /**
     * Get all requests for the logged-in user's office (formatted for table)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $officeId = $user->office_id;
            $type = $request->type; // 'received' or 'sent'
            $status = $request->status; // filter by status
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            
            $query = $type === 'sent' 
                ? InternalTransaction::where('from_office_id', $officeId)
                : InternalTransaction::where('to_office_id', $officeId);
            
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if ($startDate && $endDate) {
                $query->whereBetween('transaction_date', [$startDate, $endDate]);
            }
            
            $requests = $query->with(['fromOffice', 'toOffice', 'evaluationSession'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);
            
            // Format for table display
            $formattedRequests = $requests->through(function ($request) use ($type) {
                // Calculate remaining days and deadline status
                $remainingDays = null;
                $deadlineStatus = null;
                
                if ($request->expected_completion_date) {
                    $now = Carbon::now();
                    $deadline = Carbon::parse($request->expected_completion_date);
                    $remainingDays = $now->diffInDays($deadline, false);
                    
                    if ($remainingDays < 0) {
                        $deadlineStatus = ['status' => 'overdue', 'label' => 'Overdue', 'color' => 'red'];
                    } elseif ($remainingDays == 0) {
                        $deadlineStatus = ['status' => 'urgent', 'label' => 'Today', 'color' => 'orange'];
                    } elseif ($remainingDays <= 3) {
                        $deadlineStatus = ['status' => 'soon', 'label' => "{$remainingDays} days left", 'color' => 'yellow'];
                    } else {
                        $deadlineStatus = ['status' => 'ok', 'label' => "{$remainingDays} days left", 'color' => 'green'];
                    }
                }
                
                return [
                    'id' => $request->id,
                    'transaction_id' => $request->transaction_id,
                    'from_office' => $request->fromOffice->office_name . ' (' . $request->fromOffice->office_acronym . ')',
                    'from_office_id' => $request->fromOffice->id,
                    'to_office' => $request->toOffice->office_name . ' (' . $request->toOffice->office_acronym . ')',
                    'to_office_id' => $request->toOffice->id,
                    'services' => $request->getServicesListAttribute()->pluck('service_name')->join(', '),
                    'service_ids' => $request->service_ids,
                    'full_name' => $request->full_name,
                    'contact_number' => $request->contact_number,
                    'requirement_link' => $request->requirement_link,
                    'status' => $request->status,
                    'status_label' => $this->getStatusLabel($request->status),
                    'status_color' => $this->getStatusColor($request->status),
                    'request_notes' => $request->request_notes,
                    'denial_reason' => $request->denial_reason,
                    'completion_notes' => $request->completion_notes,
                    'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $request->created_at->format('M d, Y h:i A'),
                    'accepted_at' => $request->accepted_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $request->completed_at?->format('Y-m-d H:i:s'),
                    'expected_completion_date' => $request->expected_completion_date?->format('Y-m-d'),
                    'remaining_days' => $remainingDays,
                    'deadline_status' => $deadlineStatus,
                    'has_evaluation' => (bool) $request->evaluationSession,
                    'can_accept' => $type === 'received' && $request->status === InternalTransaction::STATUS_PENDING,
                    'can_deny' => $type === 'received' && $request->status === InternalTransaction::STATUS_PENDING,
                    'can_complete' => $type === 'received' && $request->status === InternalTransaction::STATUS_ON_PROCESS,
                    'can_evaluate' => $type === 'received' && $request->status === InternalTransaction::STATUS_COMPLETED && !$request->evaluationSession,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'requests' => $formattedRequests,
                    'pagination' => [
                        'current_page' => $requests->currentPage(),
                        'last_page' => $requests->lastPage(),
                        'per_page' => $requests->perPage(),
                        'total' => $requests->total(),
                        'from' => $requests->firstItem(),
                        'to' => $requests->lastItem(),
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch requests: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch requests'
            ], 500);
        }
    }
    
    /**
     * Create a new request
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'to_office_id' => 'required|exists:offices,id',
                'service_ids' => 'required|array|min:1',
                'service_ids.*' => 'exists:services,id',
                'full_name' => 'required|string|max:150',
                'contact_number' => 'required|string|max:20',
                'requirement_link' => 'nullable|url',
                'request_notes' => 'nullable|string',
                'transaction_date' => 'required|date',
                'expected_completion_date' => 'required|date',
            ]);
            
            $user = $request->user();

            $internalTransaction = DB::transaction(function () use ($validated, $user) {
                $transaction = InternalTransaction::create([
                    'transaction_id' => InternalTransaction::generateTransactionId(),
                    'from_office_id' => $user->office_id,
                    'to_office_id' => $validated['to_office_id'],
                    'office_id' => $validated['to_office_id'],
                    'service_ids' => $validated['service_ids'],
                    'full_name' => $validated['full_name'],
                    'contact_number' => $validated['contact_number'],
                    'requirement_link' => $validated['requirement_link'] ?? null,
                    'request_notes' => $validated['request_notes'] ?? null,
                    'status' => InternalTransaction::STATUS_PENDING,
                    'transaction_date' => $validated['transaction_date'],
                    'expected_completion_date' => $validated['expected_completion_date'],
                    'requested_at' => now(),
                    'created_by' => $user->id,
                ]);

                // Link internal transaction to services in the shared pivot table
                // so that analytics can join via queue_transaction_services
                // using internal_transaction_id (queue_transaction_id stays null).
                foreach ($validated['service_ids'] as $serviceId) {
                    DB::table('queue_transaction_services')->insert([
                        'queue_transaction_id' => null,
                        'internal_transaction_id' => $transaction->id,
                        'service_id' => $serviceId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                // Notification for REQUESTING OFFICE (self)
                $this->createNotification(
                    $transaction,
                    $user->office_id,
                    'Request Submitted',
                    "Your request (#{$transaction->transaction_id}) has been submitted successfully.",
                    'request_created_self'
                );
                
                // Notification for RECEIVING OFFICE
                $this->createNotification(
                    $transaction,
                    $validated['to_office_id'],
                    'New Request Received',
                    "New request (#{$transaction->transaction_id}) from {$user->office->office_name}",
                    'request_created_receiver'
                );
                
                return $transaction;
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Request submitted successfully',
                'data' => $internalTransaction->load(['fromOffice', 'toOffice'])
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create request: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create request: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single request details
     */
    public function show($id)
    {
        try {
            $request = InternalTransaction::with(['fromOffice', 'toOffice', 'creator', 'processor'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $request->id,
                    'transaction_id' => $request->transaction_id,
                    'from_office' => $request->fromOffice->office_name . ' (' . $request->fromOffice->office_acronym . ')',
                    'from_office_id' => $request->fromOffice->id,
                    'to_office' => $request->toOffice->office_name . ' (' . $request->toOffice->office_acronym . ')',
                    'to_office_id' => $request->toOffice->id,
                    'services' => $request->getServicesListAttribute(),
                    'service_ids' => $request->service_ids,
                    'full_name' => $request->full_name,
                    'contact_number' => $request->contact_number,
                    'requirement_link' => $request->requirement_link,
                    'status' => $request->status,
                    'status_label' => $this->getStatusLabel($request->status),
                    'request_notes' => $request->request_notes,
                    'denial_reason' => $request->denial_reason,
                    'completion_notes' => $request->completion_notes,
                    'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                    'accepted_at' => $request->accepted_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $request->completed_at?->format('Y-m-d H:i:s'),
                    'denied_at' => $request->denied_at?->format('Y-m-d H:i:s'),
                    'expected_completion_date' => $request->expected_completion_date?->format('Y-m-d'),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Request not found'
            ], 404);
        }
    }
    
    /**
     * Accept a request
     */
    public function accept(Request $request, $id)
    {
        try {
            $internalTransaction = InternalTransaction::findOrFail($id);
            $user = $request->user();
            
            if ($internalTransaction->to_office_id !== $user->office_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to accept this request'
                ], 403);
            }
            
            if ($internalTransaction->status !== InternalTransaction::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request cannot be accepted'
                ], 400);
            }
            
            DB::transaction(function () use ($internalTransaction, $user) {
                $internalTransaction->update([
                    'status' => InternalTransaction::STATUS_ON_PROCESS,
                    'accepted_at' => now(),
                    'processed_by' => $user->id,
                ]);
                
                $this->createNotification(
                    $internalTransaction,
                    $internalTransaction->from_office_id,
                    'Request Accepted',
                    "Your request (#{$internalTransaction->transaction_id}) has been accepted and is now being processed.",
                    'accepted'
                );
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Request accepted successfully',
                'data' => $internalTransaction->fresh()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to accept request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept request'
            ], 500);
        }
    }
    
    /**
     * Deny a request with reason
     */
    public function deny(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'denial_reason' => 'required|string'
            ]);
            
            $internalTransaction = InternalTransaction::findOrFail($id);
            $user = $request->user();
            
            if ($internalTransaction->to_office_id !== $user->office_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to deny this request'
                ], 403);
            }
            
            if ($internalTransaction->status !== InternalTransaction::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request cannot be denied'
                ], 400);
            }
            
            DB::transaction(function () use ($internalTransaction, $user, $validated) {
                $internalTransaction->update([
                    'status' => InternalTransaction::STATUS_DENIED,
                    'denial_reason' => $validated['denial_reason'],
                    'denied_at' => now(),
                    'processed_by' => $user->id,
                ]);
                
                $this->createNotification(
                    $internalTransaction,
                    $internalTransaction->from_office_id,
                    'Request Denied',
                    "Your request (#{$internalTransaction->transaction_id}) has been denied. Reason: {$validated['denial_reason']}",
                    'denied'
                );
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Request denied successfully',
                'data' => $internalTransaction->fresh()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to deny request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to deny request'
            ], 500);
        }
    }
    
    /**
     * Complete a request
     */
    public function complete(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'completion_notes' => 'required|string'
            ]);
            
            $internalTransaction = InternalTransaction::findOrFail($id);
            $user = $request->user();
            
            if ($internalTransaction->to_office_id !== $user->office_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to complete this request'
                ], 403);
            }
            
            if ($internalTransaction->status !== InternalTransaction::STATUS_ON_PROCESS) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request cannot be completed'
                ], 400);
            }
            
            DB::transaction(function () use ($internalTransaction, $user, $validated) {
                $internalTransaction->update([
                    'status' => InternalTransaction::STATUS_COMPLETED,
                    'completion_notes' => $validated['completion_notes'],
                    'completed_at' => now(),
                    'processed_by' => $user->id,
                ]);
                
                $this->createNotification(
                    $internalTransaction,
                    $internalTransaction->from_office_id,
                    'Request Completed',
                    "Your request (#{$internalTransaction->transaction_id}) has been completed. Note: {$validated['completion_notes']}",
                    'completed'
                );
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Request completed successfully',
                'data' => $internalTransaction->fresh()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to complete request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete request'
            ], 500);
        }
    }
    
    /**
     * Helper: Create notification
     */
    private function createNotification($transaction, $officeId, $title, $message, $type)
    {
        $users = \App\Models\User::where('office_id', $officeId)->get();
        
        foreach ($users as $user) {
            $notification = \App\Models\InternalRequestNotification::create([
                'internal_transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
            ]);

            event(new InternalRequestNotificationCreated($notification));
        }
    }
    
    /**
     * Helper: Get status label
     */
    private function getStatusLabel($status)
    {
        return match($status) {
            InternalTransaction::STATUS_PENDING => 'Pending',
            InternalTransaction::STATUS_ON_PROCESS => 'On Process',
            InternalTransaction::STATUS_COMPLETED => 'Completed',
            InternalTransaction::STATUS_DENIED => 'Denied',
            InternalTransaction::STATUS_OVERDUE => 'Overdue',
            default => ucfirst($status),
        };
    }
    
    /**
     * Helper: Get status color
     */
    private function getStatusColor($status)
    {
        return match($status) {
            InternalTransaction::STATUS_PENDING => 'yellow',
            InternalTransaction::STATUS_ON_PROCESS => 'blue',
            InternalTransaction::STATUS_COMPLETED => 'green',
            InternalTransaction::STATUS_DENIED => 'red',
            InternalTransaction::STATUS_OVERDUE => 'orange',
            default => 'gray',
        };
    }
}