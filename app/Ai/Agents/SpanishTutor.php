<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Gemini)]
//#[Model('gemini-3.5-flash-lite')]
#[Model('gemini-3.1-flash-lite')]
class SpanishTutor implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
            You are a Spanish tutor for an A1 learner.

            Respond naturally in Spanish using simple A1 language.

            Analyze ONLY the learner's latest message.

            Detect all genuine errors:
            - grammar
            - vocabulary
            - spelling
            - word order
            - relevant punctuation

            Do not report stylistic preferences or valid alternatives.

            For each error, provide:
            - type
            - subtype
            - original_text
            - corrected_text
            - explanation
            - severity

            corrected_sentence must be the complete corrected latest message.
            Apply all necessary corrections.
            If there are no errors, keep corrected_sentence identical to the original.

            Do not analyze previous learner messages for errors.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'reply' => $schema
                ->string()
                ->required(),

            'corrected_sentence' => $schema
                ->string()
                ->required(),

            'mistakes' => $schema
                ->array()
                ->items(
                    $schema->object(fn ($schema) => [
                        'type' => $schema
                            ->string()
                            ->enum([
                                'grammar',
                                'vocabulary',
                                'spelling',
                                'word_order',
                            ])
                            ->required(),

                        'subtype' => $schema
                            ->string()
                            ->enum([
                                'verb_conjugation',
                                'verb_tense',
                                'preposition',
                                'article',
                                'gender_agreement',
                                'number_agreement',
                                'pronoun',
                                'wrong_word',
                                'false_friend',
                                'typo',
                                'accent',
                                'other',
                            ])
                            ->required(),

                        'original_text' => $schema
                            ->string()
                            ->required(),

                        'corrected_text' => $schema
                            ->string()
                            ->required(),

                        'explanation' => $schema
                            ->string()
                            ->required(),

                        'severity' => $schema
                            ->string()
                            ->enum([
                                'low',
                                'medium',
                                'high',
                            ])
                            ->required(),
                    ])
                )
                ->required(),
        ];
    }
}