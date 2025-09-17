import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import {
  AdminUser,
  AdminAbbreviation,
  AdminStatistics,
} from '../interfaces/admin.interface';
import { BatchOperationRequest } from '../interfaces/admin.interface';
import { apiConfig, buildUrl } from '../config/api.config';

@Injectable({
  providedIn: 'root',
})
export class AdminService {
  private http = inject(HttpClient);

  private getHeaders(): HttpHeaders {
    let headers = new HttpHeaders({
      'Content-Type': 'application/json',
      Accept: 'application/json',
    });

    const token = localStorage.getItem('auth_token');
    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`);
    }

    return headers;
  }

  // Statistics
  getStatistics(): Observable<AdminStatistics> {
    return this.http
      .get<unknown>(buildUrl(apiConfig.endpoints.adminStatistics), {
        headers: this.getHeaders(),
      })
      .pipe(
        map(
          (response: unknown) =>
            ((response as { data?: unknown }).data ||
              response) as AdminStatistics,
        ),
      );
  }

  // User Management
  getUsers(): Observable<AdminUser[]> {
    return this.http
      .get<unknown>(buildUrl(apiConfig.endpoints.adminUsers), {
        headers: this.getHeaders(),
      })
      .pipe(
        map(
          (response: unknown) =>
            ((response as { data?: unknown }).data || response) as AdminUser[],
        ),
      );
  }

  promoteUser(userId: number): Observable<unknown> {
    return this.http.post(
      `${apiConfig.baseUrl}/admin/users/${userId}/promote`,
      {},
      {
        headers: this.getHeaders(),
      },
    );
  }

  demoteUser(userId: number): Observable<unknown> {
    return this.http.post(
      `${apiConfig.baseUrl}/admin/users/${userId}/demote`,
      {},
      {
        headers: this.getHeaders(),
      },
    );
  }

  deleteUser(userId: number): Observable<unknown> {
    return this.http.delete(`${apiConfig.baseUrl}/admin/users/${userId}`, {
      headers: this.getHeaders(),
    });
  }

  // Abbreviation Management
  getAllAbbreviations(): Observable<AdminAbbreviation[]> {
    return this.http
      .get<unknown>(buildUrl(apiConfig.endpoints.adminAbbreviations), {
        headers: this.getHeaders(),
      })
      .pipe(
        map(
          (response: unknown) =>
            ((response as { data?: unknown }).data ||
              response) as AdminAbbreviation[],
        ),
      );
  }

  deleteAbbreviation(abbreviationId: number): Observable<unknown> {
    return this.http.delete(
      `${apiConfig.baseUrl}/admin/abbreviations/${abbreviationId}`,
      {
        headers: this.getHeaders(),
      },
    );
  }

  // Moderation
  getPendingAbbreviations(): Observable<AdminAbbreviation[]> {
    return this.http
      .get<unknown>(buildUrl(apiConfig.endpoints.adminAbbreviationsPending), {
        headers: this.getHeaders(),
      })
      .pipe(
        map(
          (response: unknown) =>
            ((response as { data?: unknown }).data ||
              response) as AdminAbbreviation[],
        ),
      );
  }

  approveAbbreviation(abbreviationId: number): Observable<unknown> {
    return this.http.post(
      `${apiConfig.baseUrl}/admin/abbreviations/${abbreviationId}/approve`,
      {},
      {
        headers: this.getHeaders(),
      },
    );
  }

  rejectAbbreviation(abbreviationId: number): Observable<unknown> {
    return this.http.post(
      `${apiConfig.baseUrl}/admin/abbreviations/${abbreviationId}/reject`,
      {},
      {
        headers: this.getHeaders(),
      },
    );
  }

  // Batch operations
  deleteMultipleUsers(userIds: number[]): Observable<unknown> {
    const request: BatchOperationRequest = { userIds };
    return this.http.post(
      `${apiConfig.baseUrl}/admin/users/batch-delete`,
      request,
      {
        headers: this.getHeaders(),
      },
    );
  }

  deleteMultipleAbbreviations(abbreviationIds: number[]): Observable<unknown> {
    const request: BatchOperationRequest = { abbreviationIds };
    return this.http.post(
      `${apiConfig.baseUrl}/admin/abbreviations/batch-delete`,
      request,
      {
        headers: this.getHeaders(),
      },
    );
  }

  approveMultipleAbbreviations(abbreviationIds: number[]): Observable<unknown> {
    const request: BatchOperationRequest = { abbreviationIds };
    return this.http.post(
      `${apiConfig.baseUrl}/admin/abbreviations/batch-approve`,
      request,
      {
        headers: this.getHeaders(),
      },
    );
  }

  rejectMultipleAbbreviations(abbreviationIds: number[]): Observable<unknown> {
    const request: BatchOperationRequest = { abbreviationIds };
    return this.http.post(
      `${apiConfig.baseUrl}/admin/abbreviations/batch-reject`,
      request,
      {
        headers: this.getHeaders(),
      },
    );
  }
}
