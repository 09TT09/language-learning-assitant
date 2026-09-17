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
                $generateTitle = $conversation->title === null;
    
                $response = (new SpanishTutor(
                    generateTitle: $generateTitle,
                ))->prompt(
                    $this->buildPrompt($conversation)
                );
    
                $data = $response->structured;
    
                // 3. Save the title only for a new conversation
                if ($generateTitle) {
                    $conversation->update([
                        'title' => $data['title'],
                    ]);
                }
    
                // 4. Store the corrected learner message
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
    
                $searchPosition = 0;

                foreach ($data['mistakes'] as $mistake) {
                    $startPosition = mb_stripos(
                        $content,
                        $mistake['original_text'],
                        $searchPosition,
                    );
                
                    if ($startPosition === false) {
                        continue;
                    }
                
                    $endPosition = $startPosition + mb_strlen(
                        $mistake['original_text'],
                    );
                
                    $mistakes[] = $userMessage->mistakes()->create([
                        'conversation_id' => $conversation->id,
                        'type' => $mistake['type'],
                        'subtype' => $mistake['subtype'],
                        'original_text' => $mistake['original_text'],
                        'corrected_text' => $mistake['corrected_text'],
                        'start_position' => $startPosition,
                        'end_position' => $endPosition,
                        'explanation' => $mistake['explanation'],
                        'severity' => $mistake['severity'],
                    ]);
                
                    $searchPosition = $endPosition;
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
    
        $titleInstruction = $conversation->title === null
            ? <<<TITLE
                Generate a short title for this conversation based ONLY on the learner's first message.

                The title must:
                - be derived directly from the learner's first message
                - preserve the meaning and intent of the original message
                - be written in Spanish when the message contains understandable Spanish
                - be 1 to 7 words when possible
                - be concise and suitable as a sidebar conversation label
                - NOT invent a topic or information that is not present in the message
                - NOT mention grammar, corrections, mistakes, or language learning
                - NOT use information from later messages
                - NOT reproduce a long sentence verbatim when it can be shortened naturally

                If the first message is very short, such as "Hola", "Buenos días", or "¿Cómo estás?",
                use the message itself as the title.

                If the first message is unclear, nonsensical, or appears to be random text, such as "sdihsscd",
                do NOT invent a meaning. Use the original input as the title, shortened only if necessary.
                TITLE
            : '';
    
        return <<<PROMPT
            Continue this Spanish learning conversation.

            Learner level: {$conversation->level}
    
            Conversation:
    
            {$history}
    
            Latest learner message:
    
            "{$latestMessage->content}"
    
            Analyze the latest learner message for genuine mistakes and provide the complete corrected sentence.
    
            Then respond naturally in Spanish and continue the conversation.
    
            {$titleInstruction}
        PROMPT;
    }
}