import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, throwError } from 'rxjs';
import {
  catchError,
  tap,
  debounceTime,
  distinctUntilChanged,
  switchMap,
  map,
} from 'rxjs/operators';
import { environment } from '../../environments/environment';

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
  original_meaning?: string;
}

export interface SuggestionRequest {
  text?: string;
  category?: string;
  context?: string;
  limit?: number;
}

export interface NewSuggestion {
  abbreviation: string;
  meaning: string;
  category: string;
  description?: string;
}

export interface SuggestionState {
  suggestions: Suggestion[];
  isLoading: boolean;
  isSubmitting: boolean;
  error: string | null;
  lastQuery: string;
  pendingSuggestions: Suggestion[];
}

@Injectable({
  providedIn: 'root',
})
export class SuggestionService {
  private http = inject(HttpClient);

  private apiUrl = environment.apiUrl;
  private suggestionStateSubject = new BehaviorSubject<SuggestionState>({
    suggestions: [],
    isLoading: false,
    isSubmitting: false,
    error: null,
    lastQuery: '',
    pendingSuggestions: [],
  });

  public suggestionState$ = this.suggestionStateSubject.asObservable();

  getCurrentState(): SuggestionState {
    return this.suggestionStateSubject.value;
  }

  private updateState(updates: Partial<SuggestionState>): void {
    const currentState = this.suggestionStateSubject.value;
    this.suggestionStateSubject.next({ ...currentState, ...updates });
  }

  // Get AI-powered suggestions based on text input
  getSuggestionsForText(request: SuggestionRequest): Observable<Suggestion[]> {
    this.updateState({
      isLoading: true,
      error: null,
      lastQuery: request.text || '',
    });

    return this.http
      .post<{
        data: Suggestion[];
      }>(`${this.apiUrl}/suggestions/generate`, request)
      .pipe(
        tap((response) => {
          this.updateState({
            suggestions: response.data || [],
            isLoading: false,
            error: null,
          });
        }),
        map((response) => response.data),
        catchError((error) => {
          this.updateState({
            isLoading: false,
            error: this.getErrorMessage(error),
          });
          return throwError(() => error);
        }),
      );
  }

  // Get suggestions for specific category
  getSuggestionsForCategory(
    category: string,
    limit = 20,
  ): Observable<Suggestion[]> {
    this.updateState({ isLoading: true, error: null });

    return this.http
      .get<{ data: Suggestion[] }>(
        `${this.apiUrl}/suggestions/category/${encodeURIComponent(category)}`,
        {
          params: { limit: limit.toString() },
        },
      )
      .pipe(
        tap((response) => {
          this.updateState({
            suggestions: response.data || [],
            isLoading: false,
            error: null,
          });
        }),
        map((response) => response.data),
        catchError((error) => {
          this.updateState({
            suggestions: [],
            isLoading: false,
            error: this.getErrorMessage(error),
          });
          return throwError(() => error);
        }),
      );
  }

  // Submit new suggestion from user
  submitSuggestion(suggestion: NewSuggestion): Observable<Suggestion> {
    this.updateState({ isSubmitting: true, error: null });

    return this.http
      .post<{ data: Suggestion }>(`${this.apiUrl}/suggestions`, suggestion)
      .pipe(
        tap((response) => {
          const newSuggestion = response.data;
          const currentState = this.getCurrentState();

          this.updateState({
            suggestions: [...currentState.suggestions, newSuggestion],
            pendingSuggestions: [
              ...currentState.pendingSuggestions,
              newSuggestion,
            ],
            isSubmitting: false,
            error: null,
          });
        }),
        map((response) => response.data),
        catchError((error) => {
          this.updateState({
            isSubmitting: false,
            error: this.getErrorMessage(error),
          });
          return throwError(() => error);
        }),
      );
  }

  // Get user's pending suggestions
  getUserPendingSuggestions(): Observable<Suggestion[]> {
    return this.http
      .get<{ data: Suggestion[] }>(`${this.apiUrl}/suggestions/pending`)
      .pipe(
        tap((response) => {
          this.updateState({
            pendingSuggestions: response.data || [],
            error: null,
          });
        }),
        map((response) => response.data),
        catchError((error) => {
          this.updateState({
            pendingSuggestions: [],
            error: this.getErrorMessage(error),
          });
          return throwError(() => error);
        }),
      );
  }

  // Approve suggestion (admin/moderator)
  approveSuggestion(suggestionId: number): Observable<{ status: string }> {
    return this.http
      .patch<{
        status: string;
      }>(`${this.apiUrl}/suggestions/${suggestionId}/approve`, {})
      .pipe(
        tap(() => {
          this.removeSuggestionFromState(suggestionId);
        }),
        catchError((error) => {
          this.updateState({
            error: this.getErrorMessage(error),
          });
          return throwError(() => error);
        }),
      );
  }

  // Reject suggestion (admin/moderator)
  rejectSuggestion(
    suggestionId: number,
    reason?: string,
  ): Observable<{ status: string }> {
    const body = reason ? { rejection_reason: reason } : {};

    return this.http
      .patch<{
        status: string;
      }>(`${this.apiUrl}/suggestions/${suggestionId}/reject`, body)
      .pipe(
        tap(() => {
          this.removeSuggestionFromState(suggestionId);
        }),
        catchError((error) => {
          this.updateState({
            error: this.getErrorMessage(error),
          });
          return throwError(() => error);
        }),
      );
  }

  // Delete suggestion (user can delete their own)
  deleteSuggestion(suggestionId: number): Observable<{ status: string }> {
    return this.http
      .delete<{ status: string }>(`${this.apiUrl}/suggestions/${suggestionId}`)
      .pipe(
        tap(() => {
          this.removeSuggestionFromState(suggestionId);
        }),
        catchError((error) => {
          this.updateState({
            error: this.getErrorMessage(error),
          });
          return throwError(() => error);
        }),
      );
  }

  // Get similar suggestions to avoid duplicates
  getSimilarSuggestions(
    abbreviation: string,
    meaning: string,
  ): Observable<Suggestion[]> {
    return this.http
      .post<{ data: Suggestion[] }>(`${this.apiUrl}/suggestions/similar`, {
        abbreviation,
        meaning,
      })
      .pipe(
        map((response) => response.data),
        catchError((error) => {
          return throwError(() => error);
        }),
      );
  }

  // Validate suggestion before submission
  validateSuggestion(
    suggestion: NewSuggestion,
  ): Observable<{ isValid: boolean; issues: string[] }> {
    return this.http
      .post<{
        isValid: boolean;
        issues: string[];
      }>(`${this.apiUrl}/suggestions/validate`, suggestion)
      .pipe(
        catchError(() => {
          const issues = this.clientSideValidation(suggestion);
          return new Observable<{ isValid: boolean; issues: string[] }>(
            (observer) => {
              observer.next({ isValid: issues.length === 0, issues });
              observer.complete();
            },
          );
        }),
      );
  }

  // Get suggestion statistics
  getSuggestionStatistics(): Observable<{
    total: number;
    pending: number;
    approved: number;
    rejected: number;
    byCategory: { category: string; count: number }[];
  }> {
    return this.http
      .get<{
        total: number;
        pending: number;
        approved: number;
        rejected: number;
        byCategory: { category: string; count: number }[];
      }>(`${this.apiUrl}/suggestions/statistics`)
      .pipe(
        catchError((error) => {
          return throwError(() => error);
        }),
      );
  }

  // Auto-suggest as user types (with debouncing)
  autoSuggest(text: string): Observable<Suggestion[]> {
    if (!text || text.length < 2) {
      return new Observable<Suggestion[]>((observer) => {
        observer.next([]);
        observer.complete();
      });
    }

    return new Observable<string>((observer) => {
      observer.next(text);
      observer.complete();
    }).pipe(
      debounceTime(300),
      distinctUntilChanged(),
      switchMap((searchText: string) =>
        this.getSuggestionsForText({ text: searchText, limit: 5 }),
      ),
    );
  }

  // Clear suggestions state
  clearSuggestions(): void {
    this.updateState({
      suggestions: [],
      isLoading: false,
      isSubmitting: false,
      error: null,
      lastQuery: '',
      pendingSuggestions: [],
    });
  }

  // Private helper methods

  // Remove suggestion from state
  private removeSuggestionFromState(suggestionId: number): void {
    const currentState = this.getCurrentState();

    this.updateState({
      suggestions: currentState.suggestions.filter(
        (s) => s.id !== suggestionId,
      ),
      pendingSuggestions: currentState.pendingSuggestions.filter(
        (s) => s.id !== suggestionId,
      ),
    });
  }

  // Client-side validation
  private clientSideValidation(suggestion: NewSuggestion): string[] {
    const issues: string[] = [];

    if (
      !suggestion.abbreviation ||
      suggestion.abbreviation.trim().length === 0
    ) {
      issues.push('skraćenica je obavezna.');
    }

    if (!suggestion.meaning || suggestion.meaning.trim().length === 0) {
      issues.push('Značenje je obavezno.');
    }

    if (!suggestion.category || suggestion.category.trim().length === 0) {
      issues.push('Kategorija je obavezna.');
    }

    if (suggestion.abbreviation && suggestion.abbreviation.length > 20) {
      issues.push('skraćenica ne smije biti duža od 20 karaktera.');
    }

    if (suggestion.meaning && suggestion.meaning.length > 200) {
      issues.push('Značenje ne smije biti duže od 200 karaktera.');
    }

    if (suggestion.description && suggestion.description.length > 1000) {
      issues.push('Opis ne smije biti duži od 1000 karaktera.');
    }

    // Check for common formatting issues
    if (
      suggestion.abbreviation &&
      !/^[A-Za-z0-9\-_.]+$/.test(suggestion.abbreviation)
    ) {
      issues.push(
        'skraćenica smije sadržavati samo slova, brojeve, crtice i tačke.',
      );
    }

    return issues;
  }

  private getErrorMessage(error: {
    error?: { message?: string };
    message?: string;
    status?: number;
  }): string {
    if (error.error?.message) {
      return error.error.message;
    }

    if (error.status === 400) {
      return 'Neispravni podaci. Molimo provjerite unos.';
    }

    if (error.status === 401) {
      return 'Morate biti ulogirani da biste predložili skraćenicu.';
    }

    if (error.status === 403) {
      return 'Nemate dozvolu za ovu akciju.';
    }

    if (error.status === 404) {
      return 'Prijedlog nije pronađen.';
    }

    if (error.status === 409) {
      return 'skraćenica već postoji ili je prethodno predložena.';
    }

    if (error.status === 429) {
      return 'Previše zahtjeva. Molimo sačekajte prije ponovnog pokušaja.';
    }

    if (error.status && error.status >= 500) {
      return 'Greška na serveru. Molimo pokušajte ponovo.';
    }

    return 'Dogodila se neočekivana greška.';
  }

  // Utility methods for UI

  // Format confidence score for display
  formatConfidenceScore(score: number): string {
    return `${Math.round(score * 100)}%`;
  }

  // Get confidence level description
  getConfidenceLevel(score: number): string {
    if (score >= 0.9) return 'Visoka pouzdanost';
    if (score >= 0.7) return 'Srednja pouzdanost';
    if (score >= 0.5) return 'Niska pouzdanost';
    return 'Vrlo niska pouzdanost';
  }

  // Get suggestion source icon
  getSourceIcon(source: string): string {
    switch (source) {
      case 'user':
        return '👤';
      case 'ai':
        return '🤖';
      case 'ml':
        return '🧠';
      default:
        return '❓';
    }
  }

  // Get status color class
  getStatusColorClass(status: string): string {
    switch (status) {
      case 'pending':
        return 'status-pending';
      case 'approved':
        return 'status-approved';
      case 'rejected':
        return 'status-rejected';
      default:
        return 'status-unknown';
    }
  }

  // Check if suggestion can be edited
  canEditSuggestion(suggestion: Suggestion, currentUserId: number): boolean {
    return (
      suggestion.user_id === currentUserId && suggestion.status === 'pending'
    );
  }

  // Check if suggestion can be deleted
  canDeleteSuggestion(
    suggestion: Suggestion,
    currentUserId: number,
    currentUserRole: string,
  ): boolean {
    return (
      suggestion.user_id === currentUserId ||
      currentUserRole === 'admin' ||
      currentUserRole === 'moderator'
    );
  }

  // Get suggestions grouped by category
  getSuggestionsGroupedByCategory(): Record<string, Suggestion[]> {
    const suggestions = this.getCurrentState().suggestions;
    const grouped: Record<string, Suggestion[]> = {};

    suggestions.forEach((suggestion) => {
      if (!grouped[suggestion.category]) {
        grouped[suggestion.category] = [];
      }
      grouped[suggestion.category].push(suggestion);
    });

    return grouped;
  }
}
