<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FrontdeskController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\MonitorController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

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
    
    // // Superadmin only routes
    // Route::middleware('role:SUPERADMIN')->prefix('admin')->group(function () {
    //     // We'll add these later
    //     Route::apiResource('users', UserController::class);
    //     Route::apiResource('offices', OfficeController::class);
    // });
    
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