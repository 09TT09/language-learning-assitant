import { BookOpenText } from 'lucide-react';

import { ConversationStep } from '@/types';

type ScenarioNarratorProps = {
    step: ConversationStep;
};

export default function ScenarioNarrator({
    step,
}: ScenarioNarratorProps) {
    return (
        <div className="my-6 rounded-lg border bg-muted/40 p-4">
            <div className="mb-2 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <BookOpenText className="size-4" />
                <span>{step.title}</span>
            </div>

            <p className="text-sm leading-relaxed">
                {step.narrator}
            </p>
        </div>
    );
}