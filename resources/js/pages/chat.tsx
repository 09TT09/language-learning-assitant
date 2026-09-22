import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import AlertError from '@/components/alert-error';
import { ChatComposer } from '@/components/chat/chat-composer';
import { ChatStart } from '@/components/chat/chat-start';
import { ChatThread } from '@/components/chat/chat-thread';
import { Button } from '@/components/ui/button';
import ScenarioNarrator from '@/components/scenario-narrator';

import {
    ApiError,
    createConversation,
    getConversation,
    sendConversationMessage,
} from '@/lib/conversation-api';

import { chat } from '@/routes';

import type {
    ChatMessage,
    Conversation,
    ConversationLevel,
    ConversationStep,
} from '@/types/conversation';

export default function Chat() {
    const [level, setLevel] = useState<ConversationLevel>('A1');
    const [conversation, setConversation] = useState<Conversation | null>(null);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [starting, setStarting] = useState(false);
    const [loadingConversation, setLoadingConversation] = useState(false);
    const [sending, setSending] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [currentStep, setCurrentStep] =
    useState<ConversationStep | null>(null);
    const [failedMessage, setFailedMessage] = useState<{
        id: string;
        content: string;
    } | null>(null);
    const bottomRef = useRef<HTMLDivElement>(null);

    async function loadConversation(conversationId: number) {
        setLoadingConversation(true);
        setError(null);
    
        try {
            const loaded = await getConversation(conversationId);
    
            setConversation(loaded);
            setCurrentStep(loaded.current_step);
            setMessages(loaded.messages);
            setLevel(loaded.level);
        } catch (caught) {
            setError(
                caught instanceof Error
                    ? caught.message
                    : 'Could not load the conversation.',
            );
        } finally {
            setLoadingConversation(false);
        }
    }
    
    useEffect(() => {
        async function initializeConversation() {
            const params = new URLSearchParams(window.location.search);
            const conversationId = Number(params.get('conversation'));
    
            if (!conversationId) {
                return;
            }
    
            await loadConversation(conversationId);
        }
    
        initializeConversation();
    }, []);
    
    useEffect(() => {
        bottomRef.current?.scrollIntoView({
            behavior: 'smooth',
        });
    }, [messages, sending]);

    async function startConversation() {
        setError(null);
        setStarting(true);

        try {
            const created = await createConversation(level);

            setConversation(created);
            setCurrentStep(created.current_step);
            setMessages([]);

            window.dispatchEvent(
                new CustomEvent('conversation-created', {
                    detail: created,
                }),
            );

            const url = new URL(window.location.href);
            url.searchParams.set('conversation', String(created.id));

            window.history.replaceState({}, '', url);
        } catch (caught) {
            setError(
                caught instanceof Error
                    ? caught.message
                    : 'Could not start the conversation.',
            );
        } finally {
            setStarting(false);
        }
    }

    function resetConversation() {
        setConversation(null);
        setMessages([]);
        setError(null);

        const url = new URL(window.location.href);
        url.searchParams.delete('conversation');

        window.history.replaceState({}, '', url);
    }

    async function sendMessage(content: string) {
        if (!conversation) {
            return;
        }
    
        const userMessage: ChatMessage = {
            id: `user-${Date.now()}`,
            role: 'user',
            content,
            created_at: new Date().toISOString(),
            mistakes: [],
        };
    
        setMessages((current) => [...current, userMessage]);
        setSending(true);
        setError(null);
    
        try {
            const result = await sendConversationMessage(
                conversation.id,
                content,
            );
    
            const updatedConversation = await getConversation(conversation.id);

            setConversation(updatedConversation);
            setCurrentStep(updatedConversation.current_step);

            window.dispatchEvent(
                new CustomEvent('conversation-updated', {
                    detail: updatedConversation,
                }),
            );
    
            setMessages((current) => [
                ...current.map((message) =>
                    message.id === userMessage.id
                        ? {
                              ...message,
                              mistakes: result.mistakes,
                              corrected_sentence:
                                  result.corrected_sentence,
                          }
                        : message,
                ),
                {
                    id: result.message.id,
                    role: 'assistant',
                    content: result.message.content,
                    created_at: result.message.created_at,
                },
            ]);
        } catch (caught) {
            // keep your existing catch block
        } finally {
            setSending(false);
        }
    }

    async function retryMessage() {
        if (!conversation || !failedMessage) {
            return;
        }
    
        const isFirstMessage = conversation.title === null;
    
        setSending(true);
        setError(null);
    
        try {
            const result = await sendConversationMessage(
                conversation.id,
                failedMessage.content,
            );
    
            // The first attempt failed, so the title was not generated.
            // If the retry succeeds, fetch the newly generated title.
            if (isFirstMessage) {
                const updatedConversation = await getConversation(
                    conversation.id,
                );
    
                setConversation(updatedConversation);
                setCurrentStep(updatedConversation.current_step);
    
                window.dispatchEvent(
                    new CustomEvent('conversation-updated', {
                        detail: updatedConversation,
                    }),
                );
            }
    
            setMessages((current) => [
                ...current.map((message) =>
                    message.id === failedMessage.id
                        ? {
                              ...message,
                              mistakes: result.mistakes,
                              corrected_sentence:
                                  result.corrected_sentence,
                          }
                        : message,
                ),
                {
                    id: result.message.id,
                    role: 'assistant',
                    content: result.message.content,
                    created_at: result.message.created_at,
                },
            ]);
    
            setFailedMessage(null);
        } catch (caught) {
            setError(
                caught instanceof Error
                    ? caught.message
                    : 'Could not send your message.',
            );
        } finally {
            setSending(false);
        }
    }

    if (loadingConversation) {
        return (
            <>
                <Head title="Practice" />

                <div className="flex h-full min-h-[calc(100vh-6rem)] flex-1 items-center justify-center p-4">
                    <p className="text-sm text-muted-foreground">
                        Loading conversation...
                    </p>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Practice" />

            <div className="flex h-full min-h-[calc(100vh-6rem)] flex-1 flex-col gap-4 p-4">
                <main className="flex min-w-0 flex-1 flex-col gap-4">
                    {conversation && (
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h1 className="text-lg font-semibold">
                                    Practice
                                </h1>

                                <p className="text-sm text-muted-foreground">
                                    Spanish · {conversation.level}
                                </p>
                            </div>

                            <Button
                                variant="outline"
                                onClick={resetConversation}
                            >
                                New conversation
                            </Button>
                        </div>
                    )}

                    {error && (
                        <div className="space-y-2">
                            <AlertError errors={[error]} />

                            {failedMessage && (
                                <Button
                                    variant="outline"
                                    onClick={retryMessage}
                                    disabled={sending}
                                >
                                    Retry
                                </Button>
                            )}
                        </div>
                    )}

                    {conversation ? (
                        <>
                            <ChatThread
                                messages={messages}
                                scenarioSteps={conversation?.scenario_steps ?? []}
                                waiting={sending}
                            />

                            <div ref={bottomRef} />

                            <ChatComposer
                                disabled={sending}
                                sending={sending}
                                onSend={sendMessage}
                            />
                        </>
                    ) : (
                        <ChatStart
                            level={level}
                            starting={starting}
                            onLevelChange={setLevel}
                            onStart={startConversation}
                        />
                    )}
                </main>
            </div>
        </>
    );
}

Chat.layout = {
    breadcrumbs: [
        {
            title: 'Practice',
            href: chat(),
        },
    ],
};