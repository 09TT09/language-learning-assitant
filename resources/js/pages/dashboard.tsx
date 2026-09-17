import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AlertError from '@/components/alert-error';
import { Button } from '@/components/ui/button';
import {
    getConversations,
    getDashboardStats,
} from '@/lib/conversation-api';
import { chat, dashboard } from '@/routes';
import type { Conversation } from '@/types/conversation';



export default function Dashboard() {
    const [conversations, setConversations] = useState<Conversation[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [stats, setStats] = useState<{
        conversations_count: number;
        mistakes_count: number;
        messages_count: number;
    } | null>(null);
    const [loadingStats, setLoadingStats] = useState(true);

    useEffect(() => {
        async function loadStats() {
            try {
                const result = await getDashboardStats();
    
                setStats(result);
            } catch {
                // Progress is supplementary, so don't block the dashboard.
            } finally {
                setLoadingStats(false);
            }
        }
    
        loadStats();
    }, []);

    useEffect(() => {
        async function loadConversations() {
            try {
                const result = await getConversations();

                setConversations(result.slice(0, 3));
            } catch (caught) {
                setError(
                    caught instanceof Error
                        ? caught.message
                        : 'Could not load your conversations.',
                );
            } finally {
                setLoading(false);
            }
        }

        loadConversations();
    }, []);

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex max-w-xl flex-col gap-3 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <div>
                        <p className="text-sm font-medium text-muted-foreground">
                            Spanish
                        </p>

                        <p className="text-sm text-muted-foreground">
                            A1 · Beginner
                        </p>
                    </div>

                    <h1 className="text-xl font-semibold tracking-tight">
                        Ready to practice?
                    </h1>

                    <p className="text-sm text-muted-foreground">
                        Choose a situation and start speaking Spanish. You'll
                        get corrections when you make a mistake.
                    </p>

                    <Button asChild className="w-fit">
                        <Link href={chat()}>Start practicing</Link>
                    </Button>
                </div>

                <div className="max-w-xl">
                    <h2 className="mb-3 text-lg font-semibold">
                        Recent conversations
                    </h2>

                    {loading && (
                        <p className="text-sm text-muted-foreground">
                            Loading conversations...
                        </p>
                    )}

                    {error && <AlertError errors={[error]} />}
                    {!loading && !error && conversations.length === 0 && (
                        <div className="rounded-xl border border-dashed p-6">
                            <p className="text-sm text-muted-foreground">
                                You haven't started any conversations yet.
                            </p>
                        </div>
                    )}

                    {!loading && !error && conversations.length > 0 && (
                        <div className="space-y-2">
                            {conversations.map((conversation) => (
                                <Link
                                    key={conversation.id}
                                    href={`/chat?conversation=${conversation.id}`}
                                    className="block rounded-xl border p-4 transition-colors hover:bg-muted/50"
                                >
                                    <div className="flex items-center justify-between gap-4">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {conversation.title ??
                                                    'Untitled conversation'}
                                            </p>

                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Spanish · {conversation.level}
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

                <div className="max-w-xl">
                    <h2 className="mb-3 text-lg font-semibold">
                        Your progress
                    </h2>

                    {loadingStats && (
                        <p className="text-sm text-muted-foreground">
                            Loading progress...
                        </p>
                    )}

                    {!loadingStats && stats && (
                        <div className="grid grid-cols-3 gap-3">
                            <div className="rounded-xl border p-4">
                                <p className="text-2xl font-semibold">
                                    {stats.conversations_count}
                                </p>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    Conversations
                                </p>
                            </div>

                            <div className="rounded-xl border p-4">
                                <p className="text-2xl font-semibold">
                                    {stats.mistakes_count}
                                </p>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    Mistakes
                                </p>
                            </div>

                            <div className="rounded-xl border p-4">
                                <p className="text-2xl font-semibold">
                                    {stats.messages_count}
                                </p>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    Messages
                                </p>
                            </div>
                        </div>
                    )}
                </div>

            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};