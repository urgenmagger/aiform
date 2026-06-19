<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MetricsController;
use App\Http\Middleware\ApiRequestLogger;

Route::middleware([ApiRequestLogger::class])->group(function () {
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/metrics', [MetricsController::class, 'index']);
    Route::post('/contact', [ContactController::class, 'store']);
});
