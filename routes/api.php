<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\PrioritySectorController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\PrintController;
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
    
    // // Front Desk routes
    // Route::middleware('role:FRONTDESK')->prefix('office')->group(function () {
    //     // We'll add these later
    //     Route::get('/queue/today', [QueueController::class, 'today']);
    //     Route::post('/queue/{queue}/call', [QueueController::class, 'call']);
    //     Route::post('/queue/{queue}/skip', [QueueController::class, 'skip']);
    //     Route::post('/queue/{queue}/complete', [QueueController::class, 'complete']);
    // });
});


        