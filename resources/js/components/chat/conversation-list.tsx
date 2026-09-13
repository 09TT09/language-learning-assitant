import type { Conversation } from '@/types/conversation';

type ConversationListProps = {
    conversations: Conversation[];
    activeConversationId: number | null;
    onSelect: (conversationId: number) => void;
    onNewConversation: () => void;
};

export function ConversationList({
    conversations,
    activeConversationId,
    onSelect,
    onNewConversation,
}: ConversationListProps) {
    return (
        <aside className="flex w-full flex-col lg:w-64">
            <div className="mb-2 flex items-center justify-between">
                <h2 className="text-sm font-semibold">
                    Practice
                </h2>

                <button
                    type="button"
                    onClick={onNewConversation}
                    className="text-xs text-muted-foreground hover:text-foreground"
                >
                    + New
                </button>
            </div>

            <div className="flex flex-col gap-1">
                {conversations.length === 0 ? (
                    <p className="px-2 py-3 text-sm text-muted-foreground">
                        No conversations yet.
                    </p>
                ) : (
                    conversations.map((item) => (
                        <button
                            key={item.id}
                            type="button"
                            onClick={() => onSelect(item.id)}
                            className={`rounded-md px-3 py-2 text-left text-sm transition ${
                                activeConversationId === item.id
                                    ? 'bg-muted font-medium'
                                    : 'hover:bg-muted/50'
                            }`}
                        >
                            <div>
                                Spanish · {item.level}
                            </div>

                            <div className="text-xs text-muted-foreground">
                                {new Date(
                                    item.created_at,
                                ).toLocaleDateString()}
                            </div>
                        </button>
                    ))
                )}
            </div>
        </aside>
    );
}