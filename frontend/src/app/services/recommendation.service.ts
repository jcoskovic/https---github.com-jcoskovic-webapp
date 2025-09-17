import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { BehaviorSubject, Observable, throwError, timer } from 'rxjs';
import { catchError, tap, switchMap, retryWhen, map } from 'rxjs/operators';
import { environment } from '../../environments/environment';

interface ApiResponse<T> {
  data: T;
  message?: string;
  status?: number;
}

export interface Recommendation {
  id: number;
  abbreviation: string;
  meaning: string;
  category: string;
  votes_sum?: number; // Make optional for trending
  user_vote?: 'up' | 'down' | null;
  similarity_score?: number; // Make optional for trending
  score?: number; // For trending/personalized recommendation scores
  recommendation_reason?: string; // Make optional for trending
}

export interface RecommendationState {
  recommendations: Recommendation[];
  isLoading: boolean;
  error: string | null;
  lastUpdated: Date | null;
  targetAbbreviationId: number | null;
}

@Injectable({
  providedIn: 'root',
})
export class RecommendationService {
  private http = inject(HttpClient);

  private apiUrl = environment.apiUrl;
  private recommendationStateSubject = new BehaviorSubject<RecommendationState>(
    {
      recommendations: [],
      isLoading: false,
      error: null,
      lastUpdated: null,
      targetAbbreviationId: null,
    },
  );

  public recommendationState$ = this.recommendationStateSubject.asObservable();
  // Get current recommendation state
  getCurrentState(): RecommendationState {
    return this.recommendationStateSubject.value;
  }

  private updateState(updates: Partial<RecommendationState>): void {
    const currentState = this.recommendationStateSubject.value;
    this.recommendationStateSubject.next({ ...currentState, ...updates });
  }

  // Get recommendations for a specific abbreviation
  getRecommendationsFor(abbreviationId: number): Observable<Recommendation[]> {
    this.updateState({
      isLoading: true,
      error: null,
      targetAbbreviationId: abbreviationId,
    });

    return this.http
      .get<
        ApiResponse<Recommendation[]>
      >(`${this.apiUrl}/ml/recommendations/${abbreviationId}`)
      .pipe(
        retryWhen((errors) =>
          errors.pipe(
            switchMap((error, index) => {
              // Retry up to 3 times with exponential backoff
              if (index < 3) {
                const delay = Math.pow(2, index) * 1000; // 1s, 2s, 4s
                return timer(delay);
              }
              return throwError(() => error);
            }),
          ),
        ),
        tap((response) => {
          this.updateState({
            recommendations: response.data || [],
            isLoading: false,
            error: null,
            lastUpdated: new Date(),
          });
        }),
        map((response) => response.data || []), // Return only the data array
        catchError((error) => {
          this.updateState({
            recommendations: [],
            isLoading: false,
            error: this.getErrorMessage(error),
            lastUpdated: new Date(),
          });
          return throwError(() => error);
        }),
      );
  }

  // Get general recommendations (not tied to specific abbreviation)
  getGeneralRecommendations(limit = 10): Observable<Recommendation[]> {
    this.updateState({
      isLoading: true,
      error: null,
      targetAbbreviationId: null,
    });

    return this.http
      .get<ApiResponse<Recommendation[]>>(`${this.apiUrl}/ml/trending`, {
        params: { limit: limit.toString() },
      })
      .pipe(
        retryWhen((errors) =>
          errors.pipe(
            switchMap((error, index) => {
              if (index < 3) {
                const delay = Math.pow(2, index) * 1000;
                return timer(delay);
              }
              return throwError(() => error);
            }),
          ),
        ),
        tap((response) => {
          this.updateState({
            recommendations: response.data || [],
            isLoading: false,
            error: null,
            lastUpdated: new Date(),
          });
        }),
        map((response) => response.data || []), // Return only the data array
        catchError((error) => {
          this.updateState({
            recommendations: [],
            isLoading: false,
            error: this.getErrorMessage(error),
            lastUpdated: new Date(),
          });
          return throwError(() => error);
        }),
      );
  }

  // Get personalized recommendations based on user activity
  getPersonalizedRecommendations(
    userId: number,
    limit = 10,
  ): Observable<Recommendation[]> {
    this.updateState({
      isLoading: true,
      error: null,
      targetAbbreviationId: null,
    });

    return this.http
      .get<ApiResponse<Recommendation[]>>(
        `${this.apiUrl}/ml/recommendations/personalized/${userId}`,
        {
          params: { limit: limit.toString() },
        },
      )
      .pipe(
        retryWhen((errors) =>
          errors.pipe(
            switchMap((error, index) => {
              if (index < 3) {
                const delay = Math.pow(2, index) * 1000;
                return timer(delay);
              }
              return throwError(() => error);
            }),
          ),
        ),
        tap((response) => {
          this.updateState({
            recommendations: response.data || [],
            isLoading: false,
            error: null,
            lastUpdated: new Date(),
          });
        }),
        map((response) => response.data || []), // Return only the data array
        catchError((error) => {
          this.updateState({
            recommendations: [],
            isLoading: false,
            error: this.getErrorMessage(error),
            lastUpdated: new Date(),
          });
          return throwError(() => error);
        }),
      );
  }

  // Get category-based recommendations - fallback to general trending since backend doesn't have this endpoint
  getCategoryRecommendations(
    category: string,
    limit = 10,
  ): Observable<Recommendation[]> {
    this.updateState({
      isLoading: true,
      error: null,
      targetAbbreviationId: null,
    });

    // Since backend doesn't have category-specific recommendations, use trending and filter
    return this.http
      .get<ApiResponse<Recommendation[]>>(`${this.apiUrl}/ml/trending`, {
        params: { limit: (limit * 2).toString() }, // Get more to allow for filtering
      })
      .pipe(
        retryWhen((errors) =>
          errors.pipe(
            switchMap((error, index) => {
              if (index < 3) {
                const delay = Math.pow(2, index) * 1000;
                return timer(delay);
              }
              return throwError(() => error);
            }),
          ),
        ),
        tap((response) => {
          this.updateState({
            recommendations: response.data || [],
            isLoading: false,
            error: null,
            lastUpdated: new Date(),
          });
        }),
        map((response) => response.data || []), // Return only the data array
        catchError((error) => {
          this.updateState({
            recommendations: [],
            isLoading: false,
            error: this.getErrorMessage(error),
            lastUpdated: new Date(),
          });
          return throwError(() => error);
        }),
      );
  }

  // Record user interaction with recommendation
  recordRecommendationInteraction(
    recommendationId: number,
    interactionType: 'view' | 'click' | 'vote',
  ): Observable<ApiResponse<{ success: boolean }>> {
    return this.http
      .post<ApiResponse<{ success: boolean }>>(
        `${this.apiUrl}/recommendations/${recommendationId}/interaction`,
        {
          interaction_type: interactionType,
          timestamp: new Date().toISOString(),
        },
      )
      .pipe(
        catchError((error) => {
          // Don't throw error for interaction tracking failures
          return throwError(() => error);
        }),
      );
  }

  // Clear current recommendations
  clearRecommendations(): void {
    this.updateState({
      recommendations: [],
      isLoading: false,
      error: null,
      lastUpdated: null,
      targetAbbreviationId: null,
    });
  }

  // Refresh current recommendations
  refreshRecommendations(): Observable<Recommendation[]> {
    const currentState = this.getCurrentState();

    if (currentState.targetAbbreviationId) {
      return this.getRecommendationsFor(currentState.targetAbbreviationId);
    } else {
      return this.getGeneralRecommendations();
    }
  }

  // Check if recommendations are stale (older than 5 minutes)
  areRecommendationsStale(): boolean {
    const currentState = this.getCurrentState();
    if (!currentState.lastUpdated) return true;

    const fiveMinutesAgo = new Date(Date.now() - 5 * 60 * 1000);
    return currentState.lastUpdated < fiveMinutesAgo;
  }

  // Get cached recommendations if fresh, otherwise fetch new ones
  getRecommendationsWithCache(
    abbreviationId?: number,
  ): Observable<Recommendation[]> {
    const currentState = this.getCurrentState();

    // If we have fresh recommendations for the same abbreviation, return them
    if (
      !this.areRecommendationsStale() &&
      currentState.targetAbbreviationId === abbreviationId &&
      currentState.recommendations.length > 0
    ) {
      return new Observable((observer) => {
        observer.next(currentState.recommendations);
        observer.complete();
      });
    }

    // Otherwise fetch new recommendations
    if (abbreviationId) {
      return this.getRecommendationsFor(abbreviationId);
    } else {
      return this.getGeneralRecommendations();
    }
  }

  // Filter recommendations by category
  filterByCategory(category: string): Recommendation[] {
    const currentState = this.getCurrentState();
    return currentState.recommendations.filter((rec) =>
      rec.category.toLowerCase().includes(category.toLowerCase()),
    );
  }

  // Sort recommendations by similarity score
  sortBySimilarity(): Recommendation[] {
    const currentState = this.getCurrentState();
    return [...currentState.recommendations].sort(
      (a, b) => (b.similarity_score || 0) - (a.similarity_score || 0),
    );
  }

  // Sort recommendations by vote score
  sortByVotes(): Recommendation[] {
    const currentState = this.getCurrentState();
    return [...currentState.recommendations].sort(
      (a, b) => (b.votes_sum || 0) - (a.votes_sum || 0),
    );
  }

  // Get top N recommendations
  getTopRecommendations(count: number): Recommendation[] {
    const currentState = this.getCurrentState();
    return currentState.recommendations.slice(0, count);
  }

  formatSimilarityScore(score: number): string {
    return `${Math.round(score * 100)}%`;
  }

  private getErrorMessage(error: unknown): string {
    const httpError = error as HttpErrorResponse;
    if (httpError.error?.message) {
      return httpError.error.message;
    }

    if (httpError.status === 0) {
      return 'Nema konekcije sa serverom. Molimo pokušajte ponovo.';
    }

    if (httpError.status === 404) {
      return 'Preporuke nisu dostupne.';
    }

    if (httpError.status >= 500) {
      return 'Greška na serveru. Molimo pokušajte ponovo.';
    }

    return 'Dogodila se neočekivana greška.';
  }

  // Get recommendation statistics
  getRecommendationStats(): {
    total: number;
    highSimilarity: number;
    mediumSimilarity: number;
    lowSimilarity: number;
    averageSimilarity: number;
  } {
    const recommendations = this.getCurrentState().recommendations;

    if (recommendations.length === 0) {
      return {
        total: 0,
        highSimilarity: 0,
        mediumSimilarity: 0,
        lowSimilarity: 0,
        averageSimilarity: 0,
      };
    }

    const total = recommendations.length;
    const highSimilarity = recommendations.filter(
      (r) => (r.similarity_score || 0) >= 0.8,
    ).length;
    const mediumSimilarity = recommendations.filter(
      (r) =>
        (r.similarity_score || 0) >= 0.5 && (r.similarity_score || 0) < 0.8,
    ).length;
    const lowSimilarity = recommendations.filter(
      (r) => (r.similarity_score || 0) < 0.5,
    ).length;
    const averageSimilarity =
      recommendations.reduce((sum, r) => sum + (r.similarity_score || 0), 0) /
      total;

    return {
      total,
      highSimilarity,
      mediumSimilarity,
      lowSimilarity,
      averageSimilarity,
    };
  }
}
