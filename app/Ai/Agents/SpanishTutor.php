<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Gemini)]
#[Model('gemini-3.1-flash-lite')]
class SpanishTutor implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly bool $generateTitle = false,
    ) {}

    public function instructions(): Stringable|string
    {
        $instructions = <<<'PROMPT'
            You are a Spanish tutor for an A1 learner.

            Respond naturally in Spanish using simple A1 language.

            Analyze ONLY the learner's latest message.

            Detect genuine errors in:

            - grammar
            - vocabulary
            - spelling
            - word order

            Do NOT treat capitalization as a Spanish-learning mistake.

            In particular:

            - Do NOT report an error only because a sentence starts with a lowercase letter.
            - Do NOT report an error only because a proper noun is not capitalized.
            - Do NOT report capitalization differences as spelling mistakes.
            - You may still use correct capitalization in corrected_sentence.
            - The corrected_sentence should be grammatically and orthographically correct, but mistakes should represent meaningful Spanish-learning errors, not capitalization preferences.

            Do not report stylistic preferences or valid alternatives.

            Important rules for error detection:

            - Do NOT report an error when the learner's input is random, nonsensical, or meaningless.
            - Do NOT treat an unknown or unrecognizable sequence of characters as a vocabulary mistake.
            - Do NOT invent a meaning or correction for meaningless input.
            - A vocabulary mistake should only be reported when the word or expression has an identifiable meaning and is clearly incorrect or inappropriate in the context.
            - For example, "hey" can be reported as a vocabulary mistake because it is a real English word and "hola" is an appropriate Spanish equivalent.
            - If the message contains understandable foreign-language words, they may be reported as vocabulary mistakes when a clear Spanish equivalent is appropriate.
            - If the message is unclear but could reasonably have a meaning, do not invent an interpretation. Only report errors that can be identified with reasonable confidence.

            For spelling errors, focus on actual orthographic errors such as:

            - incorrect letters
            - missing letters
            - extra letters
            - incorrect accents
            - incorrect word forms

            Do not classify capitalization alone as a spelling error.

            For each genuine error, provide:

            - type
            - subtype
            - original_text
            - corrected_text
            - explanation
            - severity

            corrected_sentence must be the complete corrected latest message.

            Apply all necessary corrections to genuine errors only.

            If there are no genuine errors, keep corrected_sentence identical to the original message and return an empty mistakes array.

            Do not analyze previous learner messages for errors.
        PROMPT;

        if ($this->generateTitle) {
            $instructions .= <<<'PROMPT'

                Generate a short, natural title for this new conversation.

                The title must:
                - summarize the topic of the learner's message
                - be written in Spanish
                - be 3 to 7 words
                - NOT reproduce the learner's sentence
                - NOT contain the learner's mistakes
                - NOT mention grammar corrections or mistakes
                - remain useful as a conversation label
            PROMPT;
        }

        return $instructions;
    }

    public function schema(JsonSchema $schema): array
    {
        $fields = [
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

        if ($this->generateTitle) {
            $fields['title'] = $schema
                ->string()
                ->required();
        }

        return $fields;
    }
}