import { User } from './user.interface';

export interface Vote {
  id: number;
  user_id: number;
  abbreviation_id: number;
  type: 'up' | 'down';
  created_at: string;
  updated_at: string;
}

export interface Comment {
  id: number;
  user_id: number;
  abbreviation_id: number;
  content: string;
  created_at: string;
  updated_at: string;
  user?: User;
}

export interface Abbreviation {
  id: number;
  abbreviation: string;
  meaning: string;
  description?: string;
  category: string;
  user_id: number;
  created_at: string;
  updated_at: string;
  votes_count?: number;
  comments_count?: number;
  votes_sum?: number;
  user_vote?: 'up' | 'down' | null;
  user?: User;
  votes?: Vote[];
  comments?: Comment[];
}

export interface AbbreviationCreateRequest {
  abbreviation: string;
  meaning: string;
  description?: string;
  category: string;
}

export interface AbbreviationUpdateRequest {
  abbreviation?: string;
  meaning?: string;
  description?: string;
  category?: string;
}

export interface AbbreviationSearchParams {
  query?: string;
  category?: string;
  page?: number;
  limit?: number;
}

export interface VoteRequest {
  type: 'up' | 'down';
}

export interface CommentRequest {
  content: string;
}

export interface VoteResult {
  vote: Vote;
  votes_count: number;
}

export interface VoteState {
  userVote?: 'up' | 'down' | null;
  totalVotes: number;
  currentScore?: number;
  isVoting?: boolean;
  abbreviationId?: number;
}

export interface ServiceComment {
  id: number;
  content: string;
  user_id: number;
  user?: User;
  created_at: string;
  replies?: ServiceComment[];
  parent_id?: number;
}

export interface CommentState {
  comments: ServiceComment[];
  isLoading: boolean;
  isSubmitting?: boolean;
  error?: string | null;
  expandedComments?: Set<number>;
  replyingTo?: number | null;
  abbreviationId?: number | null;
}

export interface NewComment {
  content: string;
  parent_id?: number;
  abbreviation_id?: number;
}
