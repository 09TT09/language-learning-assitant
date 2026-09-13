import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { chat, dashboard } from '@/routes';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex max-w-xl flex-col gap-3 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Ready to practice?
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Open a Spanish conversation with the tutor. You will get
                        a reply and corrections when you make a mistake.
                    </p>
                    <Button asChild className="w-fit">
                        <Link href={chat()}>Start practicing</Link>
                    </Button>
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
