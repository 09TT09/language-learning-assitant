<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'completed_at' => $this->completed_at,

            'step' => [
                'id' => $this->topicStep->id,
                'position' => $this->topicStep->position,
                'title' => $this->topicStep->title,
                'narrator' => $this->topicStep->narrator,
                'objective' => $this->topicStep->objective,
                'characters' => TopicCharacterResource::collection(
                    $this->topicStep->characters
                ),
            ],
        ];
    }
}