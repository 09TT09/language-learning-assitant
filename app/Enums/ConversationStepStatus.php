<?php

namespace App\Enums;

enum ConversationStepStatus: string
{
    case LOCKED = 'locked';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
}