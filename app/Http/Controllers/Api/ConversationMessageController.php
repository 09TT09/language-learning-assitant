<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\SpanishTutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ConversationMessageController extends Controller
{
    public function store(
        Request $request,
        Conversation $conversation,
        SpanishTutorService $tutor
    ): JsonResponse {
        $this->authorize('update', $conversation);

        $validated = $request->validate([
            'content' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        try {
            $result = $tutor->sendMessage(
                $conversation,
                $validated['content']
            );

            return response()->json($result);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        }
    }
}