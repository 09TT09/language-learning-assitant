<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\SpanishTutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $result = $tutor->sendMessage(
            $conversation,
            $validated['content']
        );

        return response()->json($result);
    }
}