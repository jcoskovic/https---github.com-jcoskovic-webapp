import { UserRole } from '../enums/user-role.enum';

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string | null;
  department?: string;
  role: UserRole;
  created_at: string;
  updated_at: string;
  abbreviations_count?: number;
  votes_count?: number;
  comments_count?: number;
}

export interface AuthResponse {
  user: User;
  token: string;
  email_verification_sent?: boolean;
}
