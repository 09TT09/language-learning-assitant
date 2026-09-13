<?php

namespace App\Enums;

enum MistakeSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}
