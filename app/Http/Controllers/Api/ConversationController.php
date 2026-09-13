<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Conversation;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conversations = $request->user()
            ->conversations()
            ->latest()
            ->get();
    
        return response()->json($conversations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language' => [
                'sometimes',
                'string',
                'in:es',
            ],
            'level' => [
                'sometimes',
                'string',
                'in:A1,A2,B1,B2,C1,C2',
            ],
        ]);

        $conversation = $request->user()->conversations()->create([
            'language' => $validated['language'] ?? 'es',
            'level' => $validated['level'] ?? 'A1',
        ]);

        return response()->json($conversation, 201);
    }

    public function show(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        $this->authorize('view', $conversation);

        $conversation->load(['messages.mistakes']);

        $conversation->messages->each(function ($message) {
            $message->setAttribute(
                'corrected_sentence',
                $message->corrected_content
            );
        });

        return response()->json($conversation);
    }
}
