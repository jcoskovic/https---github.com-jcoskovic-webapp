import { UserRole } from '../enums/user-role.enum';

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  created_at: string;
  abbreviations_count?: number;
  votes_count?: number;
  comments_count?: number;
}

export interface AdminAbbreviation {
  id: number;
  abbreviation: string;
  meaning: string;
  description?: string;
  category: string;
  user: {
    id: number;
    name: string;
    email: string;
  };
  votes_sum: number;
  comments_count: number;
  created_at: string;
  status: 'pending' | 'approved' | 'rejected';
}

export interface AdminStatistics {
  total_users: number;
  total_abbreviations: number;
  total_votes: number;
  total_comments: number;
  pending_abbreviations: number;
  active_users_today: number;
  top_categories: { name: string; count: number }[];
}

export interface TopCategory {
  name: string;
  count: number;
}

export interface ConfirmationModalData {
  title: string;
  message: string;
  action: () => void;
}

export interface BatchOperationRequest {
  ids?: number[];
  action?: string;
  userIds?: number[];
  abbreviationIds?: number[];
}

export type AdminTabType =
  | 'overview'
  | 'users'
  | 'abbreviations'
  | 'moderation';
