import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { AuthService } from './auth.service';
import type {
  Abbreviation,
  Comment,
  PaginatedResponse,
  ApiResponse,
  AlternativeSuggestionResponse,
} from '../interfaces';
import { apiConfig, buildUrl } from '../config/api.config';

@Injectable({
  providedIn: 'root',
})
export class AbbreviationService {
  private http = inject(HttpClient);
  private authService = inject(AuthService);

  private getHeaders(): HttpHeaders {
    let headers = new HttpHeaders({
      'Content-Type': 'application/json',
      Accept: 'application/json',
    });

    const token = this.authService.getToken();
    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`);
    }

    return headers;
  }

  getAbbreviations(params?: {
    search?: string;
    category?: string;
    department?: string;
    page?: number;
  }): Observable<ApiResponse<PaginatedResponse<Abbreviation>>> {
    let url = buildUrl(apiConfig.endpoints.abbreviations);

    if (params) {
      const queryParams = new URLSearchParams();
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== '') {
          queryParams.append(key, value.toString());
        }
      });

      if (queryParams.toString()) {
        url += `?${queryParams.toString()}`;
      }
    }

    return this.http.get<ApiResponse<PaginatedResponse<Abbreviation>>>(url, {
      headers: this.getHeaders(),
    });
  }

  getAbbreviation(id: number): Observable<ApiResponse<Abbreviation>> {
    return this.http.get<ApiResponse<Abbreviation>>(
      buildUrl(apiConfig.endpoints.abbreviationById(id)),
      {
        headers: this.getHeaders(),
      },
    );
  }

  createAbbreviation(data: {
    abbreviation: string;
    meaning: string;
    description?: string;
    category?: string;
    department?: string;
  }): Observable<ApiResponse<Abbreviation>> {
    return this.http.post<ApiResponse<Abbreviation>>(
      buildUrl(apiConfig.endpoints.abbreviations),
      data,
      {
        headers: this.getHeaders(),
      },
    );
  }

  updateAbbreviation(
    id: number,
    data: Partial<{
      abbreviation: string;
      meaning: string;
      description?: string;
      category?: string;
      department?: string;
    }>,
  ): Observable<ApiResponse<Abbreviation>> {
    return this.http.put<ApiResponse<Abbreviation>>(
      buildUrl(apiConfig.endpoints.abbreviationById(id)),
      data,
      {
        headers: this.getHeaders(),
      },
    );
  }

  deleteAbbreviation(id: number): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(
      buildUrl(apiConfig.endpoints.abbreviationById(id)),
      {
        headers: this.getHeaders(),
      },
    );
  }

  voteAbbreviation(
    id: number,
    type: 'up' | 'down',
  ): Observable<ApiResponse<unknown>> {
    return this.http.post<ApiResponse<unknown>>(
      buildUrl(apiConfig.endpoints.abbreviationVote(id)),
      { type },
      {
        headers: this.getHeaders(),
      },
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

  // Admin methods
  getAdminStatistics(): Observable<unknown> {
    return this.http
      .get<unknown>(buildUrl(apiConfig.endpoints.adminStatistics), {
        headers: this.getHeaders(),
      })
      .pipe(
        map(
          (response: unknown) =>
            (response as { data?: unknown }).data || response,
        ),
      );
  }

  getUsers(): Observable<unknown[]> {
    return this.http
      .get<unknown>(buildUrl(apiConfig.endpoints.adminUsers), {
        headers: this.getHeaders(),
      })
      .pipe(
        map(
          (response: unknown) =>
            ((response as { data?: unknown }).data || response) as unknown[],
        ),
      );
  }

  getAllAbbreviations(): Observable<unknown[]> {
    return this.http
      .get<unknown>(buildUrl(apiConfig.endpoints.adminAbbreviations), {
        headers: this.getHeaders(),
      })
      .pipe(
        map(
          (response: unknown) =>
            ((response as { data?: unknown }).data || response) as unknown[],
        ),
      );
  }

  getPendingAbbreviations(): Observable<unknown[]> {
    return this.http
      .get<unknown>(buildUrl(apiConfig.endpoints.adminAbbreviationsPending), {
        headers: this.getHeaders(),
      })
      .pipe(
        map(
          (response: unknown) =>
            ((response as { data?: unknown }).data || response) as unknown[],
        ),
      );
  }

  promoteUser(userId: number): Observable<unknown> {
    return this.http.post<unknown>(
      buildUrl(apiConfig.endpoints.adminUserPromote(userId)),
      {},
      {
        headers: this.getHeaders(),
      },
    );
  }

  demoteUser(userId: number): Observable<unknown> {
    return this.http.post<unknown>(
      buildUrl(apiConfig.endpoints.adminUserDemote(userId)),
      {},
      {
        headers: this.getHeaders(),
      },
    );
  }

  deleteUser(userId: number): Observable<unknown> {
    return this.http.delete<unknown>(
      buildUrl(apiConfig.endpoints.adminUserDelete(userId)),
      {
        headers: this.getHeaders(),
      },
    );
  }

  approveAbbreviation(abbreviationId: number): Observable<unknown> {
    return this.http.post<unknown>(
      buildUrl(apiConfig.endpoints.adminAbbreviationApprove(abbreviationId)),
      {},
      {
        headers: this.getHeaders(),
      },
    );
  }

  rejectAbbreviation(abbreviationId: number): Observable<unknown> {
    return this.http.post<unknown>(
      buildUrl(apiConfig.endpoints.adminAbbreviationReject(abbreviationId)),
      {},
      {
        headers: this.getHeaders(),
      },
    );
  }

  getCategories(): Observable<ApiResponse<string[]>> {
    return this.http.get<ApiResponse<string[]>>(
      buildUrl(apiConfig.endpoints.categories),
      {
        headers: this.getHeaders(),
      },
    );
  }

  getSuggestions(
    abbreviation: string,
  ): Observable<ApiResponse<AlternativeSuggestionResponse>> {
    return this.http.get<ApiResponse<AlternativeSuggestionResponse>>(
      buildUrl(apiConfig.endpoints.suggestions, { abbreviation }),
      {
        headers: this.getHeaders(),
      },
    );
  }
}
