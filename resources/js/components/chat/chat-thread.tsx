import { BookOpenText } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

import type {
    ChatMessage,
    ConversationScenarioStep,
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

type ChatThreadProps = {
    messages: ChatMessage[];
    scenarioSteps: ConversationScenarioStep[];
    waiting: boolean;
};

type TimelineItem =
    | {
          type: 'scenario';
          id: string;
          createdAt: string;
          step: ConversationScenarioStep;
      }
    | {
          type: 'message';
          id: string;
          createdAt: string;
          message: ChatMessage;
      };

export function ChatThread({
    messages,
    scenarioSteps,
    waiting,
}: ChatThreadProps) {
    const timeline: TimelineItem[] = [
        ...scenarioSteps
            .filter((scenarioStep) => scenarioStep.status !== 'locked')
            .map((scenarioStep) => ({
                type: 'scenario' as const,
                id: `scenario-${scenarioStep.id}`,
                createdAt: scenarioStep.created_at,
                step: scenarioStep,
            })),
        ...messages.map((message) => ({
            type: 'message' as const,
            id: `message-${message.id}`,
            createdAt: message.created_at,
            message,
        })),
    ].sort(
        (a, b) =>
            new Date(a.createdAt).getTime() -
            new Date(b.createdAt).getTime(),
    );

    return (
        <div
            className="flex flex-1 flex-col gap-4 overflow-y-auto px-1 py-2"
            aria-live="polite"
        >
            {timeline.map((item) => {
                if (item.type === 'scenario') {
                    return (
                        <div
                            key={item.id}
                            className="my-2 rounded-lg border bg-muted/40 p-4"
                        >
                            <div className="mb-2 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <BookOpenText className="size-4" />
                                <span>{item.step.step.title}</span>
                            </div>

                            <p className="text-sm leading-relaxed">
                                {item.step.step.narrator}
                            </p>
                        </div>
                    );
                }

                const message = item.message;

                return (
                    <div
                        key={item.id}
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
                                                {mistake.type.replace(
                                                    '_',
                                                    ' ',
                                                )}
                                            </Badge>

                                            <Badge variant="secondary">
                                                {mistake.subtype.replace(
                                                    '_',
                                                    ' ',
                                                )}
                                            </Badge>

                                            <Badge
                                                variant={
                                                    severityVariant[
                                                        mistake.severity
                                                    ]
                                                }
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
                );
            })}

            {waiting ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Spinner />
                    Tutor is typing…
                </div>
            ) : null}
        </div>
    );
}