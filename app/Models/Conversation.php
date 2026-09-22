<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Topic;
use App\Models\TopicStep;

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
    ];

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
}
