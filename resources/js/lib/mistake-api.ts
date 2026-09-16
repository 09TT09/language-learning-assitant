export type Mistake = {
    id: number;
    type: string;
    subtype: string;
    original_text: string;
    corrected_text: string;
    explanation: string;
    severity: string;
    conversation_id: number;
    message_id: number;
    sentence: string;
    created_at: string;
};

export type MistakesResponse = {
    data: Mistake[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

export type MistakeFilters = {
    type?: string;
    subtype?: string;
    severity?: string;
    per_page?: number;
    page?: number;
};

export async function getMistakes(
    filters: MistakeFilters = {},
): Promise<MistakesResponse> {
    const params = new URLSearchParams();

    if (filters.type) {
        params.set('type', filters.type);
    }

    if (filters.subtype) {
        params.set('subtype', filters.subtype);
    }

    if (filters.severity) {
        params.set('severity', filters.severity);
    }

    if (filters.per_page) {
        params.set('per_page', String(filters.per_page));
    }

    if (filters.page) {
        params.set('page', String(filters.page));
    }

    const query = params.toString();

    const response = await fetch(
        `/api/mistakes${query ? `?${query}` : ''}`,
        {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'include',
        },
    );

    if (!response.ok) {
        throw new Error('Failed to load mistakes.');
    }

    return response.json();
}