<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\TopicCharacter;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'topic_id',
    'position',
    'title',
    'narrator',
    'objective',
])]
class TopicStep extends Model
{
    use HasFactory;

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function conversationSteps(): HasMany
    {
        return $this->hasMany(ConversationStep::class);
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(
            TopicCharacter::class,
            'topic_step_character'
        );
    }

    public function nextStep(): ?TopicStep
    {
        return static::query()
            ->where('topic_id', $this->topic_id)
            ->where('position', '>', $this->position)
            ->orderBy('position')
            ->first();
    }
}