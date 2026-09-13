import { Badge } from '@/components/ui/badge';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

import type {
    ChatMessage,
    MistakeSeverity,
} from '@/types/conversation';

const severityVariant: Record<
    MistakeSeverity,
    'low' | 'medium' | 'high'
> = {
    low: 'low',
    medium: 'medium',
    high: 'high',
};

export function ChatThread({
    messages,
    waiting,
}: {
    messages: ChatMessage[];
    waiting: boolean;
}) {
    return (
        <div
            className="flex flex-1 flex-col gap-4 overflow-y-auto px-1 py-2"
            aria-live="polite"
        >
            {messages.map((message) => (
                <div
                    key={message.id}
                    className={cn(
                        'flex flex-col gap-2',
                        message.role === 'user'
                            ? 'items-end'
                            : 'items-start',
                    )}
                >
                    <div
                        className={cn(
                            'max-w-[85%] rounded-2xl px-4 py-2 text-sm leading-relaxed',
                            message.role === 'user'
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-foreground',
                        )}
                    >
                        <p className="whitespace-pre-wrap">
                            {message.content}
                        </p>
                    </div>

                    {message.role === 'user' &&
                    message.mistakes?.length ? (
                        <div className="flex w-full max-w-[85%] flex-col gap-3">
                            {message.mistakes.map((mistake) => (
                                <div
                                    key={mistake.id}
                                    className="rounded-xl border bg-card p-3 text-left text-sm shadow-sm"
                                >
                                    <div className="mb-2 flex flex-wrap items-center gap-2">
                                    <Badge variant="outline">
                                        {mistake.type.replace('_', ' ')}
                                    </Badge>

                                    <Badge variant="secondary">
                                        {mistake.subtype.replace('_', ' ')}
                                    </Badge>

                                    <Badge
                                        variant={severityVariant[mistake.severity]}
                                    >
                                        {mistake.severity}
                                    </Badge>
                                    </div>

                                    <p>
                                        <span className="text-muted-foreground line-through">
                                            {mistake.original_text}
                                        </span>

                                        <span className="mx-2 text-muted-foreground">
                                            →
                                        </span>

                                        <span className="font-medium">
                                            {mistake.corrected_text}
                                        </span>
                                    </p>

                                    <p className="mt-1 text-muted-foreground">
                                        {mistake.explanation}
                                    </p>
                                </div>
                            ))}

                            {message.corrected_sentence ? (
                                <div className="rounded-xl border border-green-800 bg-[#060f00] p-4 text-left">
                                    <p className="mb-1 text-xs font-medium text-muted-foreground">
                                        Corrected sentence
                                    </p>

                                    <p className="text-sm font-medium leading-relaxed">
                                        {message.corrected_sentence}
                                    </p>
                                </div>
                            ) : null}
                        </div>
                    ) : null}
                </div>
            ))}

            {waiting ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Spinner />
                    Tutor is typing…
                </div>
            ) : null}
        </div>
    );
}