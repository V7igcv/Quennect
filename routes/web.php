<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Catch-all route for Vue.js
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
