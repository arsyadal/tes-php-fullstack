<?php

use App\Http\Controllers\MyClientController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Route ini otomatis diberi prefix '/api' dan middleware 'api'
| oleh RouteServiceProvider.
|
*/

// Resource routes untuk CRUD client
Route::apiResource('clients', MyClientController::class);

// Route tambahan untuk akses via slug
Route::get('clients/slug/{slug}', [MyClientController::class, 'showBySlug']);
