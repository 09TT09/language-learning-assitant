<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(
            TopicCharacter::class,
            'topic_step_character'
        );
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            TopicStep::class,
            'topic_step_dependencies',
            'topic_step_id',
            'depends_on_topic_step_id'
        );
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            TopicStep::class,
            'topic_step_dependencies',
            'depends_on_topic_step_id',
            'topic_step_id'
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

    public function conversationSteps(): HasMany
    {
        return $this->hasMany(ConversationStep::class);
    }
}