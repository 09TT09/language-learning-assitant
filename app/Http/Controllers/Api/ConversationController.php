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
use App\Enums\ConversationStepStatus;

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
    
        $conversation = $request->user()->conversations()->create([
            'topic_id' => $topic?->id,
            'language' => $validated['language'] ?? 'es',
            'level' => $topic?->level ?? ($validated['level'] ?? ConversationLevel::A1),
        ]);
    
        if ($topic) {
            $topicSteps = $topic->steps()
                ->orderBy('position')
                ->get();
    
            foreach ($topicSteps as $step) {
                $conversation->steps()->create([
                    'topic_step_id' => $step->id,
                    'status' => $step->dependencies()->exists()
                        ? ConversationStepStatus::LOCKED
                        : ConversationStepStatus::ACTIVE,
                ]);
            }
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
                        'status' => $conversationStep->status->value,
                        'completed_at' => $conversationStep->completed_at,
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
