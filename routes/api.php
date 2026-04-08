<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FrontdeskController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\MonitorController;

use App\Http\Controllers\OfficeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\PrioritySectorController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\OfficeManagementController;
use App\Http\Controllers\ServiceManagementController;
use App\Http\Controllers\FrontdeskAnalyticsController;
use App\Http\Controllers\CsmAnalyticsController;

// Internal Transactions Controllers
use App\Http\Controllers\InternalRequestController;
use App\Http\Controllers\InternalRequestNotificationController;
use App\Http\Controllers\InternalEvaluationController;

// Chat Controller
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==================== PUBLIC ROUTES ====================

// Offices
Route::get('/offices', [OfficeController::class, 'index']);
Route::get('/offices/{office}', [OfficeController::class, 'show']);

// External Services (kiosk)
Route::get('/offices/{office}/services', [ServiceController::class, 'getByOffice']);
Route::get('/services/{service}', [ServiceController::class, 'show']);

// ✅ INTERNAL SERVICES (ginawa kong public para gumana sa internal transactions UI)
Route::get('/internal/offices/{office}/services', [ServiceController::class, 'getInternalServices']);

// Supporting data
Route::get('/barangays', [BarangayController::class, 'index']);
Route::get('/priority-sectors', [PrioritySectorController::class, 'index']);

// Queue
Route::post('/queue', [QueueController::class, 'store']);
Route::get('/queue/{queueNumber}', [QueueController::class, 'show']);
Route::get('/offices/{office}/queue/today', [QueueController::class, 'getTodayQueue']);

// Print
Route::patch('/queue/{id}/printed', [PrintController::class, 'markAsPrinted']);

// Login
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:20,1');

// Monitor
Route::prefix('monitor')->group(function () {
    Route::get('/office/{officeId}', [MonitorController::class, 'getMonitorData']);
    Route::get('/office/{officeId}/details', [MonitorController::class, 'getOfficeDetails']);
    Route::get('/office/{officeId}/current-serving', [MonitorController::class, 'getCurrentServing']);
    Route::get('/office/{officeId}/now-serving', [MonitorController::class, 'getNowServing']);
    Route::get('/office/{officeId}/waiting-list', [MonitorController::class, 'getWaitingList']);
});


// ==================== PROTECTED ROUTES ====================

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/verify', [AuthController::class, 'verify']);

    // ==================== CHAT ROUTES (Available for all authenticated users) ====================
    Route::prefix('chat')->group(function () {
        Route::get('/offices', [ChatController::class, 'getOffices']);
        Route::get('/messages/{officeId}', [ChatController::class, 'getMessages']);
        Route::post('/send', [ChatController::class, 'sendMessage']);
        Route::post('/upload', [ChatController::class, 'uploadFile']);
        Route::post('/read/{senderId}', [ChatController::class, 'markAsRead']);
        Route::get('/unread-count', [ChatController::class, 'getUnreadCount']);
        Route::delete('/message/{messageId}', [ChatController::class, 'deleteMessage']);
    });

    // ==================== SUPERADMIN ====================
    Route::middleware('role:SUPERADMIN')->prefix('superadmin')->group(function () {

        // User Management
        Route::get('/user-management/users', [UserManagementController::class, 'index']);
        Route::get('/user-management/offices', [UserManagementController::class, 'offices']);
        Route::post('/user-management/users', [UserManagementController::class, 'store']);
        Route::put('/user-management/users/{user}', [UserManagementController::class, 'update']);

        // Office Management
        Route::get('/office-management/offices', [OfficeManagementController::class, 'index']);
        Route::post('/office-management/offices', [OfficeManagementController::class, 'store']);
        Route::put('/office-management/offices/{office}', [OfficeManagementController::class, 'update']);
        Route::delete('/office-management/offices/{office}', [OfficeManagementController::class, 'destroy']);

        // Service Management
        Route::get('/office-management/offices/{office}/services', [ServiceManagementController::class, 'index']);
        Route::post('/office-management/offices/{office}/services', [ServiceManagementController::class, 'store']);
        Route::put('/office-management/offices/{office}/services/{service}', [ServiceManagementController::class, 'update']);
        Route::delete('/office-management/offices/{office}/services/{service}', [ServiceManagementController::class, 'destroy']);
        Route::patch('/office-management/offices/{office}/services/{service}/toggle-is-free', [ServiceManagementController::class, 'toggleIsFree']);
        Route::patch('/office-management/offices/{office}/services/{service}/toggle-status', [ServiceManagementController::class, 'toggleStatus']);

        // Analytics
        Route::get('/analytics/cards', [FrontdeskAnalyticsController::class, 'getCardStats']);
        Route::get('/analytics/client-satisfaction', [FrontdeskAnalyticsController::class, 'getClientSatisfactionDistribution']);
        Route::get('/analytics/barangay-distribution', [FrontdeskAnalyticsController::class, 'getBarangayDistribution']);
        Route::get('/analytics/lane-type', [FrontdeskAnalyticsController::class, 'getLaneTypeDistribution']);
        Route::get('/analytics/queue-summary', [FrontdeskAnalyticsController::class, 'getQueueSummary']);
        Route::get('/analytics/export-graphs', [FrontdeskAnalyticsController::class, 'exportGraphs']);
    });

    // ==================== FRONTDESK ====================
    Route::middleware('role:OFFICE FRONTDESK')->prefix('frontdesk')->group(function () {

        // Dashboard
        Route::get('/dashboard-stats', [FrontdeskController::class, 'getDashboardStats']);

        // CSM Analytics
        Route::get('/analytics/csm/overview', [CsmAnalyticsController::class, 'getOverviewStats']);
        Route::get('/analytics/csm/citizen-charter', [CsmAnalyticsController::class, 'getCitizenCharterCounts']);
        Route::get('/analytics/csm/sqd-results', [CsmAnalyticsController::class, 'getSqdResults']);
        Route::get('/analytics/csm/demographic-profile', [CsmAnalyticsController::class, 'getDemographicProfile']);
        Route::get('/analytics/csm/overall-score-per-service', [CsmAnalyticsController::class, 'getOverallScorePerService']);
        Route::post('/analytics/csm/export', [CsmAnalyticsController::class, 'exportTables']);
        Route::post('/analytics/csm/export-graphs', [CsmAnalyticsController::class, 'exportGraphs']);

        // Counter Management
        Route::get('/counters', [CounterController::class, 'index']);
        Route::get('/counters/available', [CounterController::class, 'getAvailableCounters']);
        Route::post('/counters', [CounterController::class, 'store']);
        Route::put('/counters/{id}/status', [CounterController::class, 'updateStatus']);
        Route::delete('/counters/{id}', [CounterController::class, 'destroy']);
        
        // Queue
        Route::get('/queue-table', [FrontdeskController::class, 'getQueueTable']);
        Route::post('/queue/call/{queueId}', [FrontdeskController::class, 'callQueue']);
        Route::post('/queue/skip-from-table/{queueId}', [FrontdeskController::class, 'skipFromTable']);
        Route::post('/queue/skip-from-counter/{queueId}', [FrontdeskController::class, 'skipFromCounter']);
        Route::post('/queue/complete/{queueId}', [FrontdeskController::class, 'completeTransaction']);
        Route::post('/queue/auto-skip-stale', [FrontdeskController::class, 'autoSkipStaleQueues']);

        // Counters
        Route::get('/counters', [CounterController::class, 'index']);
        Route::get('/counters/available', [CounterController::class, 'getAvailableCounters']);
        Route::post('/counters', [CounterController::class, 'store']);
        Route::put('/counters/{id}/status', [CounterController::class, 'updateStatus']);
        Route::delete('/counters/{id}', [CounterController::class, 'destroy']);

        // Evaluation
        Route::get('/evaluation/questions', [EvaluationController::class, 'getQuestions']);
        Route::get('/evaluation/transaction/{queueId}', [EvaluationController::class, 'getTransactionForEvaluation']);
        Route::post('/evaluation/submit/{queueId}', [EvaluationController::class, 'submitEvaluation']);
        Route::get('/evaluation/results/{queueId}', [EvaluationController::class, 'getEvaluationResults']);

        // Analytics
        Route::get('/analytics/cards', [FrontdeskAnalyticsController::class, 'getCardStats']);
        Route::get('/analytics/client-satisfaction', [FrontdeskAnalyticsController::class, 'getClientSatisfactionDistribution']);
        Route::get('/analytics/barangay-distribution', [FrontdeskAnalyticsController::class, 'getBarangayDistribution']);
        Route::get('/analytics/lane-type', [FrontdeskAnalyticsController::class, 'getLaneTypeDistribution']);
        Route::get('/analytics/queue-summary', [FrontdeskAnalyticsController::class, 'getQueueSummary']);
        Route::get('/analytics/export-graphs', [FrontdeskAnalyticsController::class, 'exportGraphs']);

        // ==================== INTERNAL TRANSACTIONS ====================
        Route::prefix('internal-transactions')->group(function () {

            // Dashboard
            Route::get('/dashboard', [InternalRequestController::class, 'dashboard']);

            // Requests
            Route::get('/requests', [InternalRequestController::class, 'index']);
            Route::post('/requests', [InternalRequestController::class, 'store']);
            Route::get('/requests/{id}', [InternalRequestController::class, 'show']);

            // Actions
            Route::post('/requests/{id}/accept', [InternalRequestController::class, 'accept']);
            Route::post('/requests/{id}/deny', [InternalRequestController::class, 'deny']);
            Route::post('/requests/{id}/complete', [InternalRequestController::class, 'complete']);

            // Notifications
            Route::get('/notifications', [InternalRequestNotificationController::class, 'index']);
            Route::get('/notifications/unread-count', [InternalRequestNotificationController::class, 'unreadCount']);
            Route::patch('/notifications/{id}/read', [InternalRequestNotificationController::class, 'markAsRead']);
            Route::patch('/notifications/read-all', [InternalRequestNotificationController::class, 'markAllAsRead']);

            // ✅ INTERNAL EVALUATION (separate controller)
            Route::prefix('evaluation')->group(function () {
                Route::get('/questions', [InternalEvaluationController::class, 'getQuestions']);
                Route::post('/submit/{id}', [InternalEvaluationController::class, 'submitEvaluation']);
                Route::get('/status/{id}', [InternalEvaluationController::class, 'checkEvaluationStatus']);
            });
        });
    });

});