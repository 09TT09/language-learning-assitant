<?php

namespace App\Enums;

enum MistakeType: string
{
    case GRAMMAR = 'grammar';
    case VOCABULARY = 'vocabulary';
    case SPELLING = 'spelling';
    case WORD_ORDER = 'word_order';
}
