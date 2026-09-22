import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import AlertError from '@/components/alert-error';
import { Button } from '@/components/ui/button';
import {
    createConversation,
    getTopic
    
} from '@/lib/conversation-api';
import type {Topic} from '@/lib/conversation-api';
import { dashboard } from '@/routes';

export default function Topic() {
    const [topic, setTopic] = useState<Topic | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [starting, setStarting] = useState(false);

    const slug = window.location.pathname.split('/').pop() ?? '';

    useEffect(() => {
        if (!slug) {
            return;
        }
    
        async function loadTopic() {
            try {
                setError(null);
    
                const data = await getTopic(slug);
    
                setTopic(data);
            } catch (error) {
                setError(
                    error instanceof Error
                        ? error.message
                        : 'Failed to load topic.',
                );
            } finally {
                setLoading(false);
            }
        }
    
        loadTopic();
    }, [slug]);

    async function handleStartPracticing() {
        if (!topic || starting) {
            return;
        }
    
        try {
            setStarting(true);
            setError(null);
    
            const conversation = await createConversation(
                topic.level,
                topic.id,
            );
    
            window.location.href = `/chat?conversation=${conversation.id}`;
        } catch (error) {
            setError(
                error instanceof Error
                    ? error.message
                    : 'Failed to start conversation.',
            );
            setStarting(false);
        }
    }

    return (
        <>
            <Head title={topic?.title ?? 'Topic'} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="max-w-2xl">
                    <Link
                        href="/topics"
                        className="text-sm text-muted-foreground hover:underline"
                    >
                        ← Back to topics
                    </Link>

                    {loading && (
                        <p className="mt-6 text-sm text-muted-foreground">
                            Loading topic...
                        </p>
                    )}

                    {error && (
                        <div className="mt-6">
                            <AlertError errors={[error]} />
                        </div>
                    )}

                    {!loading && !error && topic && (
                        <div className="mt-6 space-y-6">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    {topic.level} · Beginner
                                </p>

                                <h1 className="mt-1 text-2xl font-semibold tracking-tight">
                                    {topic.title}
                                </h1>

                                <p className="mt-2 text-muted-foreground">
                                    {topic.description}
                                </p>
                            </div>

                            <div className="rounded-xl border p-6">
                                <h2 className="font-semibold">
                                    Situation
                                </h2>

                                <p className="mt-2 text-sm text-muted-foreground">
                                    {topic.scenario}
                                </p>
                            </div>

                            <div>
                                <h2 className="font-semibold">
                                    Useful words and expressions
                                </h2>

                                <div className="mt-3 flex flex-wrap gap-2">
                                    {topic.vocabulary.map((word) => (
                                        <span
                                            key={word}
                                            className="rounded-md bg-muted px-2 py-1 text-sm"
                                        >
                                            {word}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            <Button
                                onClick={handleStartPracticing}
                                disabled={starting}
                            >
                                {starting ? 'Starting...' : 'Start practicing'}
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

Topic.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Topics',
            href: '/topics',
        },
        {
            title: 'Topic',
            href: '#',
        },
    ],
};