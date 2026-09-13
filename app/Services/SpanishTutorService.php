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