<?php

namespace App\Http\Controllers\Api;

use App\Enums\ConversationLevel;
use App\Http\Controllers\Controller;
use App\Http\Resources\TopicResource;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class TopicController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'level' => [
                'sometimes',
                'string',
                Rule::enum(ConversationLevel::class),
            ],
        ]);

        $topics = Topic::query()
            ->where('is_active', true)
            ->when(
                isset($validated['level']),
                fn ($query) => $query->where('level', $validated['level'])
            )
            ->orderBy('title')
            ->get();

        return TopicResource::collection($topics);
    }

    public function show(string $slug): TopicResource
    {
        $topic = Topic::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('steps.characters')
            ->firstOrFail();
    
        return new TopicResource($topic);
    }
}