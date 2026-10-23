<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BookController;

Route::prefix('api/v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/health', [AuthController::class, 'health']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index']);

        Route::get('/books', [BookController::class, 'index']);
        Route::get('/books/{id}', [BookController::class, 'show']);
    });
});
