<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QueueMonitorController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/queue-monitor', [QueueMonitorController::class, 'index'])
    ->name('api.queue-monitor'); 