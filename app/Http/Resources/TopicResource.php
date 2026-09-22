<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'level' => $this->level,
            'scenario' => $this->scenario,
            'vocabulary' => $this->vocabulary,
            'steps' => TopicStepResource::collection(
                $this->whenLoaded('steps')
            ),
        ];
    }
}