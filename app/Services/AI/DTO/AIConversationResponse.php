<?php

namespace App\Services\AI\DTO;

class AIConversationResponse
{
    public function __construct(
        public readonly string $reply,
        public readonly array $mistakes,
    ) {}
}
