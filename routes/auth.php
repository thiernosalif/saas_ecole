<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes — API (token Sanctum, mobile Flutter)
|--------------------------------------------------------------------------
|
| cf. PROJET_LARAVEL.md §6. AuthController::login émet un token Sanctum
| opaque consommé par flutter_secure_storage côté mobile.
|
*/

Route::prefix('v1/auth')->group(function () {
    // Route::post('/login', [AuthController::class, 'login']);
    // Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});
