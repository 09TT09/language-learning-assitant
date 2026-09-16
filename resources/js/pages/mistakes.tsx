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

const mistakeSubtypes: Record<
    string,
    { label: string; value: string }[]
> = {
    grammar: [
        { label: 'All', value: '' },
        { label: 'Verb conjugation', value: 'verb_conjugation' },
        { label: 'Verb tense', value: 'verb_tense' },
        { label: 'Preposition', value: 'preposition' },
        { label: 'Article', value: 'article' },
        { label: 'Gender agreement', value: 'gender_agreement' },
        { label: 'Number agreement', value: 'number_agreement' },
        { label: 'Pronoun', value: 'pronoun' },
    ],

    vocabulary: [
        { label: 'All', value: '' },
        { label: 'Wrong word', value: 'wrong_word' },
        { label: 'False friend', value: 'false_friend' },
    ],

    spelling: [
        { label: 'All', value: '' },
        { label: 'Typo', value: 'typo' },
        { label: 'Accent', value: 'accent' },
    ],
};

export default function Mistakes() {
    const [mistakes, setMistakes] = useState<Mistake[]>([]);
    const [selectedType, setSelectedType] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedSeverity, setSelectedSeverity] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [selectedSubtype, setSelectedSubtype] = useState('');

    useEffect(() => {
        const fetchMistakes = async () => {
            try {
                setLoading(true);
                setError(null);
    
                const result = await getMistakes({
                    type: selectedType || undefined,
                    subtype: selectedSubtype || undefined,
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
    }, [selectedType, selectedSubtype, selectedSeverity, currentPage]);

    useEffect(() => {
        setCurrentPage(1);
    }, [selectedType, selectedSubtype, selectedSeverity]);

    useEffect(() => {
        setSelectedSubtype('');
    }, [selectedType]);

    const renderSentenceWithMistake = (mistake: Mistake) => {
        const sentence = mistake.sentence;
        const originalText = mistake.original_text;
    
        const index = sentence
            .toLocaleLowerCase()
            .indexOf(originalText.toLocaleLowerCase());
    
        if (index === -1) {
            return sentence;
        }
    
        const before = sentence.slice(0, index);
        const mistakeText = sentence.slice(
            index,
            index + originalText.length,
        );
        const after = sentence.slice(
            index + originalText.length,
        );
    
        return (
            <>
                {before}
                <span className="rounded-sm bg-destructive/15 px-1 text-destructive underline decoration-destructive/50">
                    {mistakeText}
                </span>
                {after}
            </>
        );
    };

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

            {selectedType && mistakeSubtypes[selectedType] && (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-medium">
                        Subtype:
                    </span>

                    {mistakeSubtypes[selectedType].map((subtype) => (
                        <button
                            key={subtype.value}
                            type="button"
                            onClick={() =>
                                setSelectedSubtype(subtype.value)
                            }
                            className={`rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                                selectedSubtype === subtype.value
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted hover:bg-muted/80'
                            }`}
                        >
                            {subtype.label}
                        </button>
                    ))}
                </div>
            )}

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
                <div className="space-y-4">
                    {[1, 2, 3].map((item) => (
                        <div
                            key={item}
                            className="animate-pulse rounded-lg border p-5"
                        >
                            {/* Metadata */}
                            <div className="flex gap-2">
                                <div className="h-6 w-20 rounded-md bg-muted" />
                                <div className="h-6 w-28 rounded-md bg-muted" />
                                <div className="h-6 w-16 rounded-md bg-muted" />
                            </div>

                            {/* Original */}
                            <div className="mt-5">
                                <div className="h-4 w-16 rounded bg-muted" />
                                <div className="mt-2 h-5 w-3/4 rounded bg-muted" />
                            </div>

                            {/* Correction */}
                            <div className="mt-4">
                                <div className="h-4 w-16 rounded bg-muted" />
                                <div className="mt-2 h-5 w-2/3 rounded bg-muted" />
                            </div>

                            {/* Explanation */}
                            <div className="mt-4">
                                <div className="h-4 w-24 rounded bg-muted" />
                                <div className="mt-2 h-4 w-full rounded bg-muted" />
                                <div className="mt-2 h-4 w-4/5 rounded bg-muted" />
                            </div>
                        </div>
                    ))}
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
                            {/* Metadata */}
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="rounded-md bg-muted px-2 py-1 text-xs font-medium capitalize">
                                    {mistake.type.replaceAll('_', ' ')}
                                </span>

                                {mistake.subtype && (
                                    <span className="rounded-md bg-muted px-2 py-1 text-xs capitalize">
                                        {mistake.subtype.replaceAll('_', ' ')}
                                    </span>
                                )}

                                <span className="rounded-md bg-muted px-2 py-1 text-xs capitalize">
                                    {mistake.severity}
                                </span>
                            </div>

                            {/* Original */}
                            <div className="mt-5">
                                <p className="text-sm font-medium text-muted-foreground">
                                    You said
                                </p>

                                <p className="mt-1 text-base leading-relaxed">
                                    {renderSentenceWithMistake(mistake)}
                                </p>
                            </div>

                            {/* Correction */}
                            <div className="mt-4">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Correct
                                </p>

                                <p className="mt-1 text-base font-medium">
                                    {mistake.corrected_text}
                                </p>
                            </div>

                            {/* Explanation */}
                            <div className="mt-4">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Explanation
                                </p>

                                <p className="mt-1 text-sm">
                                    {mistake.explanation}
                                </p>
                            </div>
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