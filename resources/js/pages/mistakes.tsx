import { Head } from '@inertiajs/react';

export default function Mistakes() {
    return (
        <>
            <Head title="Mistakes" />

            <div className="flex h-full min-h-[calc(100vh-6rem)] flex-1 flex-col p-4">
                <main className="flex flex-1 flex-col">
                    <h1 className="text-lg font-semibold">
                        Mistakes
                    </h1>
                </main>
            </div>
        </>
    );
}