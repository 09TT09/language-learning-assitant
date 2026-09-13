import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

export function ChatComposer({
    disabled,
    sending,
    onSend,
}: {
    disabled: boolean;
    sending: boolean;
    onSend: (content: string) => void;
}) {
    const [content, setContent] = useState('');

    function submit() {
        const trimmed = content.trim();

        if (!trimmed || disabled || sending) {
            return;
        }

        onSend(trimmed);
        setContent('');
    }

    return (
        <form
            className="flex items-end gap-2 border-t pt-3"
            onSubmit={(event) => {
                event.preventDefault();
                submit();
            }}
        >
            <textarea
                value={content}
                onChange={(event) => setContent(event.target.value)}
                onKeyDown={(event) => {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        submit();
                    }
                }}
                disabled={disabled || sending}
                rows={2}
                maxLength={5000}
                placeholder="Write in Spanish…"
                aria-label="Message"
                className={cn(
                    'border-input placeholder:text-muted-foreground flex min-h-16 w-full resize-none rounded-md border bg-transparent px-3 py-2 text-base shadow-xs outline-none md:text-sm',
                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                )}
            />
            <Button type="submit" disabled={disabled || sending || !content.trim()}>
                {sending ? <Spinner /> : null}
                Send
            </Button>
        </form>
    );
}
