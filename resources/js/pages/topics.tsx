import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import AlertError from '@/components/alert-error';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { getTopics  } from '@/lib/conversation-api';
import type {Topic} from '@/lib/conversation-api';
import { dashboard } from '@/routes';

export default function Topics() {
    const [topics, setTopics] = useState<Topic[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        async function loadTopics() {
            try {
                setError(null);

                const data = await getTopics('A1');

                setTopics(data);
            } catch (error) {
                setError(
                    error instanceof Error
                        ? error.message
                        : 'Failed to load topics.',
                );
            } finally {
                setLoading(false);
            }
        }

        loadTopics();
    }, []);

    return (
        <>
            <Head title="Topics" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Choose a topic
                    </h1>

                    <p className="mt-1 text-sm text-muted-foreground">
                        Choose a situation and practice Spanish with your tutor.
                    </p>
                </div>

                {error && <AlertError errors={[error]} />}

                {loading && (
                    <p className="text-sm text-muted-foreground">
                        Loading topics...
                    </p>
                )}

                {!loading && !error && topics.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        No topics available.
                    </p>
                )}

                {!loading && !error && topics.length > 0 && (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {topics.map((topic) => (
                            <Link
                                key={topic.id}
                                href={`/topics/${topic.slug}`}
                                className="block"
                            >
                                <Card className="h-full transition-colors hover:bg-muted/50">
                                    <CardHeader>
                                        <CardTitle>{topic.title}</CardTitle>

                                        <CardDescription>
                                            {topic.description}
                                        </CardDescription>
                                    </CardHeader>

                                    <CardContent>
                                        <p className="text-sm font-medium">
                                            Useful words
                                        </p>

                                        <div className="mt-2 flex flex-wrap gap-2">
                                            {topic.vocabulary.map((word) => (
                                                <span
                                                    key={word}
                                                    className="rounded-md bg-muted px-2 py-1 text-xs"
                                                >
                                                    {word}
                                                </span>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

Topics.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Topics',
            href: '/topics',
        },
    ],
};