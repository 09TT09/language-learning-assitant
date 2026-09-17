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
            'start_position' => $this->start_position,
            'end_position' => $this->end_position,
            'explanation' => $this->explanation,
            'severity' => $this->severity->value,
            'conversation_id' => $this->conversation_id,
            'message_id' => $this->message_id,
            'created_at' => $this->created_at,
            'sentence' => $this->message->content,
        
            'message_mistakes' => $this->message->mistakes
                ->map(fn ($mistake) => [
                    'id' => $mistake->id,
                    'type' => $mistake->type->value,
                    'subtype' => $mistake->subtype->value,
                    'original_text' => $mistake->original_text,
                    'corrected_text' => $mistake->corrected_text,
                    'start_position' => $mistake->start_position,
                    'end_position' => $mistake->end_position,
                    'explanation' => $mistake->explanation,
                    'severity' => $mistake->severity->value,
                ])
                ->values(),
        ];
    }
}