<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Health check de infraestructura (BD, Redis, cola). No pasa por TenantMiddleware.
Route::get('/health', [HealthController::class, 'index']);
