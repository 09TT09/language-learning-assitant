<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'topic_id',
    'name',
    'role',
    'description',
])]
class TopicCharacter extends Model
{
    use HasFactory;

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function steps(): BelongsToMany
    {
        return $this->belongsToMany(
            TopicStep::class,
            'topic_step_character'
        );
    }
}