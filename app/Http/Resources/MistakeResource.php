<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MistakeResource extends JsonResource
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
            'type' => $this->type->value,
            'subtype' => $this->subtype->value,
            'original_text' => $this->original_text,
            'corrected_text' => $this->corrected_text,
            'explanation' => $this->explanation,
            'severity' => $this->severity->value,
            'conversation_id' => $this->conversation_id,
            'message_id' => $this->message_id,
            'sentence' => $this->message->content,
            'created_at' => $this->created_at,
        ];
    }
}