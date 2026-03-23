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
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

 // Public routes for Kiosk
    Route::get('/offices', [OfficeController::class, 'index']);
    Route::get('/offices/{office}', [OfficeController::class, 'show']);
    Route::get('/offices/{office}/services', [ServiceController::class, 'getByOffice']);
    Route::get('/services/{service}', [ServiceController::class, 'show']);
    Route::get('/barangays', [BarangayController::class, 'index']);
    Route::get('/priority-sectors', [PrioritySectorController::class, 'index']);

    // Queue routes
    Route::post('/queue', [QueueController::class, 'store']);
    Route::get('/queue/{queueNumber}', [QueueController::class, 'show']);
    Route::get('/offices/{office}/queue/today', [QueueController::class, 'getTodayQueue']);

    // Print route
    Route::patch('/queue/{id}/printed', [PrintController::class, 'markAsPrinted']);

    // Public routes (no authentication required)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:20,1'); // 5 attempts per minute

    // Public routes (no authentication required)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:20,1');

    // Public Monitor Routes (no auth required)
    Route::prefix('monitor')->group(function () {
        // Get complete monitor dashboard data for an office
        Route::get('/office/{officeId}', [MonitorController::class, 'getMonitorData']);
    
        // Individual endpoints if needed (you can use these or just the combined one above)
        Route::get('/office/{officeId}/details', [MonitorController::class, 'getOfficeDetails']);
        Route::get('/office/{officeId}/current-serving', [MonitorController::class, 'getCurrentServing']);
        Route::get('/office/{officeId}/now-serving', [MonitorController::class, 'getNowServing']);
        Route::get('/office/{officeId}/waiting-list', [MonitorController::class, 'getWaitingList']);
    });

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    // Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/verify', [AuthController::class, 'verify']);

    Route::middleware('role:SUPERADMIN')->prefix('superadmin')->group(function () {
        Route::get('/user-management/users', [UserManagementController::class, 'index']);
        Route::get('/user-management/offices', [UserManagementController::class, 'offices']);
        Route::post('/user-management/users', [UserManagementController::class, 'store']);
        Route::put('/user-management/users/{user}', [UserManagementController::class, 'update']);

        Route::get('/office-management/offices', [OfficeManagementController::class, 'index']);
        Route::post('/office-management/offices', [OfficeManagementController::class, 'store']);
        Route::put('/office-management/offices/{office}', [OfficeManagementController::class, 'update']);
        Route::delete('/office-management/offices/{office}', [OfficeManagementController::class, 'destroy']);

        Route::get('/office-management/offices/{office}/services', [ServiceManagementController::class, 'index']);
        Route::post('/office-management/offices/{office}/services', [ServiceManagementController::class, 'store']);
        Route::put('/office-management/offices/{office}/services/{service}', [ServiceManagementController::class, 'update']);
        Route::delete('/office-management/offices/{office}/services/{service}', [ServiceManagementController::class, 'destroy']);
        Route::patch('/office-management/offices/{office}/services/{service}/toggle-is-free', [ServiceManagementController::class, 'toggleIsFree']);
        Route::patch('/office-management/offices/{office}/services/{service}/toggle-status', [ServiceManagementController::class, 'toggleStatus']);
    });
    
    // Front Desk routes
    Route::middleware('role:OFFICE FRONTDESK')->prefix('frontdesk')->group(function () {
    //     // We'll add these later
    //     Route::get('/queue/today', [QueueController::class, 'today']);
    //     Route::post('/queue/{queue}/call', [QueueController::class, 'call']);
    //     Route::post('/queue/{queue}/skip', [QueueController::class, 'skip']);
    //     Route::post('/queue/{queue}/complete', [QueueController::class, 'complete']);
    // Dashboard stats
    Route::get('/dashboard-stats', [FrontdeskController::class, 'getDashboardStats']);
        
    // Queue table
    Route::get('/queue-table', [FrontdeskController::class, 'getQueueTable']);
        
    // Queue actions
    Route::post('/queue/call/{queueId}', [FrontdeskController::class, 'callQueue']);
    Route::post('/queue/skip-from-table/{queueId}', [FrontdeskController::class, 'skipFromTable']);
    Route::post('/queue/skip-from-counter/{queueId}', [FrontdeskController::class, 'skipFromCounter']);
    Route::post('/queue/complete/{queueId}', [FrontdeskController::class, 'completeTransaction']);

    // Analytics
    Route::get('/analytics/cards', [FrontdeskAnalyticsController::class, 'getCardStats']);
    Route::get('/analytics/client-satisfaction', [FrontdeskAnalyticsController::class, 'getClientSatisfactionDistribution']);
    Route::get('/analytics/lane-type', [FrontdeskAnalyticsController::class, 'getLaneTypeDistribution']);
    Route::get('/analytics/queue-summary', [FrontdeskAnalyticsController::class, 'getQueueSummary']);

    // Counter Management
    Route::get('/counters', [CounterController::class, 'index']);
    Route::get('/counters/available', [CounterController::class, 'getAvailableCounters']);
    Route::post('/counters', [CounterController::class, 'store']);
    Route::put('/counters/{id}/status', [CounterController::class, 'updateStatus']);
    Route::delete('/counters/{id}', [CounterController::class, 'destroy']);

    // Evaluation Routes
    Route::get('/evaluation/questions', [EvaluationController::class, 'getQuestions']);
    Route::get('/evaluation/transaction/{queueId}', [EvaluationController::class, 'getTransactionForEvaluation']);
    Route::post('/evaluation/submit/{queueId}', [EvaluationController::class, 'submitEvaluation']);
    Route::get('/evaluation/results/{queueId}', [EvaluationController::class, 'getEvaluationResults']);
    
    });

});
