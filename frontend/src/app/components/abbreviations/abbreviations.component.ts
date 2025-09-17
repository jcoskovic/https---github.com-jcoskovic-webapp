import {
  Component,
  OnInit,
  OnDestroy,
  signal,
  computed,
  ChangeDetectorRef,
  inject,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import { MatDialog } from '@angular/material/dialog';

import { ApiService } from '../../services/api.service';
import type { StatsResponse } from '../../services/api.service';
import { AbbreviationService } from '../../services/abbreviation.service';
import { AuthService } from '../../services/auth.service';
import { NotificationService } from '../../services/notification.service';
import { ModalService } from '../../services/modal.service';
import { RoleHelper } from '../../enums/user-role.enum';

import { SearchService } from '../../services/search.service';
import { VotingService } from '../../services/voting.service';
import { RecommendationService } from '../../services/recommendation.service';
import { CommentService } from '../../services/comment.service';
import { ExportService } from '../../services/export.service';
import {
  SuggestionService,
  Suggestion,
} from '../../services/suggestion.service';

import {
  Abbreviation,
  Comment,
  ServiceComment,
  ApiResponse,
  PaginatedResponse,
  Recommendation,
  User,
} from '../../interfaces';
import { UserProfileComponent } from '../user-profile/user-profile.component';
import { PdfExportModalComponent } from '../pdf-export-modal/pdf-export-modal.component';

@Component({
  selector: 'app-abbreviations',
  standalone: true,
  imports: [CommonModule, FormsModule, UserProfileComponent],
  templateUrl: './abbreviations.component.html',
  styleUrls: ['./abbreviations.component.scss'],
})
export class AbbreviationsComponent implements OnInit, OnDestroy {
  private apiService = inject(ApiService);
  private abbreviationService = inject(AbbreviationService);
  authService = inject(AuthService);
  private notificationService = inject(NotificationService);
  private router = inject(Router);
  private modalService = inject(ModalService);
  private cdr = inject(ChangeDetectorRef);
  searchService = inject(SearchService);
  votingService = inject(VotingService);
  recommendationService = inject(RecommendationService);
  commentService = inject(CommentService);
  exportService = inject(ExportService);
  suggestionService = inject(SuggestionService);
  private dialog = inject(MatDialog);

  private destroy$ = new Subject<void>();

  abbreviations = signal<Abbreviation[]>([]);
  categories = signal<string[]>([]);
  stats = signal<StatsResponse | null>(null);
  currentUser = signal<User | null>(null);

  personalRecommendations = signal<Recommendation[]>([]);
  personalRecommendationsLoading = signal(false);
  showRecommendationsButton = signal(true);
  private isLoadingPersonalRecommendations = false; // Guard to prevent multiple calls
  // Prevents login prompts from showing before user info is resolved
  userInfoLoading = signal(false);

  loading = signal(false);
  error = signal<string | null>(null);

  // Pagination signali
  currentPage = signal(1);
  hasMorePages = signal(false);
  totalCount = signal(0);
  loadingMore = signal(false);

  showAddForm = false;
  // State management
  showLoginModal = false;
  showRegisterModal = false;
  showForgotPasswordModal = false;
  showUserProfile = false;
  isResendingVerification = false;
  selectedAbbreviation: Abbreviation | null = null;

  // Search popup properties
  showSearchPopup = false;
  searchResults: Abbreviation[] = [];
  currentSearchTerm = '';

  newAbbreviation = {
    abbreviation: '',
    meaning: '',
    description: '',
    category: '',
  };

  loginCredentials = {
    email: '',
    password: '',
  };

  registerCredentials = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    department: '',
  };

  forgotPasswordEmail = '';
  modalComment = '';
  selectedSortOption = 'created_at_desc'; // Default sorting option

  // Signal to force re-computation of filtered abbreviations
  sortingTrigger = signal(0);

  filteredAbbreviations = computed(() => {
    const abbrs = this.abbreviations();

    // Track sorting trigger to force re-computation
    this.sortingTrigger();

    const filters = this.searchService.getCurrentFilters();

    let filtered = abbrs;

    if (filters.searchTerm) {
      const term = filters.searchTerm.toLowerCase();
      filtered = filtered.filter(
        (abbr) =>
          abbr.abbreviation.toLowerCase().startsWith(term) ||
          abbr.meaning.toLowerCase().includes(term) ||
          (abbr.description && abbr.description.toLowerCase().includes(term)),
      );
    }

    if (filters.category) {
      filtered = filtered.filter((abbr) => abbr.category === filters.category);
    }

    filtered = this.searchService.sortItems(
      filtered,
      (a, b, sortBy, sortOrder) => {
        let comparison = 0;

        switch (sortBy) {
          case 'abbreviation':
            comparison = a.abbreviation.localeCompare(b.abbreviation);
            break;
          case 'meaning':
            comparison = a.meaning.localeCompare(b.meaning);
            break;
          case 'votes': {
            const aVotes = this.getNetVotes(a);
            const bVotes = this.getNetVotes(b);
            comparison = aVotes - bVotes;
            break;
          }
          case 'comments': {
            const aComments = a.comments?.length || a.comments_count || 0;
            const bComments = b.comments?.length || b.comments_count || 0;
            comparison = aComments - bComments;
            break;
          }
          case 'created_at':
          default:
            // For dates: positive = newer first, negative = older first
            // When sortOrder is 'desc' (najnovije prvo), we want newer dates to come first
            // When sortOrder is 'asc' (najstarije prvo), we want older dates to come first
            const aTime = new Date(a.created_at).getTime();
            const bTime = new Date(b.created_at).getTime();
            comparison = aTime - bTime; // This gives positive if a is newer than b
            break;
        }

        return sortOrder === 'asc' ? comparison : -comparison;
      },
    );

    return filtered;
  });

  displayedAbbreviations = computed(() => {
    // With Load More, we just return all loaded abbreviations
    // Filtering and sorting is now handled by backend
    return this.abbreviations();
  });

  ngOnInit() {
    // Reset personal recommendations state on init
    this.personalRecommendations.set([]);
    this.personalRecommendationsLoading.set(false);
    this.showRecommendationsButton.set(true);

    this.setupServiceSubscriptions();
    this.setupModalSubscriptions();
    this.setupVisibilityChangeListener();
    this.loadInitialData();

    this.checkForEmailVerificationUpdate();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private setupServiceSubscriptions(): void {
    this.commentService.commentState$
      .pipe(takeUntil(this.destroy$))
      .subscribe((state) => {
        if (
          this.selectedAbbreviation &&
          state.abbreviationId === this.selectedAbbreviation.id
        ) {
          this.selectedAbbreviation = {
            ...this.selectedAbbreviation,
            comments: state.comments as Comment[],
          };
        }
      });
  }

  private setupModalSubscriptions(): void {
    this.modalService.loginModal$.subscribe(() => {
      this.showLogin();
    });

    this.modalService.registerModal$.subscribe(() => {
      this.showRegister();
    });
  }

  private loadInitialData(): void {
    if (this.authService.isAuthenticated()) {
      this.userInfoLoading.set(true);
      this.loadCurrentUser();
    } else {
      this.currentUser.set(null);
      this.userInfoLoading.set(false);
    }

    // Load essential data immediately for fast page load
    this.loadAbbreviations();
    this.loadCategories();
    this.loadStats();

    // Personal recommendations will be loaded on user request via button
  }

  private checkForEmailVerificationUpdate(): void {
    // If user is authenticated but email isn't verified according to cache,
    // refresh user data to check for email verification updates
    if (
      this.authService.isAuthenticated() &&
      !this.authService.isEmailVerified()
    ) {
      // Delay the check slightly to allow for any recent verification
      setTimeout(() => {
        this.loadCurrentUser();
      }, 1000);
    }
  }

  private setupVisibilityChangeListener(): void {
    // Listen for when user returns to the tab (e.g., after clicking email verification link)
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden && this.authService.isAuthenticated()) {
        // User returned to tab, refresh user data to check for email verification
        this.loadCurrentUser();
      }
    });
  }

  loadCurrentUser() {
    this.apiService.getCurrentUser().subscribe({
      next: (response) => {
        const newUser = response.data.user;
        const currentUser = this.currentUser();

        // Check if email verification status changed
        const emailVerificationChanged =
          currentUser &&
          currentUser.email_verified_at !== newUser.email_verified_at;

        this.currentUser.set(newUser);

        this.authService.setCachedUser(newUser);

        // If email verification status changed, show notification
        if (emailVerificationChanged && newUser.email_verified_at) {
          this.notificationService.showSuccess(
            'Vaša email adresa je potvrđena! Sada možete koristiti sve funkcije.',
          );
        }

        // Personal recommendations will be loaded on user request via button

        this.userInfoLoading.set(false);
      },
      error: () => {
        this.currentUser.set(null);
        this.userInfoLoading.set(false);
      },
    });
  }

  loadAbbreviations(page: number = 1, append: boolean = false) {
    if (append) {
      this.loadingMore.set(true);
    } else {
      this.loading.set(true);
    }
    this.error.set(null);

    const params: any = {
      page,
      per_page: 10
    };

    // Get current filters if search service is available
    try {
      const currentFilters = this.searchService.getCurrentFilters();

      // Add search term if exists
      if (currentFilters?.searchTerm) {
        params.search = currentFilters.searchTerm;
      }

      // Add category if exists
      if (currentFilters?.category) {
        params.category = currentFilters.category;
      }

      // Add sorting
      if (currentFilters?.sortBy) {
        params.sort = currentFilters.sortBy;
        params.order = currentFilters.sortOrder;
      }
    } catch (error) {
      console.warn('Error getting filters:', error);
    }

    this.apiService.getAbbreviations(params).subscribe({
      next: (response: ApiResponse<PaginatedResponse<Abbreviation>>) => {
        const newData = response.data.data;

        if (append) {
          // Append new data to existing
          const currentAbbreviations = this.abbreviations();
          this.abbreviations.set([...currentAbbreviations, ...newData]);
        } else {
          // Replace data (first load)
          this.abbreviations.set(newData);
        }

        // Update pagination info
        this.currentPage.set(response.data.current_page);
        this.hasMorePages.set(response.data.current_page < response.data.last_page);
        this.totalCount.set(response.data.total);

        // Initialize voting states for new abbreviations
        this.votingService.initializeMultipleVotingStates(newData);

        this.loading.set(false);
        this.loadingMore.set(false);
      },
      error: (error) => {
        console.error('Error loading abbreviations:', error);
        this.error.set('Greška pri učitavanju skraćenica');
        this.loading.set(false);
        this.loadingMore.set(false);
      },
    });
  }

  loadCategories() {
    this.apiService.getCategories().subscribe({
      next: (response: ApiResponse<string[]>) => {
        this.categories.set(response.data);
      },
    });
  }

  loadStats() {
    this.apiService.getStats().subscribe({
      next: (response) => {
        this.stats.set(response.data);
      },
    });
  }

  loadMoreAbbreviations() {
    if (this.hasMorePages() && !this.loadingMore()) {
      const nextPage = this.currentPage() + 1;
      this.loadAbbreviations(nextPage, true);
    }
  }

  resetPaginationAndReload() {
    this.currentPage.set(1);
    this.hasMorePages.set(false);
    this.totalCount.set(0);
    this.loadAbbreviations(1, false);
  }

  onLoadRecommendationsClick() {
    this.showRecommendationsButton.set(false);
    this.loadPersonalRecommendations();
  }

  loadPersonalRecommendations() {
    const user = this.currentUser();
    if (!user) {
      this.personalRecommendations.set([]);
      return;
    }

    // Guard: prevent multiple simultaneous calls
    if (this.isLoadingPersonalRecommendations) {
      console.log('ML recommendations already loading, skipping...');
      return;
    }

    this.isLoadingPersonalRecommendations = true;
    this.personalRecommendationsLoading.set(true);
    this.recommendationService
      .getPersonalizedRecommendations(user.id, 6)
      .subscribe({
        next: (recommendations) => {
          // Limit to maximum 6 recommendations
          const limitedRecommendations = recommendations.slice(0, 6);
          this.personalRecommendations.set(limitedRecommendations);
          this.personalRecommendationsLoading.set(false);
          this.isLoadingPersonalRecommendations = false;
        },
        error: (error) => {
          // Gracefully handle ML errors - don't show error to user, just hide the loading
          console.warn('ML recommendations failed, falling back to trending:', error);
          this.personalRecommendationsLoading.set(false);
          this.personalRecommendations.set([]);
          this.isLoadingPersonalRecommendations = false;
        },
      });
  }

  // Search and filter methods
  searchAbbreviations() {
    if (!this.currentSearchTerm.trim()) {
      this.searchResults = [];
      this.showSearchPopup = false;
      return;
    }

    const term = this.currentSearchTerm.toLowerCase();
    this.searchResults = this.abbreviations().filter(abbr =>
      abbr.abbreviation.toLowerCase().startsWith(term)
    );
    this.showSearchPopup = this.searchResults.length > 0;
  }

  closeSearchPopup() {
    this.showSearchPopup = false;
  }

  selectSearchResult(abbreviation: Abbreviation) {
    this.showAbbreviationDetails(abbreviation);
    this.closeSearchPopup();
  }

  onSearchTermChange(term: string) {
    this.searchService.setSearchTerm(term);
  }

  onCategoryChange(category: string) {
    this.searchService.setCategory(category);
    this.resetPaginationAndReload();
  }

  applySorting(): void {
    const parts = this.selectedSortOption.split('_');
    const sortBy = parts.slice(0, -1).join('_'); // Everything except last part
    const order = parts[parts.length - 1] as 'asc' | 'desc'; // Last part

    this.searchService.setSorting(sortBy, order);

    // Use setTimeout(0) to ensure BehaviorSubject state update completes before API call
    // Without this, loadAbbreviations() might use old filter state instead of new sorting params
    setTimeout(() => {
      // Reset pagination and reload with new sorting
      this.resetPaginationAndReload();

      // Trigger re-computation of filteredAbbreviations
      this.sortingTrigger.set(this.sortingTrigger() + 1);
    }, 0);
  }

  // Pagination methods
  goToPage(page: number) {
    this.searchService.setCurrentPage(page);
  }



  // Voting methods using VotingService
  vote(abbreviationId: number, type: 'up' | 'down') {
    if (!this.currentUser()) {
      this.showLoginRequired();
      return;
    }

    if (!this.authService.isEmailVerified()) {
      // Try to refresh user data first in case email was recently verified
      this.loadCurrentUser();

      // Check again after a short delay
      setTimeout(() => {
        if (!this.authService.isEmailVerified()) {
          this.showEmailVerificationRequired();
        } else {
          this.performVote(abbreviationId, type);
        }
      }, 500);
      return;
    }

    this.performVote(abbreviationId, type);
  }

  private performVote(abbreviationId: number, type: 'up' | 'down') {
    const voteObservable =
      type === 'up'
        ? this.votingService.toggleUpVote(abbreviationId)
        : this.votingService.toggleDownVote(abbreviationId);

    voteObservable.subscribe({
      next: () => {
        this.notificationService.showSuccess('Glas je uspješno zabilježen');
      },
      error: (error: { error?: { email_verification_required?: boolean } }) => {
        if (error.error?.email_verification_required) {
          // Email verification required - refresh user data and show message
          this.loadCurrentUser();
          this.showEmailVerificationRequired();
        } else {
          this.notificationService.showError(
            'Greška pri glasovanju. Pokušajte ponovo.',
          );
        }
      },
    });
  }

  getUserVote(abbreviation: Abbreviation): string | null {
    const vote = this.votingService.getCurrentVote(abbreviation.id);
    return vote;
  }

  getNetVotes(abbreviation: Abbreviation): number {
    const score = this.votingService.getCurrentScore(abbreviation.id);
    return score;
  }

  private updateAbbreviationCommentCount(
    abbreviationId: number,
    increment: number,
  ): void {
    const currentAbbreviations = this.abbreviations();
    const updatedAbbreviations = currentAbbreviations.map((abbr) => {
      if (abbr.id === abbreviationId) {
        const newCount = (abbr.comments_count || 0) + increment;
        return {
          ...abbr,
          comments_count: newCount,
        };
      }
      return abbr;
    });

    this.abbreviations.set(updatedAbbreviations);
  }

  private addCommentToAbbreviation(
    abbreviationId: number,
    comment: ServiceComment,
  ): void {
    const currentAbbreviations = this.abbreviations();
    const updatedAbbreviations = currentAbbreviations.map((abbr) => {
      if (abbr.id === abbreviationId) {
        // Convert ServiceComment to Comment format for consistency
        const convertedComment: Comment = {
          id: comment.id,
          user_id: comment.user_id,
          abbreviation_id: abbreviationId,
          content: comment.content,
          created_at: comment.created_at,
          updated_at: comment.created_at, // Use created_at as fallback
          user: comment.user,
        };

        const updatedComments = [...(abbr.comments || []), convertedComment];
        return {
          ...abbr,
          comments: updatedComments,
        };
      }
      return abbr;
    });

    this.abbreviations.set(updatedAbbreviations);
  }

  // Comment methods using CommentService
  addComment(abbreviationId: number, content: string) {
    if (!this.currentUser()) {
      this.showLoginRequired();
      return;
    }

    if (!this.authService.isEmailVerified()) {
      // Try to refresh user data first in case email was recently verified
      this.loadCurrentUser();

      // Check again after a short delay
      setTimeout(() => {
        if (!this.authService.isEmailVerified()) {
          this.showEmailVerificationRequired();
        } else {
          this.performAddComment(abbreviationId, content);
        }
      }, 500);
      return;
    }

    this.performAddComment(abbreviationId, content);
  }

  private performAddComment(abbreviationId: number, content: string) {
    if (!content || !content.trim()) return;

    // Additional check for email verification to prevent unnecessary requests
    if (!this.authService.isEmailVerified()) {
      this.showEmailVerificationRequired();
      return;
    }

    this.commentService
      .addComment({
        content: content.trim(),
        abbreviation_id: abbreviationId,
      })
      .subscribe({
        next: (comment) => {
          this.notificationService.showSuccess('Komentar je uspješno dodan!');
          this.loadStats();

          this.updateAbbreviationCommentCount(abbreviationId, 1);
          this.addCommentToAbbreviation(abbreviationId, comment);
        },
        error: (error) => {
          if (error.error?.email_verification_required) {
            this.loadCurrentUser();
            this.showEmailVerificationRequired();
          } else {
            this.notificationService.showError(
              'Greška pri dodavanju komentara',
            );
          }
        },
      });
  }

  toggleComments(abbreviationId: number) {
    this.commentService.toggleCommentExpansion(abbreviationId);

    // Load comments if expanding for the first time
    if (this.commentService.isCommentExpanded(abbreviationId)) {
      this.commentService.loadCommentsFor(abbreviationId).subscribe();
    }
  }

  isCommentsExpanded(abbreviationId: number): boolean {
    return this.commentService.isCommentExpanded(abbreviationId);
  }

  // CRUD operations
  addAbbreviation() {
    if (!this.currentUser()) {
      this.showLoginRequired();
      return;
    }

    if (!this.authService.isEmailVerified()) {
      // Try to refresh user data first in case email was recently verified
      this.loadCurrentUser();

      // Check again after a short delay
      setTimeout(() => {
        if (!this.authService.isEmailVerified()) {
          this.showEmailVerificationRequired();
        } else {
          this.performAddAbbreviation();
        }
      }, 500);
      return;
    }

    this.performAddAbbreviation();
  }

  private performAddAbbreviation() {
    if (!this.newAbbreviation.abbreviation || !this.newAbbreviation.meaning) {
      this.notificationService.showError('Molimo unesite skraćenicu i značenje');
      return;
    }

    this.apiService.createAbbreviation(this.newAbbreviation).subscribe({
      next: () => {
        this.loadAbbreviations();
        this.loadCategories();
        this.loadStats();
        this.resetForm();
        this.showAddForm = false;
        this.notificationService.showSuccess('skraćenica je uspješno dodana!');
      },
      error: (error) => {
        if (error.error?.email_verification_required) {
          // Email verification required - refresh user data and show message
          this.loadCurrentUser();
          this.showEmailVerificationRequired();
        } else {
          this.error.set('Greška pri dodavanju skraćenice');
        }
      },
    });
  }

  deleteAbbreviation(abbreviation: Abbreviation) {
    if (!this.currentUser()) {
      this.showLoginRequired();
      return;
    }

    // Check permissions
    if (
      abbreviation.user_id !== this.currentUser()!.id &&
      !this.canModerate()
    ) {
      this.notificationService.showError(
        'Nemate dozvolu za brisanje ove skraćenice',
      );
      return;
    }

    const confirmDelete = confirm(
      `Jeste li sigurni da želite obrisati skraćenicu "${abbreviation.abbreviation}"?`,
    );
    if (!confirmDelete) return;

    this.apiService.deleteAbbreviation(abbreviation.id).subscribe({
      next: () => {
        this.notificationService.showSuccess('skraćenica je uspješno obrisana');
        this.loadAbbreviations();
      },
      error: (error) => {
        const message =
          error.error?.message || 'Greška prilikom brisanja skraćenice';
        this.notificationService.showError(message);
      },
    });
  }

  // Suggestion methods using SuggestionService
  onAbbreviationChange(value: string) {
    this.newAbbreviation.abbreviation = value;
    this.suggestionService.clearSuggestions();
  }

  getSuggestions() {
    if (!this.newAbbreviation.abbreviation.trim()) return;

    this.suggestionService
      .getSuggestionsForText({
        text: this.newAbbreviation.abbreviation,
      })
      .subscribe({
        next: () => {
          // Suggestions are handled by the service
        },
      });
  }

  applySuggestion(suggestion: Suggestion) {
    if (suggestion.original_meaning) {
      this.newAbbreviation.meaning = suggestion.original_meaning;
      this.newAbbreviation.description = suggestion.meaning;
    } else {
      this.newAbbreviation.meaning = suggestion.meaning;
    }
    this.newAbbreviation.category = suggestion.category;
  }

  // Export methods using ExportService
  exportToPdf() {
    if (!this.currentUser()) {
      this.showLoginRequired();
      return;
    }

    if (!this.authService.isEmailVerified()) {
      // Try to refresh user data first in case email was recently verified
      this.loadCurrentUser();

      // Check again after a short delay
      setTimeout(() => {
        if (!this.authService.isEmailVerified()) {
          this.showEmailVerificationRequired();
        } else {
          this.openPdfExportModal();
        }
      }, 500);
      return;
    }

    this.openPdfExportModal();
  }

  private openPdfExportModal() {
    const dialogRef = this.dialog.open(PdfExportModalComponent, {
      width: '700px',
      maxWidth: '95vw',
      maxHeight: '90vh',
      disableClose: false,
      autoFocus: false,
      panelClass: 'pdf-export-modal-container'
    });

    dialogRef.afterClosed().subscribe(result => {
      // Modal closed, no additional action needed
    });
  }

  // Authentication methods
  showLogin() {
    this.showLoginModal = true;
  }

  hideLogin() {
    this.showLoginModal = false;
    this.loginCredentials = {
      email: '',
      password: '',
    };
  }

  login() {
    if (!this.loginCredentials.email || !this.loginCredentials.password) {
      this.notificationService.showError('Molimo unesite email i lozinku');
      return;
    }

    this.apiService.login(this.loginCredentials).subscribe({
      next: (response) => {
        this.apiService.setToken(response.data.token);
        this.authService.setToken(response.data.token);
        this.authService.setCachedUser(response.data.user);
        this.currentUser.set(response.data.user);
        this.hideLogin();
        // ML recommendations will be loaded by the async timeout in loadInitialData
        this.notificationService.showSuccess('Uspješno ste se prijavili!');
      },
      error: (error) => {
        let errorMessage = 'Greška pri prijavi';
        if (error.error?.message) {
          errorMessage = error.error.message;
        } else if (error.status === 401) {
          errorMessage = 'Neispravni podaci za prijavu';
        }
        this.notificationService.showError(errorMessage);
      },
    });
  }

  showRegister() {
    this.showRegisterModal = true;
  }

  hideRegister() {
    this.showRegisterModal = false;
    this.registerCredentials = {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      department: '',
    };
  }

  register() {
    if (
      this.registerCredentials.password !==
      this.registerCredentials.password_confirmation
    ) {
      this.notificationService.showError('Lozinke se ne poklapaju');
      return;
    }

    if (
      !this.registerCredentials.name ||
      !this.registerCredentials.email ||
      !this.registerCredentials.password
    ) {
      this.notificationService.showError('Molimo unesite sve potrebne podatke');
      return;
    }

    if (this.registerCredentials.password.length < 8) {
      this.notificationService.showError(
        'Lozinka mora imati minimalno 8 karaktera',
      );
      return;
    }

    this.apiService.register(this.registerCredentials).subscribe({
      next: (response) => {
        this.apiService.setToken(response.data.token);
        this.authService.setToken(response.data.token);
        this.currentUser.set(response.data.user);
        this.hideRegister();

        // Show different message based on whether email verification was sent
        if (response.data.email_verification_sent) {
          this.notificationService.showSuccess(
            'Uspješna registracija! Provjerite email za potvrdu adrese.',
          );
        } else {
          this.notificationService.showSuccess(
            'Uspješna registracija! Prijavljeni ste.',
          );
        }
        this.notificationService.showSuccess(
          'Uspješna registracija! Prijavljeni ste.',
        );
      },
      error: (error) => {
        let errorMessage = 'Greška pri registraciji';
        if (error.error?.message) {
          errorMessage = error.error.message;
        }
        this.notificationService.showError(errorMessage);
      },
    });
  }

  logout() {
    this.apiService.logout().subscribe({
      next: () => {
        this.performLogout();
      },
      error: () => {
        this.performLogout();
      },
    });
  }

  resendEmailVerification() {
    const user = this.currentUser();
    if (!user || !user.email) {
      this.notificationService.showError(
        'Greška: korisničke informacije nisu dostupne',
      );
      return;
    }

    this.isResendingVerification = true;

    this.authService.resendVerification(user.email).subscribe({
      next: () => {
        this.isResendingVerification = false;
        this.notificationService.showSuccess(
          'Link za potvrdu email adrese je ponovo poslat',
        );
      },
      error: (error) => {
        this.isResendingVerification = false;
        let errorMessage = 'Greška pri slanju linka za potvrdu';
        if (error.error?.message) {
          errorMessage = error.error.message;
        }
        this.notificationService.showError(errorMessage);
      },
    });
  }

  private performLogout() {
    this.apiService.setToken('');
    this.authService.setToken('');
    this.authService.setCachedUser(null); // Clear cached user
    this.currentUser.set(null);
    this.votingService.clearVotingStates();
    this.commentService.clearComments();
    this.recommendationService.clearRecommendations();
    this.personalRecommendations.set([]); // Clear personal recommendations
    this.personalRecommendationsLoading.set(false); // Reset loading state

    // Hide any open forms/modals
    this.showAddForm = false;
    this.showUserProfile = false;
    this.selectedAbbreviation = null;

    // Reset form data
    this.resetForm();
    this.modalComment = '';

    // Clear suggestion service
    this.suggestionService.clearSuggestions();

    // Force page refresh to ensure everything is reset
    window.location.reload();
  }

  // Modal methods
  showAbbreviationDetails(abbreviation: Abbreviation) {
    this.selectedAbbreviation = abbreviation;
    this.modalComment = '';
    this.commentService.loadCommentsFor(abbreviation.id).subscribe();
  }

  hideAbbreviationDetails() {
    this.selectedAbbreviation = null;
    this.modalComment = '';
  }

  addCommentToModal() {
    if (!this.selectedAbbreviation || !this.modalComment.trim()) return;

    this.addComment(this.selectedAbbreviation.id, this.modalComment);
    this.modalComment = '';
  }

  showUserProfileModal() {
    this.showUserProfile = true;
  }

  hideUserProfileModal() {
    this.showUserProfile = false;
  }

  // Utility methods
  showLoginRequired() {
    this.notificationService.showWarning(
      'Morate se prijaviti da biste mogli glasovati i komentirati!',
    );
  }

  showEmailVerificationRequired() {
    this.notificationService.showWarning(
      'Morate potvrditi vašu email adresu prije korištenja ove funkcije. Provjerite vaš email i kliknite na link za potvrdu.',
    );
  }

  isEmailVerified(): boolean {
    const user = this.currentUser();
    return user ? !!user.email_verified_at : false;
  }

  canUseProtectedFeatures(): boolean {
    return !!this.currentUser() && this.isEmailVerified();
  }

  handleAddComment(
    abbreviationId: number,
    content: string,
    textareaRef: HTMLTextAreaElement,
  ) {
    if (this.canUseProtectedFeatures()) {
      this.addComment(abbreviationId, content);
      textareaRef.value = '';
    } else {
      this.showEmailVerificationRequired();
    }
  }

  isAdmin(): boolean {
    return this.currentUser()?.role === 'admin';
  }

  canModerate(): boolean {
    const user = this.currentUser();
    return user ? RoleHelper.canModerateContent(user.role) : false;
  }

  goToAdmin() {
    this.router.navigate(['/admin']);
  }

  resetForm() {
    this.newAbbreviation = {
      abbreviation: '',
      meaning: '',
      description: '',
      category: '',
    };
    this.suggestionService.clearSuggestions();
  }

  // Navigation helpers
  switchToRegister() {
    this.hideLogin();
    this.showRegister();
  }

  switchToLogin() {
    this.hideRegister();
    this.hideForgotPassword();
    this.showLogin();
  }

  showForgotPassword() {
    this.hideLogin();
    this.showForgotPasswordModal = true;
  }

  hideForgotPassword() {
    this.showForgotPasswordModal = false;
    this.forgotPasswordEmail = '';
  }

  sendForgotPassword() {
    if (!this.forgotPasswordEmail || !this.forgotPasswordEmail.includes('@')) {
      this.notificationService.showError('Molimo unesite validan email');
      return;
    }

    this.authService.forgotPassword(this.forgotPasswordEmail).subscribe({
      next: () => {
        this.notificationService.showSuccess(
          'Link za resetovanje lozinke je poslat na vaš email',
        );
        this.hideForgotPassword();
      },
      error: (error) => {
        let errorMessage = 'Greška pri slanju linka za resetovanje';
        if (error.error?.message) {
          errorMessage = error.error.message;
        }
        this.notificationService.showError(errorMessage);
      },
    });
  }

  // Recommendation click handlers
  onTrendingClick(trendingItem: Recommendation) {
    const existingAbbr = this.abbreviations().find(
      (a) => a.abbreviation === trendingItem.abbreviation,
    );

    if (existingAbbr) {
      this.showAbbreviationDetails(existingAbbr);
    } else {
      this.loading.set(true);
      this.apiService
        .getAbbreviations({ search: trendingItem.abbreviation })
        .subscribe({
          next: (response: ApiResponse<PaginatedResponse<Abbreviation>>) => {
            this.loading.set(false);
            if (response.data.data && response.data.data.length > 0) {
              this.showAbbreviationDetails(response.data.data[0]);
            }
          },
          error: () => {
            this.loading.set(false);
            this.notificationService.showError('Greška pri učitavanju skraćenice');
          },
        });
    }
  }

  onRecommendationClick(recommendationItem: Recommendation) {
    this.onTrendingClick(recommendationItem); // Same logic
  }

  getSuggestionTypeLabel(type: string): string {
    const labels: Record<string, string> = {
      original_meaning: '🌍 Originalno',
      croatian_translation: '🇭🇷 Hrvatski prijevod',
      croatian_meaning: '🇭🇷 Hrvatski',
      enhanced_combined: '🔗 Kombinovano',
      wikipedia_description: '📖 Wikipedia',
      english_meaning: '🇺🇸 Engleski',
      dictionary: '📚 Rječnik',
      ai_general: '🤖 AI općenito',
    };
    return labels[type] || '📝 Općenito';
  }

  // Scale score from 0-1 to 0-100 percentage
  formatScore(score: number): string {
    if (score >= 0 && score <= 1) {
      // Score is between 0-1, convert to percentage
      return `${Math.round(score * 100)}%`;
    } else if (score > 1) {
      // Score is already above 1, just round it
      return `${Math.round(score)}`;
    } else {
      // Fallback for negative or invalid scores
      return '0%';
    }
  }

  // TrackBy function for ngFor optimization
  trackByAbbreviationId(index: number, item: Abbreviation): number {
    return item.id;
  }

  debugCommentCount(abbr: Abbreviation): string {
    return `${abbr.comments_count || 0}`;
  }
}
