import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, throwError } from 'rxjs';
import { catchError, tap, map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import {
  VoteResult,
  VoteState,
  Abbreviation,
} from '../interfaces/abbreviation.interface';

@Injectable({
  providedIn: 'root',
})
export class VotingService {
  private http = inject(HttpClient);

  private apiUrl = environment.apiUrl;
  private votingStatesSubject = new BehaviorSubject<Map<number, VoteState>>(
    new Map(),
  );

  public votingStates$ = this.votingStatesSubject.asObservable();

  getVotingState(abbreviationId: number): VoteState | null {
    return this.votingStatesSubject.value.get(abbreviationId) || null;
  }

  // Initialize voting state for an abbreviation
  initializeVotingState(
    abbreviationId: number,
    currentScore: number,
    userVote: 'up' | 'down' | null,
    totalVotes = 0,
  ): void {
    const currentStates = this.votingStatesSubject.value;
    currentStates.set(abbreviationId, {
      abbreviationId,
      currentScore,
      userVote,
      totalVotes,
      isVoting: false,
    });
    this.votingStatesSubject.next(new Map(currentStates));
  }

  private updateVotingState(
    abbreviationId: number,
    updates: Partial<VoteState>,
  ): void {
    const currentStates = this.votingStatesSubject.value;
    const existingState = currentStates.get(abbreviationId);

    if (existingState) {
      currentStates.set(abbreviationId, { ...existingState, ...updates });
      this.votingStatesSubject.next(new Map(currentStates));
    }
  }

  // Vote up on an abbreviation
  voteUp(abbreviationId: number): Observable<VoteResult> {
    return this.vote(abbreviationId, 'up');
  }

  // Vote down on an abbreviation
  voteDown(abbreviationId: number): Observable<VoteResult> {
    return this.vote(abbreviationId, 'down');
  }

  // Remove vote from an abbreviation
  removeVote(abbreviationId: number): Observable<VoteResult> {
    // Note: Backend doesn't have a separate remove endpoint
    // To remove a vote, we need to send the same vote type again
    const currentVote = this.getCurrentVote(abbreviationId);
    if (!currentVote) {
      return throwError(() => new Error('No vote to remove'));
    }
    return this.vote(abbreviationId, currentVote);
  }

  // Generic vote method
  private vote(
    abbreviationId: number,
    voteType: 'up' | 'down',
  ): Observable<VoteResult> {
    // Check if already voting to prevent duplicate calls
    const currentState = this.getVotingState(abbreviationId);
    if (currentState?.isVoting) {
      return throwError(() => new Error('Vote already in progress'));
    }

    // Set voting state to loading
    this.updateVotingState(abbreviationId, { isVoting: true });

    const endpoint = `${this.apiUrl}/abbreviations/${abbreviationId}/vote`;
    const payload = { type: voteType };
    const headers = this.getAuthHeaders();

    return this.http
      .post<{
        data: { votes_sum: number; user_vote: 'up' | 'down' | null };
      }>(endpoint, payload, { headers })
      .pipe(
        tap((response) => {
          this.updateVotingState(abbreviationId, {
            currentScore: response.data.votes_sum,
            userVote: response.data.user_vote,
            isVoting: false,
          });
        }),
        catchError((error) => {
          // Reset voting state on error
          this.updateVotingState(abbreviationId, { isVoting: false });
          return throwError(() => error);
        }),
        map((response) => ({
          vote: {
            id: Date.now(),
            abbreviation_id: abbreviationId,
            user_id: 0,
            type: response.data.user_vote || voteType,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
          },
          votes_count: response.data.votes_sum,
        })),
      );
  }

  // Bulk initialize voting states for multiple abbreviations
  initializeMultipleVotingStates(abbreviations: Abbreviation[]): void {
    const currentStates = this.votingStatesSubject.value;

    abbreviations.forEach((abbr) => {
      currentStates.set(abbr.id, {
        abbreviationId: abbr.id,
        currentScore: abbr.votes_sum || 0,
        userVote: abbr.user_vote || null,
        totalVotes: abbr.votes_count || 0,
        isVoting: false,
      });
    });

    this.votingStatesSubject.next(new Map(currentStates));
  }

  // Check if user can vote (not voting in progress)
  canVote(abbreviationId: number): boolean {
    const state = this.getVotingState(abbreviationId);
    return !state?.isVoting;
  }

  // Get current vote for abbreviation
  getCurrentVote(abbreviationId: number): 'up' | 'down' | null {
    return this.getVotingState(abbreviationId)?.userVote || null;
  }

  // Get current score for abbreviation
  getCurrentScore(abbreviationId: number): number {
    return this.getVotingState(abbreviationId)?.currentScore || 0;
  }

  // Check if currently voting
  isVoting(abbreviationId: number): boolean {
    return this.getVotingState(abbreviationId)?.isVoting || false;
  }

  // Toggle vote (if up, remove; if down, make up; if none, make up)
  toggleUpVote(abbreviationId: number): Observable<VoteResult> {
    const currentVote = this.getCurrentVote(abbreviationId);

    if (currentVote === 'up') {
      return this.removeVote(abbreviationId);
    } else {
      return this.voteUp(abbreviationId);
    }
  }

  // Toggle vote (if down, remove; if up, make down; if none, make down)
  toggleDownVote(abbreviationId: number): Observable<VoteResult> {
    const currentVote = this.getCurrentVote(abbreviationId);

    if (currentVote === 'down') {
      return this.removeVote(abbreviationId);
    } else {
      return this.voteDown(abbreviationId);
    }
  }

  // Clear all voting states (useful for cleanup)
  clearVotingStates(): void {
    this.votingStatesSubject.next(new Map());
  }

  // Clear voting state for specific abbreviation
  clearVotingState(abbreviationId: number): void {
    const currentStates = this.votingStatesSubject.value;
    currentStates.delete(abbreviationId);
    this.votingStatesSubject.next(new Map(currentStates));
  }

  // Get vote statistics
  getVoteStatistics(): {
    totalVoted: number;
    upVotes: number;
    downVotes: number;
    noVotes: number;
  } {
    const states = Array.from(this.votingStatesSubject.value.values());

    return {
      totalVoted: states.length,
      upVotes: states.filter((state) => state.userVote === 'up').length,
      downVotes: states.filter((state) => state.userVote === 'down').length,
      noVotes: states.filter((state) => state.userVote === null).length,
    };
  }

  formatVoteCount(count: number): string {
    if (count === 0) return '0';
    if (count > 0) return `+${count}`;
    return count.toString();
  }

  getVoteButtonClass(abbreviationId: number, voteType: 'up' | 'down'): string {
    const currentVote = this.getCurrentVote(abbreviationId);
    const isVoting = this.isVoting(abbreviationId);

    let baseClass = `vote-btn vote-${voteType}`;

    if (currentVote === voteType) {
      baseClass += ' active';
    }

    if (isVoting) {
      baseClass += ' loading';
    }

    return baseClass;
  }

  // Get authorization headers
  private getAuthHeaders(): HttpHeaders {
    const token = localStorage.getItem('auth_token'); // Changed from 'token' to 'auth_token'
    const headers = new HttpHeaders({
      'Content-Type': 'application/json',
    });

    if (token) {
      return headers.set('Authorization', `Bearer ${token}`);
    }

    return headers;
  }
}
