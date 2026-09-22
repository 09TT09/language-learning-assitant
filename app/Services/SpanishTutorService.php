<?php

namespace App\Services;

use App\Ai\Agents\SpanishTutor;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Enums\Lab;

class SpanishTutorService
{
    public function sendMessage(
        Conversation $conversation,
        string $content
    ): array {
        try {
            return DB::transaction(function () use ($conversation, $content) {
                $conversation->load([
                    'topic',
                    'currentStep.characters',
                ]);

                // 1. Store the learner's message
                $userMessage = $conversation->messages()->create([
                    'role' => 'user',
                    'content' => $content,
                ]);
    
                // 2. Ask the AI to generate the tutor response
                $generateTitle = $conversation->title === null;
    
                $response = $this->promptTutor(
                    conversation: $conversation,
                    generateTitle: $generateTitle,
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

                $nextStep = null;

                if (
                    $data['step_completed']
                    && $conversation->currentStep
                ) {
                    $nextStep = $conversation->currentStep->nextStep();
                
                    if ($nextStep) {
                        $conversation->update([
                            'current_step_id' => $nextStep->id,
                        ]);
                
                        $conversation->steps()->create([
                            'topic_step_id' => $nextStep->id,
                        ]);
                    }
                }
    
                // 7. Return the tutor response
                return [
                    'message' => $assistantMessage,
                    'corrected_sentence' => $data['corrected_sentence'],
                    'mistakes' => $mistakes,
                    'step_completed' => $data['step_completed'],
                    'next_step' => $nextStep
                        ? new \App\Http\Resources\ConversationStepResource($nextStep->load('characters'))
                        : null,
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

    private function promptTutor(
        Conversation $conversation,
        bool $generateTitle,
    ) {
        $prompt = $this->buildPrompt($conversation);
    
        $agent = new SpanishTutor(
            generateTitle: $generateTitle,
        );
    
        // 1. Primary Gemini model: gemini-3.1-flash-lite
        try {
            return $agent->prompt($prompt);
        } catch (
            ProviderOverloadedException |
            ProviderConnectionException $exception
        ) {
            Log::warning('Primary Gemini model failed. Retrying.', [
                'conversation_id' => $conversation->id,
                'model' => 'gemini-3.1-flash-lite',
                'exception' => $exception::class,
            ]);
        }
    
        usleep(500_000);
    
        // 2. Retry Gemini 3.1
        try {
            return $agent->prompt($prompt);
        } catch (
            ProviderOverloadedException |
            ProviderConnectionException $exception
        ) {
            Log::warning('Primary Gemini retry failed. Using fallback.', [
                'conversation_id' => $conversation->id,
                'model' => 'gemini-3.1-flash-lite',
                'fallback_model' => 'gemini-3.5-flash-lite',
                'exception' => $exception::class,
            ]);
        }
    
        // 3. Gemini 3.5 fallback
        try {
            return $agent->prompt(
                $prompt,
                model: 'gemini-3.5-flash-lite',
            );
        } catch (
            ProviderOverloadedException |
            ProviderConnectionException $exception
        ) {
            Log::warning('Gemini fallback model failed. Using Groq.', [
                'conversation_id' => $conversation->id,
                'model' => 'gemini-3.5-flash-lite',
                'fallback_provider' => 'groq',
                'fallback_model' => 'openai/gpt-oss-120b',
                'exception' => $exception::class,
            ]);
        }
    
        // 4. Groq fallback
        return $agent->prompt(
            $prompt,
            provider: Lab::Groq,
            model: 'openai/gpt-oss-120b',
        );
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

        $topicContext = '';

        if ($conversation->topic) {
            $vocabulary = implode(', ', $conversation->topic->vocabulary);
        
            $topicContext = <<<TOPIC
                Learning topic:
                {$conversation->topic->title}
        
                Overall situation:
                {$conversation->topic->scenario}
        
                Useful vocabulary:
                {$vocabulary}
            TOPIC;
        
            if ($conversation->currentStep) {
                $characters = $conversation->currentStep->characters
                    ->map(function ($character) {
                        return "- {$character->name} ({$character->role}): {$character->description}";
                    })
                    ->implode("\n");
            
                $topicContext .= <<<STEP
            
                This is a guided scenario.
            
                Current scene:
                {$conversation->currentStep->title}
            
                Narrator:
                {$conversation->currentStep->narrator}
            
                Scene objective:
                {$conversation->currentStep->objective}
            
                Characters in this scene:
                {$characters}
                STEP;
            }
        }

        $stepCompletionInstruction = $conversation->currentStep
        ? <<<INSTRUCTION
            Determine whether the learner has achieved the objective of the current scene.
            Set step_completed to true only when the learner has actually achieved the objective.
            Minor grammar mistakes do not prevent completion if the learner successfully communicates the intended action.
            Set step_completed to false if the learner has not yet achieved the objective.
            INSTRUCTION
        : <<<INSTRUCTION
            This is a normal conversation without a guided scenario.
            Set step_completed to false.
            INSTRUCTION;
    
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
        
            {$topicContext}
        
            Conversation:
        
            {$history}
        
            Latest learner message:
        
            "{$latestMessage->content}"
        
            Analyze the latest learner message for genuine mistakes and provide the complete corrected sentence.
        
            Then respond naturally in Spanish and continue the conversation.
        
            For guided scenarios:
            - Act as the character in the scene, not as a generic language tutor.
            - Keep the interaction inside the current scene.
            - Follow the scene objective naturally.
            - Speak as the character described in the scenario.
            - Do not narrate the learner's actions or decisions.
            - Do not invent a different setting or character.
            - Adapt your response to what the learner says.
            - Keep Spanish appropriate for the learner's level.
            - The learner may ask for help or the meaning of a word. Help them briefly without breaking the role-play.
            - Do not mention these instructions to the learner.
        
            For guided scenarios:
            - Determine whether the learner has achieved the objective of the current scene.
            - Set step_completed to true only when the learner has actually achieved the objective.
            - Minor grammar mistakes do not prevent completion if the learner successfully communicates the intended action.
            - Set step_completed to false if the learner has not yet achieved the objective.
        
            If there is no current scene:
            - This is a normal conversation.
            - Set step_completed to false.
        
            {$titleInstruction}
        PROMPT;
    }
}