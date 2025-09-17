import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import {
  User,
  AuthResponse,
  Abbreviation,
  Comment,
  PaginatedResponse,
  ApiResponse,
  Recommendation,
  SuggestionResponse,
  ExportOptions,
} from '../interfaces';
import { apiConfig, buildUrl } from '../config/api.config';

// Define specific types for API parameters
interface AbbreviationQueryParams {
  page?: number;
  per_page?: number;
  search?: string;
  category?: string;
  sort?: string;
  order?: 'asc' | 'desc';
}

interface RecommendationQueryParams {
  limit?: number;
  category?: string;
  user_id?: number;
}

export interface StatsResponse {
  total_abbreviations: number;
  total_users: number;
  total_comments: number;
  total_votes: number;
  popular_categories: Array<{ category: string; count: number }>;
  recent_activity: Array<{ type: string; count: number; date: string }>;
}

interface VoteResponse {
  success: boolean;
  message: string;
  votes_sum: number;
  user_vote: 'up' | 'down' | null;
}

interface ChangePasswordResponse {
  success: boolean;
  message: string;
}

interface LogoutResponse {
  success: boolean;
  message: string;
}

interface MLHealthResponse {
  status: 'healthy' | 'unhealthy';
  version: string;
  last_trained: string | null;
  model_accuracy?: number;
}

interface MLTrainingResponse {
  success: boolean;
  message: string;
  training_id: string;
  estimated_duration: number;
}

@Injectable({
  providedIn: 'root',
})
export class ApiService {
  private http = inject(HttpClient);

  private token: string | null = null;

  constructor() {
    this.token = localStorage.getItem('auth_token');
  }

  private getHeaders(): HttpHeaders {
    let headers = new HttpHeaders({
      'Content-Type': 'application/json',
      Accept: 'application/json',
    });

    if (this.token) {
      headers = headers.set('Authorization', `Bearer ${this.token}`);
    }

    return headers;
  }

  // Authentication methods
  register(userData: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    department?: string;
  }): Observable<ApiResponse<AuthResponse>> {
    return this.http.post<ApiResponse<AuthResponse>>(
      buildUrl(apiConfig.endpoints.register),
      userData,
      { headers: this.getHeaders() },
    );
  }

  login(credentials: {
    email: string;
    password: string;
  }): Observable<ApiResponse<AuthResponse>> {
    return this.http.post<ApiResponse<AuthResponse>>(
      buildUrl(apiConfig.endpoints.login),
      credentials,
      { headers: this.getHeaders() },
    );
  }

  logout(): Observable<ApiResponse<LogoutResponse>> {
    return this.http.post<ApiResponse<LogoutResponse>>(
      buildUrl(apiConfig.endpoints.logout),
      {},
      { headers: this.getHeaders() },
    );
  }

  getCurrentUser(): Observable<ApiResponse<{ user: User }>> {
    return this.http.get<ApiResponse<{ user: User }>>(
      buildUrl(apiConfig.endpoints.me),
      { headers: this.getHeaders() },
    );
  }

  changePassword(passwordData: {
    current_password: string;
    new_password: string;
    new_password_confirmation: string;
  }): Observable<ApiResponse<ChangePasswordResponse>> {
    return this.http.post<ApiResponse<ChangePasswordResponse>>(
      buildUrl(apiConfig.endpoints.changePassword),
      passwordData,
      { headers: this.getHeaders() },
    );
  }

  refreshToken(): Observable<ApiResponse<{ token: string }>> {
    return this.http.post<ApiResponse<{ token: string }>>(
      buildUrl(apiConfig.endpoints.refresh),
      {},
      { headers: this.getHeaders() },
    );
  }

  setToken(token: string): void {
    if (token) {
      this.token = token;
      localStorage.setItem('auth_token', token);
    } else {
      this.token = null;
      localStorage.removeItem('auth_token');
    }
  }

  removeToken(): void {
    this.token = null;
    localStorage.removeItem('auth_token');
  }

  isAuthenticated(): boolean {
    return !!this.token;
  }

  // Abbreviation methods
  getAbbreviations(
    params?: AbbreviationQueryParams,
  ): Observable<ApiResponse<PaginatedResponse<Abbreviation>>> {
    let url = buildUrl(apiConfig.endpoints.abbreviations);
    if (params) {
      const queryParams = new URLSearchParams(
        params as Record<string, string>,
      ).toString();
      url += `?${queryParams}`;
    }
    return this.http.get<ApiResponse<PaginatedResponse<Abbreviation>>>(url, {
      headers: this.getHeaders(),
    });
  }

  getAbbreviation(id: number): Observable<ApiResponse<Abbreviation>> {
    return this.http.get<ApiResponse<Abbreviation>>(
      buildUrl(apiConfig.endpoints.abbreviationById(id)),
      { headers: this.getHeaders() },
    );
  }

  createAbbreviation(
    abbreviation: Partial<Abbreviation>,
  ): Observable<ApiResponse<Abbreviation>> {
    return this.http.post<ApiResponse<Abbreviation>>(
      buildUrl(apiConfig.endpoints.abbreviations),
      abbreviation,
      { headers: this.getHeaders() },
    );
  }

  getSuggestions(
    abbreviation: string,
  ): Observable<ApiResponse<SuggestionResponse>> {
    return this.http.get<ApiResponse<SuggestionResponse>>(
      buildUrl(apiConfig.endpoints.abbreviationSuggestions, {
        abbreviation: encodeURIComponent(abbreviation),
      }),
      { headers: this.getHeaders() },
    );
  }

  updateAbbreviation(
    id: number,
    abbreviation: Partial<Abbreviation>,
  ): Observable<ApiResponse<Abbreviation>> {
    return this.http.put<ApiResponse<Abbreviation>>(
      buildUrl(apiConfig.endpoints.abbreviationById(id)),
      abbreviation,
      { headers: this.getHeaders() },
    );
  }

  deleteAbbreviation(
    id: number,
  ): Observable<ApiResponse<{ success: boolean; message: string }>> {
    return this.http.delete<ApiResponse<{ success: boolean; message: string }>>(
      buildUrl(apiConfig.endpoints.abbreviationById(id)),
      { headers: this.getHeaders() },
    );
  }

  voteAbbreviation(
    id: number,
    type: 'up' | 'down',
  ): Observable<ApiResponse<VoteResponse>> {
    return this.http.post<ApiResponse<VoteResponse>>(
      buildUrl(apiConfig.endpoints.abbreviationVote(id)),
      { type },
      { headers: this.getHeaders() },
    );
  }

  addComment(
    abbreviationId: number,
    content: string,
  ): Observable<ApiResponse<Comment>> {
    return this.http.post<ApiResponse<Comment>>(
      buildUrl(apiConfig.endpoints.abbreviationComment(abbreviationId)),
      { content },
      { headers: this.getHeaders() },
    );
  }

  getCategories(): Observable<ApiResponse<string[]>> {
    return this.http.get<ApiResponse<string[]>>(
      buildUrl(apiConfig.endpoints.categories),
      { headers: this.getHeaders() },
    );
  }

  getStats(): Observable<ApiResponse<StatsResponse>> {
    return this.http.get<ApiResponse<StatsResponse>>(
      buildUrl(apiConfig.endpoints.stats),
      { headers: this.getHeaders() },
    );
  }

  // Export methods
  getExportPdfUrl(options?: ExportOptions): string {
    const params = new URLSearchParams();
    if (options?.abbreviation_ids?.length) {
      options.abbreviation_ids.forEach((id) =>
        params.append('abbreviation_ids[]', id.toString()),
      );
    }
    if (options?.category) params.set('category', options.category);
    if (options?.search) params.set('search', options.search);
    if (options?.format) params.set('format', options.format);

    const queryString = params.toString();
    return `${apiConfig.baseUrl}${apiConfig.endpoints.exportPdf}${queryString ? '?' + queryString : ''}`;
  }

  // ML/Recommendation methods
  getMLHealth(): Observable<ApiResponse<MLHealthResponse>> {
    return this.http.get<ApiResponse<MLHealthResponse>>(
      buildUrl(apiConfig.endpoints.mlHealth),
      { headers: this.getHeaders() },
    );
  }

  getRecommendations(
    abbreviationId: number,
    params?: RecommendationQueryParams,
  ): Observable<ApiResponse<Recommendation[]>> {
    let url = buildUrl(apiConfig.endpoints.mlRecommendations(abbreviationId));
    if (params) {
      const queryParams = new URLSearchParams(
        params as Record<string, string>,
      ).toString();
      url += `?${queryParams}`;
    }
    return this.http.get<ApiResponse<Recommendation[]>>(url, {
      headers: this.getHeaders(),
    });
  }

  getPersonalizedRecommendations(
    userId: number,
    limit = 10,
  ): Observable<ApiResponse<Recommendation[]>> {
    return this.http.get<ApiResponse<Recommendation[]>>(
      buildUrl(apiConfig.endpoints.mlPersonalized(userId), { limit }),
      { headers: this.getHeaders() },
    );
  }

  getTrendingAbbreviations(
    limit = 10,
  ): Observable<ApiResponse<Recommendation[]>> {
    return this.http.get<ApiResponse<Recommendation[]>>(
      buildUrl(apiConfig.endpoints.mlTrending, { limit }),
      { headers: this.getHeaders() },
    );
  }

  trainMLModel(): Observable<ApiResponse<MLTrainingResponse>> {
    return this.http.post<ApiResponse<MLTrainingResponse>>(
      buildUrl(apiConfig.endpoints.mlTrain),
      {},
      { headers: this.getHeaders() },
    );
  }

  // Moderator API methods
  getModeratorStatistics(): Observable<ApiResponse<any>> {
    return this.http.get<ApiResponse<any>>(
      buildUrl('/moderator/statistics'),
      { headers: this.getHeaders() }
    );
  }

  getModeratorAbbreviations(): Observable<ApiResponse<any[]>> {
    return this.http.get<ApiResponse<any[]>>(
      buildUrl('/moderator/abbreviations'),
      { headers: this.getHeaders() }
    );
  }

  getPendingAbbreviations(): Observable<ApiResponse<any[]>> {
    return this.http.get<ApiResponse<any[]>>(
      buildUrl('/moderator/abbreviations/pending'),
      { headers: this.getHeaders() }
    );
  }

  approveAbbreviation(abbreviationId: number): Observable<ApiResponse<any>> {
    return this.http.post<ApiResponse<any>>(
      buildUrl(`/moderator/abbreviations/${abbreviationId}/approve`),
      {},
      { headers: this.getHeaders() }
    );
  }

  rejectAbbreviation(abbreviationId: number): Observable<ApiResponse<any>> {
    return this.http.post<ApiResponse<any>>(
      buildUrl(`/moderator/abbreviations/${abbreviationId}/reject`),
      {},
      { headers: this.getHeaders() }
    );
  }
}
