<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::inertia('chat', 'chat')->name('chat');
    Route::inertia('mistakes', 'mistakes')->name('mistakes');
    Route::inertia('topics', 'topics')->name('topics');
    Route::inertia('topics/{slug}', 'topic')->name('topics.show');
});

require __DIR__.'/settings.php';