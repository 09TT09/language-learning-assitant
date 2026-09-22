<?php

namespace App\Models;

use App\Enums\ConversationLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Conversation;
use App\Models\TopicStep;
use App\Models\TopicCharacter;

#[Fillable([
    'title',
    'slug',
    'description',
    'level',
    'scenario',
    'vocabulary',
    'is_active',
])]
class Topic extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => ConversationLevel::class,
            'vocabulary' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TopicStep::class)
            ->orderBy('position');
    }

    public function characters(): HasMany
    {
        return $this->hasMany(TopicCharacter::class);
    }
}