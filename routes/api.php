<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

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