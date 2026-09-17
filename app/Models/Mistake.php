<?php

namespace App\Models;

use App\Enums\MistakeSeverity;
use App\Enums\MistakeSubtype;
use App\Enums\MistakeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mistake extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'message_id',
        'type',
        'subtype',
        'original_text',
        'corrected_text',
        'start_position',
        'end_position',
        'explanation',
        'severity',
    ];

    protected function casts(): array
    {
        return [
            'type' => MistakeType::class,
            'subtype' => MistakeSubtype::class,
            'severity' => MistakeSeverity::class,
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}