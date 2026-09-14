<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ConversationMessageController;
use App\Http\Controllers\Api\MistakeController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get(
        '/conversations',
        [ConversationController::class, 'index']
    );

    Route::post(
        '/conversations',
        [ConversationController::class, 'store']
    );

    Route::get(
        '/conversations/{conversation}',
        [ConversationController::class, 'show']
    );

    Route::post(
        '/conversations/{conversation}/messages',
        [ConversationMessageController::class, 'store']
    )->middleware('throttle:ai-chat');

    Route::delete(
        '/conversations/{conversation}',
        [ConversationController::class, 'destroy']
    );
    
    Route::get(
        '/mistakes',
        [MistakeController::class, 'index']
    );

    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    );
});