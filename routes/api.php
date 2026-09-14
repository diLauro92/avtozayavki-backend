<?php

use App\Http\Controllers\Api\TelegramLinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\AuthController;
use SergiX44\Nutgram\Nutgram;

Route::middleware('auth:sanctum')->group(function () {
    // всё внутри требует залогиненности
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/requests', [RequestController::class, 'index']);
    Route::get('/requests/{request}', [RequestController::class, 'show']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::patch('/requests/{requestModel}/status', [RequestController::class, 'updateStatus']);
    Route::post('/requests/{requestModel}/comments', [RequestController::class, 'storeComment']);
    Route::patch('/requests/{requestModel}/next-contact', [RequestController::class, 'updateNextContact']);
    Route::post('/telegram/link', [TelegramLinkController::class, 'store']);
    Route::delete('/telegram/link', [TelegramLinkController::class, 'destroy']);

    Route::post('/logout', [AuthController::class, 'logout']);
});

// вне группы — только login
Route::post('/login', [AuthController::class, 'login']);
Route::post('/webhook', fn (Nutgram $bot) => $bot->run());
