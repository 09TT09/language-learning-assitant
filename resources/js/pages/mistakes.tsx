import { useEffect, useState } from 'react';
import {
    getMistakes,
    type Mistake,
} from '@/lib/mistake-api';

const mistakeTypes = [
    { label: 'All', value: '' },
    { label: 'Grammar', value: 'grammar' },
    { label: 'Vocabulary', value: 'vocabulary' },
    { label: 'Spelling', value: 'spelling' },
    { label: 'Word order', value: 'word_order' },
];

export default function Mistakes() {
    const [mistakes, setMistakes] = useState<Mistake[]>([]);
    const [selectedType, setSelectedType] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedSeverity, setSelectedSeverity] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);

    useEffect(() => {
        const fetchMistakes = async () => {
            try {
                setLoading(true);
                setError(null);
    
                const result = await getMistakes({
                    type: selectedType || undefined,
                    severity: selectedSeverity || undefined,
                    page: currentPage,
                });
                
                setMistakes(result.data);
                setLastPage(result.meta.last_page);
            } catch (err) {
                setError(
                    err instanceof Error
                        ? err.message
                        : 'Failed to load mistakes.',
                );
            } finally {
                setLoading(false);
            }
        };
    
        fetchMistakes();
    }, [selectedType, selectedSeverity, currentPage]);

    useEffect(() => {
        setCurrentPage(1);
    }, [selectedType, selectedSeverity]);

    return (
        <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div>
                <h1 className="text-2xl font-semibold">
                    Mistakes
                </h1>

                <p className="text-muted-foreground">
                    Review the mistakes you made while practicing
                    Spanish.
                </p>
            </div>

            <div className="flex flex-wrap gap-2">
                {mistakeTypes.map((type) => (
                    <button
                        key={type.value}
                        type="button"
                        onClick={() => setSelectedType(type.value)}
                        className={`rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                            selectedType === type.value
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted hover:bg-muted/80'
                        }`}
                    >
                        {type.label}
                    </button>
                ))}
            </div>

            <div className="flex items-center gap-3">
                <label
                    htmlFor="severity"
                    className="text-sm font-medium"
                >
                    Severity:
                </label>

                <select
                    id="severity"
                    value={selectedSeverity}
                    onChange={(event) =>
                        setSelectedSeverity(event.target.value)
                    }
                    className="rounded-md border bg-background px-3 py-2 text-sm"
                >
                    <option value="">All</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>

            {loading && (
                <div className="text-muted-foreground">
                    Loading mistakes...
                </div>
            )}

            {error && (
                <div className="rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive">
                    {error}
                </div>
            )}

            {!loading && !error && mistakes.length === 0 && (
                <div className="rounded-lg border p-6 text-center">
                    <h2 className="font-medium">
                        No mistakes found
                    </h2>

                    <p className="mt-1 text-sm text-muted-foreground">
                        Try another category or continue practicing
                        Spanish.
                    </p>
                </div>
            )}

            {!loading && !error && mistakes.length > 0 && (
                <div className="space-y-4">
                    {mistakes.map((mistake) => (
                        <div
                            key={mistake.id}
                            className="rounded-lg border p-5"
                        >
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="rounded-md bg-muted px-2 py-1 text-xs font-medium capitalize">
                                    {mistake.type}
                                </span>

                                <span className="rounded-md bg-muted px-2 py-1 text-xs capitalize">
                                    {mistake.subtype.replaceAll(
                                        '_',
                                        ' ',
                                    )}
                                </span>

                                <span className="rounded-md bg-muted px-2 py-1 text-xs capitalize">
                                    {mistake.severity}
                                </span>
                            </div>

                            <div className="mt-4 text-lg">
                                <span className="text-destructive line-through">
                                    {mistake.original_text}
                                </span>

                                <span className="mx-2">
                                    →
                                </span>

                                <span className="font-medium">
                                    {mistake.corrected_text}
                                </span>
                            </div>

                            <p className="mt-3 text-sm text-muted-foreground">
                                {mistake.explanation}
                            </p>
                        </div>
                    ))}
                </div>
            )}

            {!loading && !error && lastPage > 1 && (
                <div className="flex items-center justify-center gap-4">
                    <button
                        type="button"
                        onClick={() =>
                            setCurrentPage((page) => page - 1)
                        }
                        disabled={currentPage === 1}
                        className="rounded-md border px-3 py-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Previous
                    </button>

                    <span className="text-sm text-muted-foreground">
                        Page {currentPage} of {lastPage}
                    </span>

                    <button
                        type="button"
                        onClick={() =>
                            setCurrentPage((page) => page + 1)
                        }
                        disabled={currentPage === lastPage}
                        className="rounded-md border px-3 py-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            )}
        </div>
    );
}