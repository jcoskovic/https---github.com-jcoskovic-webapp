import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { tap } from 'rxjs/operators';
import { RoleHelper } from '../enums/user-role.enum';
import { User } from '../interfaces/user.interface';
import { ApiResponse } from '../interfaces/api.interface';
import {
  LoginRequest,
  RegisterRequest,
  LoginResponse,
  RegisterResponse,
  ChangePasswordRequest,
  ForgotPasswordRequest,
  ResetPasswordRequest,
} from '../interfaces/auth.interface';
import { apiConfig, buildUrl } from '../config/api.config';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private http = inject(HttpClient);

  private token = '';
  private currentUser: User | null = null;

  constructor() {
    const savedToken = localStorage.getItem('auth_token');
    if (savedToken) {
      if (this.isTokenValid(savedToken)) {
        this.token = savedToken;
      } else {
        localStorage.removeItem('auth_token');
      }
    }
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

  setToken(token: string): void {
    this.token = token;
    if (token) {
      localStorage.setItem('auth_token', token);
      // Clear cached user when token changes
      this.currentUser = null;
    } else {
      localStorage.removeItem('auth_token');
      this.currentUser = null;
    }
  }

  getToken(): string {
    return this.token;
  }

  private isTokenValid(token: string): boolean {
    if (!token) return false;

    try {
      const payload = token.split('.')[1];
      const decoded = JSON.parse(atob(payload));

      // Check if token has expiration and if it's not expired
      if (decoded.exp) {
        const currentTime = Math.floor(Date.now() / 1000);
        return decoded.exp > currentTime;
      }

      // If no expiration, assume it's valid (though not recommended)
      return true;
    } catch {
      return false;
    }
  }

  isAuthenticated(): boolean {
    const token = this.getToken();
    return !!token && this.isTokenValid(token);
  }

  isAdmin(): boolean {
    // Use cached user if available
    if (this.currentUser) {
      return RoleHelper.hasAdminPrivileges(this.currentUser.role);
    }

    // Fallback to token parsing (will likely return false due to missing role in JWT)
    const user = this.getCurrentUserFromToken();
    return user ? RoleHelper.hasAdminPrivileges(user.role) : false;
  }

  // Set cached user (to be called after successful API calls)
  setCachedUser(user: User | null): void {
    this.currentUser = user;
  }

  // Get cached user
  getCachedUser(): User | null {
    return this.currentUser;
  }

  isModerator(): boolean {
    // Use cached user if available
    if (this.currentUser) {
      return RoleHelper.hasModeratorPrivileges(this.currentUser.role);
    }

    // Fallback to token parsing (will likely return false due to missing role in JWT)
    const user = this.getCurrentUserFromToken();
    return user ? RoleHelper.hasModeratorPrivileges(user.role) : false;
  }

  canModerate(): boolean {
    // Use cached user if available
    if (this.currentUser) {
      return RoleHelper.canModerateContent(this.currentUser.role);
    }

    // Fallback to token parsing (will likely return false due to missing role in JWT)
    const user = this.getCurrentUserFromToken();
    return user ? RoleHelper.canModerateContent(user.role) : false;
  }

  // Get current user info from local token (synchronous)
  getCurrentUserFromToken(): User | null {
    const token = this.getToken();
    if (!token || !this.isTokenValid(token)) {
      // Clean up invalid token
      if (token) {
        this.setToken('');
      }
      return null;
    }

    try {
      // Decode JWT token (simple base64 decode of payload)
      const payload = token.split('.')[1];
      const decoded = JSON.parse(atob(payload));
      return decoded;
    } catch {
      // Clean up invalid token
      this.setToken('');
      return null;
    }
  }

  login(credentials: LoginRequest): Observable<LoginResponse> {
    return this.http
      .post<LoginResponse>(buildUrl(apiConfig.endpoints.login), credentials, {
        headers: this.getHeaders(),
      })
      .pipe(
        tap((response) => {
          if (response.status === 'success' && response.data.user) {
            // Cache the user data for instant access
            this.setCachedUser(response.data.user);
          }
        }),
      );
  }

  register(data: RegisterRequest): Observable<RegisterResponse> {
    return this.http.post<RegisterResponse>(
      buildUrl(apiConfig.endpoints.register),
      data,
      {
        headers: this.getHeaders(),
      },
    );
  }

  logout(): Observable<ApiResponse<null>> {
    return this.http
      .post<ApiResponse<null>>(
        buildUrl(apiConfig.endpoints.logout),
        {},
        {
          headers: this.getHeaders(),
        },
      )
      .pipe(
        tap(() => {
          // Clear cached user on logout
          this.currentUser = null;
        }),
      );
  }

  getCurrentUser(): Observable<ApiResponse<{ user: User }>> {
    return this.http
      .get<ApiResponse<{ user: User }>>(buildUrl(apiConfig.endpoints.me), {
        headers: this.getHeaders(),
      })
      .pipe(
        tap((response) => {
          if (response.status === 'success' && response.data.user) {
            // Cache the user data for instant access
            this.setCachedUser(response.data.user);
          }
        }),
      );
  }

  refreshToken(): Observable<ApiResponse<{ token: string }>> {
    return this.http.post<ApiResponse<{ token: string }>>(
      buildUrl(apiConfig.endpoints.refresh),
      {},
      {
        headers: this.getHeaders(),
      },
    );
  }

  forgotPassword(email: string): Observable<ApiResponse<null>> {
    const request: ForgotPasswordRequest = { email };
    return this.http.post<ApiResponse<null>>(
      buildUrl(apiConfig.endpoints.forgotPassword),
      request,
      {
        headers: this.getHeaders(),
      },
    );
  }

  resetPassword(data: ResetPasswordRequest): Observable<ApiResponse<null>> {
    return this.http.post<ApiResponse<null>>(
      buildUrl(apiConfig.endpoints.resetPassword),
      data,
      {
        headers: this.getHeaders(),
      },
    );
  }

  changePassword(data: ChangePasswordRequest): Observable<ApiResponse<null>> {
    return this.http.post<ApiResponse<null>>(
      buildUrl(apiConfig.endpoints.changePassword),
      data,
      {
        headers: this.getHeaders(),
      },
    );
  }

  verifyEmail(data: {
    token: string;
    email: string;
  }): Observable<ApiResponse<null>> {
    return this.http.post<ApiResponse<null>>(
      buildUrl(apiConfig.endpoints.verifyEmail),
      data,
      {
        headers: this.getHeaders(),
      },
    );
  }

  resendVerification(email: string): Observable<ApiResponse<null>> {
    return this.http.post<ApiResponse<null>>(
      buildUrl(apiConfig.endpoints.resendVerification),
      { email },
      {
        headers: this.getHeaders(),
      },
    );
  }

  isEmailVerified(): boolean {
    const user = this.getCachedUser();
    return user ? !!user.email_verified_at : false;
  }
}
