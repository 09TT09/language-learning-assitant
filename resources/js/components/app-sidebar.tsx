import { Link } from '@inertiajs/react';
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    MessageSquare,
    MessagesSquare,
    NotebookTabs,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
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
import { NavUser } from '@/components/nav-user';

import { getConversations } from '@/lib/conversation-api';

import { chat, dashboard, mistakes } from '@/routes';

import type { NavItem } from '@/types';
import type { Conversation } from '@/types/conversation';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
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

    useEffect(() => {
        loadConversations();

        function handleConversationCreated() {
            loadConversations();
        }

        window.addEventListener(
            'conversation-created',
            handleConversationCreated,
        );

        return () => {
            window.removeEventListener(
                'conversation-created',
                handleConversationCreated,
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
                                <SidebarMenuItem key={conversation.id}>
                                    <SidebarMenuButton asChild>
                                        <Link href={`${chat().url}?conversation=${conversation.id}`}>
                                            <MessageSquare />
                                            <span>
                                                Spanish · {conversation.level}
                                            </span>
                                        </Link>
                                    </SidebarMenuButton>
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
        </Sidebar>
    );
}