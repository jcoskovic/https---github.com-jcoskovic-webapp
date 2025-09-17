import { Abbreviation } from './abbreviation.interface';

export interface Recommendation {
  id: number;
  abbreviation: string;
  meaning: string;
  category?: string;
  similarity_score?: number;
  score?: number;
  reason?: string;
}

export interface TrendingItem {
  id: number;
  abbreviation: string;
  meaning: string;
  score: number;
  category?: string;
}

export interface Suggestion {
  id: number;
  abbreviation: string;
  meaning: string;
  category: string;
  description?: string;
  confidence_score: number;
  source: 'user' | 'ai' | 'ml';
  status: 'pending' | 'approved' | 'rejected';
  user_id?: number;
  created_at: string;
  type?: string;
  original_meaning?: string;
}

export interface SuggestionResponse {
  abbreviation: string;
  suggestions: Suggestion[];
  suggested_category: string;
  existing?: {
    id: number;
    abbreviation: string;
    meaning: string;
    description?: string;
    category?: string;
  };
}

export interface AlternativeSuggestionResponse {
  existing: Abbreviation | null;
  suggestions: Suggestion[];
}
