import { Link, router } from '@inertiajs/react'
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    MessageSquare,
    MessagesSquare,
    NotebookTabs,
    Trash2,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import AppLogo from '@/components/app-logo';
import { DeleteConversationDialog } from '@/components/DeleteConversationDialog';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import {
    deleteConversation,
    getConversations,
} from '@/lib/conversation-api';

import { chat, dashboard, mistakes, topics } from '@/routes';

import type { NavItem } from '@/types';
import type { Conversation } from '@/types/conversation';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Topics',
        href: topics(),
        icon: BookOpen,
    },
    {
        title: 'Practice',
        href: chat(),
        icon: MessagesSquare,
    },
    {
        title: 'Mistakes',
        href: mistakes(),
        icon: NotebookTabs,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const [conversations, setConversations] = useState<Conversation[]>([]);
    const [conversationToDelete, setConversationToDelete] = useState<Conversation | null>(null);
    const [loading, setLoading] = useState(true);

    async function loadConversations() {
        try {
            const loaded = await getConversations();
            setConversations(loaded);
        } catch {
            setConversations([]);
        } finally {
            setLoading(false);
        }
    }

    async function handleDeleteConversation(id: number) {
        try {
            await deleteConversation(id);
    
            setConversations((current) =>
                current.filter((conversation) => conversation.id !== id),
            );
    
            const params = new URLSearchParams(window.location.search);
            const currentConversationId = params.get('conversation');
    
            if (currentConversationId === String(id)) {
                router.visit(dashboard());
            }
        } catch {
            // We will improve error handling later.
        }
    }

    useEffect(() => {
        async function initializeConversations() {
            try {
                const loaded = await getConversations();
                setConversations(loaded);
            } catch {
                setConversations([]);
            } finally {
                setLoading(false);
            }
        }
    
        initializeConversations();
    
        function handleConversationCreated() {
            loadConversations();
        }
    
        function handleConversationUpdated(event: Event) {
            const customEvent = event as CustomEvent<Conversation>;
    
            setConversations((current) =>
                current.map((conversation) =>
                    conversation.id === customEvent.detail.id
                        ? customEvent.detail
                        : conversation,
                ),
            );
        }
    
        window.addEventListener(
            'conversation-created',
            handleConversationCreated,
        );
    
        window.addEventListener(
            'conversation-updated',
            handleConversationUpdated,
        );
    
        return () => {
            window.removeEventListener(
                'conversation-created',
                handleConversationCreated,
            );
    
            window.removeEventListener(
                'conversation-updated',
                handleConversationUpdated,
            );
        };
    }, []);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="flex flex-col">
                <NavMain items={mainNavItems} />

                <div className="py-0 flex min-h-0 flex-1 flex-col">
                    <SidebarGroupLabel className="px-4">Conversations</SidebarGroupLabel>

                    {loading ? (
                        <p className="px-3 py-2 text-xs text-muted-foreground">
                            Loading...
                        </p>
                    ) : conversations.length === 0 ? (
                        <p className="px-3 py-2 text-xs text-muted-foreground">
                            No conversations yet.
                        </p>
                    ) : (
                        <SidebarMenu className="px-2 py-1 min-h-0 flex-1 overflow-y-auto border-y">
                            {conversations.map((conversation) => (
                                <SidebarMenuItem
                                    key={conversation.id}
                                    className="flex items-center"
                                >
                                    <SidebarMenuButton asChild className="flex-1">
                                        <Link
                                            href={`${chat().url}?conversation=${conversation.id}`}
                                        >
                                            <MessageSquare />
                                            <span>
                                                {conversation.title || `Spanish · ${conversation.level}`}
                                            </span>
                                        </Link>
                                    </SidebarMenuButton>

                                    <button
                                        type="button"
                                        onClick={(event) => {
                                            event.preventDefault();
                                            event.stopPropagation();
                                            setConversationToDelete(conversation);
                                        }}
                                        className="mr-1 flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-destructive"
                                        aria-label="Delete conversation"
                                    >
                                        <Trash2 className="size-4" />
                                    </button>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    )}
                </div>
            </SidebarContent>
            <SidebarFooter>
                <NavFooter
                    items={footerNavItems}
                    className="mt-auto"
                />

                <NavUser />
            </SidebarFooter>

            <DeleteConversationDialog
                open={conversationToDelete !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setConversationToDelete(null);
                    }
                }}
                onConfirm={() => {
                    if (conversationToDelete) {
                        handleDeleteConversation(conversationToDelete.id);
                    }

                    setConversationToDelete(null);
                }}
            />

        </Sidebar>
    );
}