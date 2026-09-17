<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'conversations_count' => $user->conversations()->count(),
            'mistakes_count' => $user->mistakes()->count(),
            'messages_count' => $user->messages()->count(),
        ]);
    }
}