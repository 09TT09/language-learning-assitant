<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Topic;
use Illuminate\Validation\Rule;
use App\Enums\ConversationLevel;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\ConversationStepResource;
use App\Models\ConversationStep;

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
            'topic_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('topics', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'language' => [
                'sometimes',
                'string',
                'in:es',
            ],
            'level' => [
                'sometimes',
                'string',
                Rule::enum(ConversationLevel::class),
            ],
        ]);
    
        $topic = null;
    
        if (!empty($validated['topic_id'])) {
            $topic = Topic::findOrFail($validated['topic_id']);
        }
    
        $firstStep = $topic?->steps()->first();

        $conversation = $request->user()->conversations()->create([
            'topic_id' => $topic?->id,
            'current_step_id' => $firstStep?->id,
            'language' => $validated['language'] ?? 'es',
            'level' => $topic?->level ?? ($validated['level'] ?? ConversationLevel::A1),
        ]);
        
        if ($firstStep) {
            $conversation->steps()->create([
                'topic_step_id' => $firstStep->id,
            ]);
        }
    
        return response()->json($conversation, 201);
    }

    public function show(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        $this->authorize('view', $conversation);
    
        $conversation->load([
            'messages.mistakes',
            'topic',
            'currentStep.characters',
            'steps.topicStep.characters',
        ]);
    
        $conversation->setAttribute(
            'current_step',
            $conversation->currentStep
                ? new ConversationStepResource($conversation->currentStep)
                : null
        );
    
        $conversation->setAttribute(
            'scenario_steps',
            $conversation->steps
                ->sortBy('created_at')
                ->map(function (ConversationStep $conversationStep) {
                    $step = $conversationStep->topicStep;
    
                    return [
                        'id' => $conversationStep->id,
                        'created_at' => $conversationStep->created_at,
                        'step' => [
                            'id' => $step->id,
                            'position' => $step->position,
                            'title' => $step->title,
                            'narrator' => $step->narrator,
                            'objective' => $step->objective,
                            'characters' => $step->characters
                                ->map(fn ($character) => [
                                    'id' => $character->id,
                                    'name' => $character->name,
                                    'role' => $character->role,
                                    'description' => $character->description,
                                ])
                                ->values(),
                        ],
                    ];
                })
                ->values()
        );
    
        $conversation->messages->each(function ($message) {
            $message->setAttribute(
                'corrected_sentence',
                $message->corrected_content
            );
        });
    
        return response()->json($conversation);
    }

    public function destroy(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        $this->authorize('delete', $conversation);
    
        $conversation->delete();
    
        return response()->json([
            'message' => 'Conversation deleted successfully.',
        ]);
    }
}
