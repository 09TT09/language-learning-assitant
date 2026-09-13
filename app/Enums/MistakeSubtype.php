<?php

namespace App\Enums;

enum MistakeSubtype: string
{
    // Grammar
    case VERB_CONJUGATION = 'verb_conjugation';
    case VERB_TENSE = 'verb_tense';
    case PREPOSITION = 'preposition';
    case ARTICLE = 'article';
    case GENDER_AGREEMENT = 'gender_agreement';
    case NUMBER_AGREEMENT = 'number_agreement';
    case PRONOUN = 'pronoun';

    // Vocabulary
    case WRONG_WORD = 'wrong_word';
    case FALSE_FRIEND = 'false_friend';

    // Spelling
    case TYPO = 'typo';
    case ACCENT = 'accent';

    // Other
    case OTHER = 'other';
}