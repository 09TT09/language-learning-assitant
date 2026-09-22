<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ConversationMessageController;
use App\Http\Controllers\Api\MistakeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TopicController;

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

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/topics', [TopicController::class, 'index']);

    Route::get('/topics/{slug}', [TopicController::class, 'show']);

    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    );
});


use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Facades\Ai;

Route::get('/test-groq', function () {
    $response = Ai::text()
        ->prompt('Say "Hola, Groq!" in Spanish.')
        ->provider(Lab::Groq)
        ->model('openai/gpt-oss-120b');

    return response()->json([
        'text' => $response->text,
    ]);
});