<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/queue-monitor', function () {
    return Inertia::render('QueueMonitor');
})->name('queue-monitor');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
