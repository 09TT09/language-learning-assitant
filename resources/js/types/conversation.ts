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
    mistakes?: Mistake[];
    corrected_sentence?: string;
}

export type Conversation = {
    id: number;
    user_id: number;
    language: string;
    level: ConversationLevel;
    created_at: string;
    updated_at: string;
};

export type ConversationWithMessages = Conversation & {
    messages: ChatMessage[];
};

export type SendMessageResponse = {
    corrected_sentence: string;
    message: {
        id: number;
        conversation_id: number;
        role: 'assistant' | 'user';
        content: string;
    };
    mistakes: Mistake[];
};