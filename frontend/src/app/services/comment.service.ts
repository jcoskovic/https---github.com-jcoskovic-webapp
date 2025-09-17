import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, throwError } from 'rxjs';
import { catchError, tap, map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import {
  ServiceComment,
  CommentState,
  NewComment,
} from '../interfaces/abbreviation.interface';
import { ApiResponse } from '../interfaces/api.interface';

@Injectable({
  providedIn: 'root',
})
export class CommentService {
  private http = inject(HttpClient);

  private apiUrl = environment.apiUrl;
  private commentStateSubject = new BehaviorSubject<CommentState>({
    comments: [],
    isLoading: false,
    isSubmitting: false,
    error: null,
    abbreviationId: null,
    expandedComments: new Set(),
    replyingTo: null,
  });

  public commentState$ = this.commentStateSubject.asObservable();

  // Get current comment state
  getCurrentState(): CommentState {
    return this.commentStateSubject.value;
  }

  private updateState(updates: Partial<CommentState>): void {
    const currentState = this.commentStateSubject.value;
    this.commentStateSubject.next({ ...currentState, ...updates });
  }

  // Load comments for abbreviation
  loadCommentsFor(abbreviationId: number): Observable<ServiceComment[]> {
    this.updateState({
      isLoading: true,
      error: null,
      abbreviationId,
    });

    return this.http
      .get<
        ApiResponse<ServiceComment[]>
      >(`${this.apiUrl}/abbreviations/${abbreviationId}/comments`)
      .pipe(
        tap((response) => {
          const comments = this.organizeComments(response.data || []);
          this.updateState({
            comments,
            isLoading: false,
            error: null,
          });
        }),
        map((response) => response.data),
        catchError((error) => {
          this.updateState({
            comments: [],
            isLoading: false,
            error: this.getErrorMessage(error),
          });
          return throwError(() => error);
        }),
      );
  }

  // Add new comment
  addComment(newComment: NewComment): Observable<ServiceComment> {
    this.updateState({ isSubmitting: true, error: null });

    const headers = this.getAuthHeaders();

    return this.http
      .post<
        ApiResponse<ServiceComment>
      >(`${this.apiUrl}/abbreviations/${newComment.abbreviation_id}/comments`, newComment, { headers })
      .pipe(
        tap((response) => {
          const comment = response.data;
          const currentState = this.getCurrentState();

          if (comment.parent_id) {
            // It's a reply - add to parent comment's replies
            const updatedComments = this.addReplyToComment(
              currentState.comments,
              comment,
            );
            this.updateState({
              comments: updatedComments,
              isSubmitting: false,
              replyingTo: null,
            });
          } else {
            // It's a top-level comment - add to the list
            this.updateState({
              comments: [...currentState.comments, comment],
              isSubmitting: false,
            });
          }
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

  updateComment(
    commentId: number,
    content: string,
  ): Observable<ServiceComment> {
    const headers = this.getAuthHeaders();

    // Note: Backend doesn't have update comment endpoint, keeping this for future implementation
    return this.http
      .put<
        ApiResponse<ServiceComment>
      >(`${this.apiUrl}/comments/${commentId}`, { content }, { headers })
      .pipe(
        tap((response) => {
          const updatedComment = response.data;
          const currentState = this.getCurrentState();
          const updatedComments = this.updateCommentInList(
            currentState.comments,
            updatedComment,
          );

          this.updateState({
            comments: updatedComments,
            error: null,
          });
        }),
        map((response) => response.data),
        catchError((error) => {
          this.updateState({
            error: this.getErrorMessage(error),
          });
          return throwError(() => error);
        }),
      );
  }

  // Delete comment
  deleteComment(commentId: number): Observable<void> {
    const headers = this.getAuthHeaders();

    return this.http
      .delete<
        ApiResponse<void>
      >(`${this.apiUrl}/comments/${commentId}`, { headers })
      .pipe(
        tap(() => {
          const currentState = this.getCurrentState();
          const updatedComments = this.removeCommentFromList(
            currentState.comments,
            commentId,
          );

          this.updateState({
            comments: updatedComments,
            error: null,
          });
        }),
        map(() => void 0),
        catchError((error) => {
          this.updateState({
            error: this.getErrorMessage(error),
          });
          return throwError(() => error);
        }),
      );
  }

  // Toggle comment expansion (for showing replies)
  toggleCommentExpansion(commentId: number): void {
    const currentState = this.getCurrentState();
    const expandedComments = new Set(currentState.expandedComments);

    if (expandedComments.has(commentId)) {
      expandedComments.delete(commentId);
    } else {
      expandedComments.add(commentId);
    }

    this.updateState({ expandedComments });
  }

  // Set which comment is being replied to
  setReplyingTo(commentId: number | null): void {
    this.updateState({ replyingTo: commentId });
  }

  // Check if comment is expanded
  isCommentExpanded(commentId: number): boolean {
    return this.getCurrentState().expandedComments?.has(commentId) || false;
  }

  // Check if currently replying to a comment
  isReplyingTo(commentId: number): boolean {
    return this.getCurrentState().replyingTo === commentId;
  }

  // Get comment count for abbreviation
  getCommentCount(): number {
    const comments = this.getCurrentState().comments;
    return this.countAllComments(comments);
  }

  // Get replies for a specific comment
  getRepliesFor(commentId: number): ServiceComment[] {
    const comments = this.getCurrentState().comments;
    const comment = this.findCommentById(comments, commentId);
    return comment?.replies || [];
  }

  // Clear comments state
  clearComments(): void {
    this.updateState({
      comments: [],
      isLoading: false,
      isSubmitting: false,
      error: null,
      abbreviationId: null,
      expandedComments: new Set(),
      replyingTo: null,
    });
  }

  // Private helper methods

  // Organize flat comment list into hierarchical structure
  private organizeComments(flatComments: ServiceComment[]): ServiceComment[] {
    const commentMap = new Map<number, ServiceComment>();
    const topLevelComments: ServiceComment[] = [];

    // First pass: create map of all comments
    flatComments.forEach((comment) => {
      comment.replies = [];
      commentMap.set(comment.id, comment);
    });

    // Second pass: organize into hierarchy
    flatComments.forEach((comment) => {
      if (comment.parent_id && commentMap.has(comment.parent_id)) {
        const parent = commentMap.get(comment.parent_id)!;
        parent.replies!.push(comment);
      } else {
        topLevelComments.push(comment);
      }
    });

    // Sort comments by creation date
    topLevelComments.sort(
      (a, b) =>
        new Date(a.created_at).getTime() - new Date(b.created_at).getTime(),
    );

    // Sort replies by creation date
    topLevelComments.forEach((comment) => {
      if (comment.replies) {
        comment.replies.sort(
          (a, b) =>
            new Date(a.created_at).getTime() - new Date(b.created_at).getTime(),
        );
      }
    });

    return topLevelComments;
  }

  // Add reply to parent comment
  private addReplyToComment(
    comments: ServiceComment[],
    reply: ServiceComment,
  ): ServiceComment[] {
    return comments.map((comment) => {
      if (comment.id === reply.parent_id) {
        return {
          ...comment,
          replies: [...(comment.replies || []), reply],
        };
      }

      if (comment.replies && comment.replies.length > 0) {
        return {
          ...comment,
          replies: this.addReplyToComment(comment.replies, reply),
        };
      }

      return comment;
    });
  }

  private updateCommentInList(
    comments: ServiceComment[],
    updatedComment: ServiceComment,
  ): ServiceComment[] {
    return comments.map((comment) => {
      if (comment.id === updatedComment.id) {
        return { ...comment, ...updatedComment };
      }

      if (comment.replies && comment.replies.length > 0) {
        return {
          ...comment,
          replies: this.updateCommentInList(comment.replies, updatedComment),
        };
      }

      return comment;
    });
  }

  // Remove comment from list
  private removeCommentFromList(
    comments: ServiceComment[],
    commentId: number,
  ): ServiceComment[] {
    return comments.filter((comment) => {
      if (comment.id === commentId) {
        return false;
      }

      if (comment.replies && comment.replies.length > 0) {
        comment.replies = this.removeCommentFromList(
          comment.replies,
          commentId,
        );
      }

      return true;
    });
  }

  // Find comment by ID in hierarchical structure
  private findCommentById(
    comments: ServiceComment[],
    commentId: number,
  ): ServiceComment | null {
    for (const comment of comments) {
      if (comment.id === commentId) {
        return comment;
      }

      if (comment.replies && comment.replies.length > 0) {
        const found = this.findCommentById(comment.replies, commentId);
        if (found) return found;
      }
    }

    return null;
  }

  // Count all comments including replies
  private countAllComments(comments: ServiceComment[]): number {
    let count = comments.length;

    comments.forEach((comment) => {
      if (comment.replies && comment.replies.length > 0) {
        count += this.countAllComments(comment.replies);
      }
    });

    return count;
  }

  private getErrorMessage(error: {
    error?: { message?: string };
    status?: number;
  }): string {
    if (error.error?.message) {
      return error.error.message;
    }

    if (error.status === 400) {
      return 'Neispravni podaci. Molimo provjerite sadržaj komentara.';
    }

    if (error.status === 401) {
      return 'Morate biti ulogirani da biste komentarisali.';
    }

    if (error.status === 403) {
      return 'Nemate dozvolu za ovu akciju.';
    }

    if (error.status === 404) {
      return 'Komentar ili skraćenica nisu pronađeni.';
    }

    if (error.status && error.status >= 500) {
      return 'Greška na serveru. Molimo pokušajte ponovo.';
    }

    return 'Dogodila se neočekivana greška.';
  }

  // Utility methods for UI

  // Format comment date for display
  formatCommentDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffMinutes < 1) {
      return 'Upravo sada';
    } else if (diffMinutes < 60) {
      return `Prije ${diffMinutes} ${diffMinutes === 1 ? 'minut' : 'minuta'}`;
    } else if (diffHours < 24) {
      return `Prije ${diffHours} ${diffHours === 1 ? 'sat' : 'sati'}`;
    } else if (diffDays < 7) {
      return `Prije ${diffDays} ${diffDays === 1 ? 'dan' : 'dana'}`;
    } else {
      return date.toLocaleDateString('sr-Latn-RS');
    }
  }

  // Check if user can edit comment
  canEditComment(comment: ServiceComment, currentUserId: number): boolean {
    return comment.user?.id === currentUserId;
  }

  // Check if user can delete comment
  canDeleteComment(
    comment: ServiceComment,
    currentUserId: number,
    currentUserRole: string,
  ): boolean {
    return (
      comment.user?.id === currentUserId ||
      currentUserRole === 'admin' ||
      currentUserRole === 'moderator'
    );
  }

  // Get comment depth (for indentation)
  getCommentDepth(
    comment: ServiceComment,
    comments: ServiceComment[],
    depth = 0,
  ): number {
    if (!comment.parent_id) return depth;

    const parent = this.findCommentById(comments, comment.parent_id);
    if (!parent) return depth;

    return this.getCommentDepth(parent, comments, depth + 1);
  }

  // Get authorization headers
  private getAuthHeaders(): HttpHeaders {
    const token = localStorage.getItem('auth_token');
    const headers = new HttpHeaders({
      'Content-Type': 'application/json',
    });

    if (token) {
      return headers.set('Authorization', `Bearer ${token}`);
    }

    return headers;
  }
}
