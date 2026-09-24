<?php

namespace App\Models;

use App\Enums\ConversationStepStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id',
    'topic_step_id',
    'status',
    'completed_at',
])]
class ConversationStep extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ConversationStepStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function topicStep(): BelongsTo
    {
        return $this->belongsTo(TopicStep::class);
    }
}