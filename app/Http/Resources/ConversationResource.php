<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'topic_id' => $this->topic_id,
            'title' => $this->title,
            'language' => $this->language,
            'level' => $this->level,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'scenario_steps' => ConversationStepResource::collection(
                $this->whenLoaded('steps')
            ),
        ];
    }
}