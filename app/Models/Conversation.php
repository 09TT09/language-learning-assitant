<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Topic;
use App\Models\TopicStep;
use App\Enums\ConversationStepStatus;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'topic_id',
        'current_step_id',
        'title',
        'language',
        'level',
        'scenario_state',
    ];

    protected function casts(): array
    {
        return [
            'scenario_state' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function mistakes(): HasMany
    {
        return $this->hasMany(Mistake::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ConversationStep::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(TopicStep::class, 'current_step_id');
    }

    public function updateStepProgress(): void
    {
        $steps = $this->steps()
            ->with('topicStep.dependencies')
            ->get();
    
        foreach ($steps as $conversationStep) {
            if ($conversationStep->status !== ConversationStepStatus::LOCKED) {
                continue;
            }
    
            $dependencies = $conversationStep->topicStep->dependencies;
    
            if (
                $dependencies->isNotEmpty()
                && $dependencies->every(function ($dependency) use ($steps) {
                    return $steps
                        ->firstWhere('topic_step_id', $dependency->id)
                        ?->status === ConversationStepStatus::COMPLETED;
                })
            ) {
                $conversationStep->update([
                    'status' => ConversationStepStatus::ACTIVE,
                ]);
            }
        }
    }
}
