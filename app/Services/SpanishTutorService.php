<?php

namespace App\Services;

use App\Ai\Agents\SpanishTutor;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SpanishTutorService
{
    public function sendMessage(
        Conversation $conversation,
        string $content
    ): array {
        try {
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
                // throw new RuntimeException('Test AI failure.');
                
                $response = (new SpanishTutor)->prompt(
                    $this->buildPrompt($conversation)
                );

                // 3. Get the structured AI response
                $data = $response->structured;

                // 4. Store the corrected version of the learner's message
                $userMessage->update([
                    'corrected_content' => $data['corrected_sentence'],
                ]);

                // 5. Store the tutor's response
                $assistantMessage = $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $data['reply'],
                ]);

                // 6. Store mistakes against the learner's message
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

                // 7. Return the tutor response
                return [
                    'message' => $assistantMessage,
                    'corrected_sentence' => $data['corrected_sentence'],
                    'mistakes' => $mistakes,
                ];
            });
        } catch (Throwable $exception) {
            Log::error('Spanish tutor AI request failed.', [
                'conversation_id' => $conversation->id,
                'exception' => $exception,
            ]);

            throw new RuntimeException(
                'The Spanish tutor is temporarily unavailable. Please try again.'
            );
        }
    }

    private function buildPrompt(Conversation $conversation): string
    {
        $messages = $conversation
            ->messages()
            ->latest('created_at')
            ->take(10)
            ->get()
            ->reverse()
            ->values();

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
            Continue this Spanish learning conversation.

            Learner level: {$conversation->level}

            Conversation:

            {$history}

            Latest learner message:

            "{$latestMessage->content}"

            Analyze the latest learner message for genuine mistakes and provide the complete corrected sentence.

            Then respond naturally in Spanish and continue the conversation.
        PROMPT;
    }
}