import * as Dialog from '@radix-ui/react-dialog';
import { X } from 'lucide-react';

interface DeleteConversationDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
}

export function DeleteConversationDialog({
    open,
    onOpenChange,
    onConfirm,
}: DeleteConversationDialogProps) {
    return (
        <Dialog.Root open={open} onOpenChange={onOpenChange}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 z-50 bg-black/50" />

                <Dialog.Content className="fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-lg border bg-background p-6 shadow-lg">
                    <Dialog.Title className="text-lg font-semibold">
                        Delete this conversation?
                    </Dialog.Title>

                    <Dialog.Description className="mt-2 text-sm text-muted-foreground">
                        This will permanently delete the conversation and
                        all of its messages and mistakes. This action cannot
                        be undone.
                    </Dialog.Description>

                    <div className="mt-6 flex justify-end gap-2">
                        <Dialog.Close asChild>
                            <button
                                type="button"
                                className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                            >
                                Cancel
                            </button>
                        </Dialog.Close>

                        <button
                            type="button"
                            onClick={onConfirm}
                            className="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:opacity-90"
                        >
                            Delete
                        </button>
                    </div>

                    <Dialog.Close asChild>
                        <button
                            type="button"
                            className="absolute right-4 top-4 rounded-sm text-muted-foreground hover:text-foreground"
                            aria-label="Close"
                        >
                            <X className="size-4" />
                        </button>
                    </Dialog.Close>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}