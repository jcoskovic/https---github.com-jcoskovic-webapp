import { Component, OnInit, OnDestroy, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';

import { AuthService } from '../../services/auth.service';
import { AdminService } from '../../services/admin.service';
import { NotificationService } from '../../services/notification.service';

import {
  AdminUser,
  AdminAbbreviation,
  AdminStatistics,
  ConfirmationModalData,
  AdminTabType,
} from '../../interfaces/admin.interface';

import { UserRole, RoleHelper } from '../../enums/user-role.enum';
import { AdminFilterHelper } from '../../utils/admin-filter.helper';
import { AdminUtilsHelper } from '../../utils/admin-utils.helper';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-dashboard.component.html',
  styleUrls: ['./admin-dashboard.component.scss'],
})
export class AdminDashboardComponent implements OnInit, OnDestroy {
  private authService = inject(AuthService);
  private adminService = inject(AdminService);
  private notificationService = inject(NotificationService);
  private router = inject(Router);

  private destroy$ = new Subject<void>();

  activeTab: AdminTabType = 'overview';
  currentUser: AdminUser | null = null;

  // Data properties
  statistics: AdminStatistics = {
    total_users: 0,
    total_abbreviations: 0,
    total_votes: 0,
    total_comments: 0,
    pending_abbreviations: 0,
    active_users_today: 0,
    top_categories: [],
  };

  users: AdminUser[] = [];
  abbreviations: AdminAbbreviation[] = [];
  displayedAbbreviations: AdminAbbreviation[] = [];
  pendingAbbreviations: AdminAbbreviation[] = [];

  // Loading states
  isLoadingStats = true;
  isLoadingUsers = false;
  isLoadingAbbreviations = false;
  isLoadingPending = false;

  // Search and filters
  userSearchTerm = '';
  abbreviationSearchTerm = '';
  selectedCategory = '';
  availableCategories: string[] = [];

  // Confirmation modal
  showConfirmModal = false;
  confirmationData: ConfirmationModalData = {
    title: '',
    message: '',
    action: () => {
      // Default empty action
    },
  };

  ngOnInit(): void {
    this.initializeComponent();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private initializeComponent(): void {
    if (!this.authService.canModerate()) {
      this.notificationService.showError(
        'Nemate dozvolu za pristup admin panelu',
      );
      this.router.navigate(['/']);
      return;
    }

    this.loadCurrentUser();
    this.loadStatistics();
  }

  private loadCurrentUser(): void {
    this.authService
      .getCurrentUser()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: unknown) => {
          const authResponse = response as {
            status: string;
            data: { user: AdminUser };
          };
          if (authResponse.status === 'success' && authResponse.data.user) {
            this.currentUser = authResponse.data.user;
          }
        },
        error: () => {
          // Handle authentication error silently
        },
      });
  }
  // Tab management
  setActiveTab(tab: AdminTabType): void {
    this.activeTab = tab;

    switch (tab) {
      case 'users':
        if (this.users.length === 0) {
          this.loadUsers();
        }
        break;
      case 'abbreviations':
        if (this.abbreviations.length === 0) {
          this.loadAbbreviations();
        }
        break;
      case 'moderation':
        if (this.pendingAbbreviations.length === 0) {
          this.loadPendingAbbreviations();
        }
        break;
    }
  }

  // Search functionality
  searchAbbreviations(): void {
    this.displayedAbbreviations = AdminFilterHelper.filterAbbreviations(
      this.abbreviations,
      this.abbreviationSearchTerm,
      this.selectedCategory,
    );
  }

  // Data loading methods
  private loadStatistics(): void {
    this.isLoadingStats = true;
    this.adminService
      .getStatistics()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (stats: AdminStatistics) => {
          this.statistics = stats;
          this.isLoadingStats = false;
        },
        error: () => {
          this.notificationService.showError(
            'Greška pri učitavanju statistika',
          );
          this.isLoadingStats = false;
        },
      });
  }

  private loadUsers(): void {
    this.isLoadingUsers = true;
    this.adminService
      .getUsers()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (users: AdminUser[]) => {
          this.users = AdminFilterHelper.sortUsersByName(users);
          this.isLoadingUsers = false;
        },
        error: () => {
          this.notificationService.showError('Greška pri učitavanju korisnika');
          this.isLoadingUsers = false;
        },
      });
  }

  private loadAbbreviations(): void {
    this.isLoadingAbbreviations = true;
    this.adminService
      .getAllAbbreviations()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (abbreviations: AdminAbbreviation[]) => {
          this.abbreviations =
            AdminFilterHelper.sortAbbreviationsByDate(abbreviations);
          this.displayedAbbreviations = this.abbreviations;
          this.availableCategories =
            AdminFilterHelper.getUniqueCategories(abbreviations);
          this.isLoadingAbbreviations = false;
        },
        error: () => {
          this.notificationService.showError('Greška pri učitavanju skraćenica');
          this.isLoadingAbbreviations = false;
        },
      });
  }

  private loadPendingAbbreviations(): void {
    this.isLoadingPending = true;
    this.adminService
      .getPendingAbbreviations()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (abbreviations: AdminAbbreviation[]) => {
          this.pendingAbbreviations =
            AdminFilterHelper.sortAbbreviationsByDate(abbreviations);
          this.isLoadingPending = false;
        },
        error: () => {
          this.notificationService.showError(
            'Greška pri učitavanju skraćenica na čekanju',
          );
          this.isLoadingPending = false;
        },
      });
  }
  // User management methods
  promoteUser(userId: number): void {
    this.adminService
      .promoteUser(userId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          this.notificationService.showSuccess('Korisnik je uspješno unapređen');
          this.loadUsers();
        },
        error: () => {
          this.notificationService.showError(
            'Greška pri unapređivanju korisnika',
          );
        },
      });
  }

  demoteUser(userId: number): void {
    this.adminService
      .demoteUser(userId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          this.notificationService.showSuccess('Korisnik je uspješno snižen');
          this.loadUsers();
        },
        error: () => {
          this.notificationService.showError('Greška pri snižavanju korisnika');
        },
      });
  }

  deleteUser(userId: number): void {
    const user = this.users.find((u) => u.id === userId);
    const userName = user ? user.name : 'korisnika';

    const confirmDelete = confirm(
      `Jeste li sigurni da želite obrisati korisnika "${userName}"?`,
    );
    if (!confirmDelete) return;

    this.adminService
      .deleteUser(userId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          this.notificationService.showSuccess(
            'Korisnik je uspješno obrisan',
          );
          this.loadUsers();
        },
        error: () => {
          this.notificationService.showError(
            'Greška pri brisanju korisnika',
          );
        },
      });
  }

  // Abbreviation management methods
  deleteAbbreviation(abbreviationId: number): void {
    const abbr = this.abbreviations.find((a) => a.id === abbreviationId);
    const abbrName = abbr ? abbr.abbreviation : 'skraćenicu';

    const confirmDelete = confirm(
      `Jeste li sigurni da želite obrisati skraćenicu "${abbrName}"?`,
    );
    if (!confirmDelete) return;

    this.adminService
      .deleteAbbreviation(abbreviationId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          this.notificationService.showSuccess(
            'skraćenica je uspješno obrisana',
          );
          this.loadAbbreviations();
          this.loadStatistics();
        },
        error: () => {
          this.notificationService.showError('Greška pri brisanju skraćenice');
        },
      });
  }

  // Moderation methods
  approveAbbreviation(abbreviationId: number): void {
    this.adminService
      .approveAbbreviation(abbreviationId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          this.notificationService.showSuccess('skraćenica je odobrena');
          this.loadPendingAbbreviations();
          this.loadStatistics();
        },
        error: () => {
          this.notificationService.showError('Greška pri odobravanju skraćenice');
        },
      });
  }

  rejectAbbreviation(abbreviationId: number): void {
    this.adminService
      .rejectAbbreviation(abbreviationId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          this.notificationService.showSuccess('skraćenica je odbijena');
          this.loadPendingAbbreviations();
          this.loadStatistics();
        },
        error: () => {
          this.notificationService.showError('Greška pri odbijanju skraćenice');
        },
      });
  }
  // Navigation methods
  goToHomepage(): void {
    this.router.navigate(['/']);
  }

  logout(): void {
    this.authService.setToken('');
    this.notificationService.showSuccess('Uspješno ste se odjavili');
    this.router.navigate(['/']);
  }

  // Confirmation modal methods
  private showConfirmation(
    title: string,
    message: string,
    action: () => void,
  ): void {
    this.confirmationData = { title, message, action };
    this.showConfirmModal = true;
  }

  confirmAction(): void {
    this.confirmationData.action();
    this.closeConfirmModal();
  }

  closeConfirmModal(): void {
    this.showConfirmModal = false;
    this.confirmationData = {
      title: '',
      message: '',
      action: () => {
        // Default empty action
      },
    };
  }

  // Computed properties for filtering
  get filteredUsers(): AdminUser[] {
    return AdminFilterHelper.filterUsers(this.users, this.userSearchTerm);
  }

  get filteredAbbreviations(): AdminAbbreviation[] {
    return this.displayedAbbreviations;
  }

  // Template helper methods
  get confirmModalTitle(): string {
    return this.confirmationData.title;
  }

  get confirmModalMessage(): string {
    return this.confirmationData.message;
  }

  // Utility methods for templates
  formatDate(dateString: string): string {
    return AdminUtilsHelper.formatDate(dateString);
  }

  getRoleDisplayName(role: UserRole | string): string {
    return RoleHelper.getDisplayName(role as UserRole);
  }

  getRoleBadgeClass(role: UserRole | string): string {
    return RoleHelper.getRoleBadgeClass(role as UserRole);
  }

  isUserAdmin(role: UserRole | string): boolean {
    return RoleHelper.hasAdminPrivileges(role as UserRole);
  }

  isCurrentUserAdmin(): boolean {
    return this.authService.isAdmin();
  }

  isUserModerator(role: UserRole | string): boolean {
    return (role as UserRole) === UserRole.MODERATOR;
  }

  getStatusDisplayName(status: string): string {
    return AdminUtilsHelper.getStatusDisplayName(status);
  }

  getStatusClass(status: string): string {
    return AdminUtilsHelper.getStatusClass(status);
  }

  getVotesClass(votes: number): string {
    return AdminUtilsHelper.getVotesClass(votes);
  }

  canPerformUserAction(targetUserRole: UserRole): boolean {
    if (!this.currentUser) return false;
    return AdminUtilsHelper.canPerformUserAction(
      this.currentUser.role,
      targetUserRole,
    );
  }

  truncateText(text: string, maxLength = 50): string {
    return AdminUtilsHelper.truncateText(text, maxLength);
  }
}
