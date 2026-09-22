<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'title' => $this->title,
            'narrator' => $this->narrator,
            'objective' => $this->objective,
            'characters' => TopicCharacterResource::collection(
                $this->whenLoaded('characters')
            ),
        ];
    }
}