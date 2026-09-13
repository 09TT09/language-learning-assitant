import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { ConversationLevel } from '@/types/conversation';

const LEVELS: ConversationLevel[] = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

export function ChatStart({
    level,
    starting,
    onLevelChange,
    onStart,
}: {
    level: ConversationLevel;
    starting: boolean;
    onLevelChange: (level: ConversationLevel) => void;
    onStart: () => void;
}) {
    return (
        <div className="flex flex-1 flex-col items-center justify-center gap-6 px-4 text-center">
            <div className="space-y-2">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Practice Spanish
                </h1>
                <p className="max-w-md text-sm text-muted-foreground">
                    Start a conversation with the tutor. You will get a reply in
                    Spanish, plus corrections when you make a mistake.
                </p>
            </div>

            <div className="grid w-full max-w-xs gap-2 text-left">
                <Label htmlFor="level">Your level</Label>
                <Select
                    value={level}
                    onValueChange={(value) =>
                        onLevelChange(value as ConversationLevel)
                    }
                    disabled={starting}
                >
                    <SelectTrigger id="level" className="w-full">
                        <SelectValue placeholder="Choose a level" />
                    </SelectTrigger>
                    <SelectContent>
                        {LEVELS.map((item) => (
                            <SelectItem key={item} value={item}>
                                {item}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <Button onClick={onStart} disabled={starting}>
                {starting ? <Spinner /> : null}
                Start conversation
            </Button>
        </div>
    );
}
