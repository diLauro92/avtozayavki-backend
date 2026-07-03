<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\AuthController;

Route::middleware('auth:sanctum')->group(function () {
    // всё внутри требует залогиненности
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/requests', [RequestController::class, 'index']);
    Route::get('/requests/{request}', [RequestController::class, 'show']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::patch('/requests/{requestModel}/status', [RequestController::class, 'updateStatus']);

    Route::post('/logout', [AuthController::class, 'logout']);
});

// вне группы — только login
Route::post('/login', [AuthController::class, 'login']);
