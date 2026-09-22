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
use Laravel\Ai\Attributes\Timeout;

#[Provider(Lab::Gemini)]
#[Model('gemini-3.1-flash-lite')]
//#[Model('gemini-3.5-flash-lite')]
#[Timeout(7)]
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
            - corrected_sentence should be grammatically and orthographically correct, but mistakes should represent meaningful Spanish-learning errors, not capitalization preferences.

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
            - start_position
            - end_position
            - explanation
            - severity

            Position rules:

            - start_position is the zero-based character index of the first character of original_text in the ORIGINAL learner message.
            - end_position is the zero-based character index immediately after the last character of original_text in the ORIGINAL learner message.
            - Positions must always refer to the ORIGINAL learner message, never to corrected_sentence.
            - original_text must exactly match the characters found between start_position and end_position in the original message.
            - Make sure start_position and end_position are accurate.
            - If the same word or expression appears multiple times, use the position of the occurrence that is actually incorrect.
            - If there are multiple mistakes, return a separate mistake for each genuine error.
            - Do not merge separate mistakes into a single mistake unless they form one inseparable grammatical error.
            - Mistake positions must not overlap unless two errors genuinely refer to the same text.
            - Do not create a mistake solely to account for a correction in corrected_sentence.

            corrected_sentence must be the complete corrected latest message.

            Apply all necessary corrections to genuine errors only.

            The corrected_sentence may contain capitalization or punctuation normalization even when capitalization or punctuation was not reported as a mistake.

            If there are no genuine errors:

            - keep corrected_sentence identical to the original message
            - return an empty mistakes array

            Do not analyze previous learner messages for errors.

            For guided scenarios:
            - Determine whether the learner has successfully achieved the current scene objective.
            - Set step_completed to true only when the learner has actually achieved the objective.
            - Do not mark the step as completed merely because the learner's Spanish is grammatically correct.
            - Minor grammar mistakes should not prevent completion if the learner successfully communicates the intended action.
            - Set step_completed to false if the learner has not yet achieved the objective.
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

            'step_completed' => $schema
                ->boolean()
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

                            'start_position' => $schema
                                ->integer()
                                ->required(),

                            'end_position' => $schema
                                ->integer()
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