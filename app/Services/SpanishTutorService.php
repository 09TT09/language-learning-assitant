<?php

namespace App\Services;

use App\Ai\Agents\SpanishTutor;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpanishTutorService
{
    public function sendMessage(
        Conversation $conversation,
        string $content
    ): array {
        return DB::transaction(function () use ($conversation, $content) {

            // 1. Store the learner's message
            $userMessage = $conversation->messages()->create([
                'role' => 'user',
                'content' => $content,
            ]);

            // 2. Ask the AI to generate the tutor response
            $response = (new SpanishTutor)->prompt(
                $this->buildPrompt($conversation)
            );

            // 3. Get the structured AI response
            $data = $response->structured;

            // 4.1. Store the corrected version of the learner's message
            $userMessage->update([
                'corrected_content' => $data['corrected_sentence'],
            ]);

            // 4.2. Store the tutor's response
            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $data['reply'],
            ]);

            // 5. Store mistakes against the learner's message
            $mistakes = [];

            foreach ($data['mistakes'] as $mistake) {
                $mistakes[] = $userMessage->mistakes()->create([
                    'conversation_id' => $conversation->id,
                    'type' => $mistake['type'],
                    'subtype' => $mistake['subtype'],
                    'original_text' => $mistake['original_text'],
                    'corrected_text' => $mistake['corrected_text'],
                    'explanation' => $mistake['explanation'],
                    'severity' => $mistake['severity'],
                ]);
            }

            // 6. Return the tutor response, corrected sentence and mistakes
            return [
                'message' => $assistantMessage,
                'corrected_sentence' => $data['corrected_sentence'],
                'mistakes' => $mistakes,
            ];
        });
    }
    
    private function buildPrompt(Conversation $conversation): string
    {
        $messages = $conversation
            ->messages()
            ->orderBy('created_at')
            ->get();

        $latestMessage = $messages->last();

        $history = $messages
            ->map(function (Message $message) {
                return match ($message->role->value) {
                    'user' => "Learner: {$message->content}",
                    'assistant' => "Tutor: {$message->content}",
                };
            })
            ->implode("\n");

        return <<<PROMPT
Continue the following Spanish learning conversation.

CONVERSATION CONTEXT

Language: {$conversation->language}

Learner level: {$conversation->level}

Conversation history:

{$history}


LATEST LEARNER MESSAGE TO ANALYZE

"{$latestMessage->content}"


ANALYSIS INSTRUCTIONS

Analyze ONLY the latest learner message.

Identify ALL genuine Spanish mistakes in the latest message.

Do not stop after finding the first mistake.

Check the complete message for:

- spelling
- grammar
- verb conjugation
- vocabulary
- gender and number agreement
- articles
- prepositions
- word order
- punctuation when relevant

For EVERY genuine mistake, create one item in the mistakes array.

Then create corrected_sentence containing the COMPLETE corrected
version of the latest learner message.

corrected_sentence MUST incorporate ALL necessary corrections.

The mistakes array and corrected_sentence MUST be consistent.

Do not report valid alternative expressions as mistakes.

Do not report stylistic preferences as mistakes.

Do not introduce unnecessary changes.

Do not analyze previous learner messages.

Do not analyze tutor messages.

If the latest learner message contains no genuine mistakes,
return an empty mistakes array and keep corrected_sentence identical
to the original learner message.

Respond naturally to the learner and continue the conversation.

PROMPT;
    }
}