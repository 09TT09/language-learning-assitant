export type ConversationLevel =
    | 'A1'
    | 'A2'
    | 'B1'
    | 'B2'
    | 'C1'
    | 'C2';

export type MistakeType =
    | 'grammar'
    | 'vocabulary'
    | 'spelling'
    | 'word_order';

export type MistakeSubtype =
    | 'verb_conjugation'
    | 'verb_tense'
    | 'preposition'
    | 'article'
    | 'gender_agreement'
    | 'number_agreement'
    | 'pronoun'
    | 'wrong_word'
    | 'false_friend'
    | 'typo'
    | 'accent'
    | 'other';

export type MistakeSeverity =
    | 'low'
    | 'medium'
    | 'high';

export type Mistake = {
    id: number;
    conversation_id: number;
    message_id: number;
    type: MistakeType;
    subtype: MistakeSubtype;
    original_text: string;
    corrected_text: string;
    explanation: string;
    severity: MistakeSeverity;
    created_at: string;
    updated_at: string;
};

export interface ChatMessage {
    id: number | string;
    role: 'user' | 'assistant';
    content: string;
    created_at: string;
    mistakes?: Mistake[];
    corrected_sentence?: string;
}

export type Conversation = {
    id: number;
    user_id: number;
    topic_id: number | null;
    current_step_id: number | null;
    current_step: ConversationStep | null;
    scenario_steps: ConversationScenarioStep[];
    title: string | null;
    language: string;
    level: ConversationLevel;
    created_at: string;
    updated_at: string;
};

export type ConversationWithMessages = Conversation & {
    messages: ChatMessage[];
};

export type ConversationStep = {
    id: number;
    position: number;
    title: string;
    narrator: string;
    objective: string;
    characters: {
        id: number;
        name: string;
        role: string;
        description: string | null;
    }[];
};

export type ConversationScenarioStep = {
    id: number;
    created_at: string;
    step: ConversationStep;
};

export type SendMessageResponse = {
    message: ChatMessage;
    corrected_sentence: string;
    mistakes: Mistake[];
    step_completed: boolean;
    next_step: ConversationStep | null;
};