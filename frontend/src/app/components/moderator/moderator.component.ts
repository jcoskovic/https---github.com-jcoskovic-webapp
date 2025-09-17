import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { NotificationService } from '../../services/notification.service';
import { Router } from '@angular/router';
import { User } from '../../interfaces/user.interface';

interface ModeratorStats {
  total_abbreviations: number;
  pending_abbreviations: number;
  total_users: number;
  recent_activity: number;
  top_categories: Array<{ name: string; count: number }>;
}

interface PendingAbbreviation {
  id: number;
  abbreviation: string;
  meaning: string;
  category: string;
  user: {
    name: string;
    email: string;
  };
  created_at: string;
  status: string;
}

@Component({
  selector: 'app-moderator',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './moderator.component.html',
  styleUrls: ['./moderator.component.scss']
})
export class ModeratorComponent implements OnInit {
  stats: ModeratorStats | null = null;
  pendingAbbreviations: PendingAbbreviation[] = [];
  allAbbreviations: PendingAbbreviation[] = [];
  displayedAbbreviations: PendingAbbreviation[] = [];
  isLoading = false;
  error: string | null = null;
  activeTab: 'overview' | 'abbreviations' | 'moderation' = 'overview';
  currentUser: User | null = null;
  abbreviationSearchTerm = '';

  constructor(
    private apiService: ApiService,
    private authService: AuthService,
    private notificationService: NotificationService,
    private router: Router
  ) { }

  ngOnInit() {
    this.checkModeratorAccess();
    this.loadCurrentUser();
    this.loadDashboardData();
  }

  checkModeratorAccess() {
    const user = this.authService.getCachedUser();
    if (!user || (!this.authService.isModerator() && !this.authService.isAdmin())) {
      this.notificationService.showError('Nemate dozvolu za pristup moderator panelu');
      this.router.navigate(['/']);
      return;
    }
  }

  loadCurrentUser() {
    this.currentUser = this.authService.getCachedUser();
    if (!this.currentUser) {
      this.apiService.getCurrentUser().subscribe({
        next: (response) => {
          this.currentUser = response.data.user;
          this.authService.setCachedUser(this.currentUser);
        },
        error: (error) => {
          console.error('Error loading current user:', error);
        }
      });
    }
  }

  loadDashboardData() {
    this.isLoading = true;
    this.error = null;

    // Load statistics
    this.apiService.getModeratorStatistics().subscribe({
      next: (response) => {
        this.stats = response.data;
      },
      error: (error: any) => {
        console.error('Error loading stats:', error);
        this.error = 'Greška pri učitavanju statistika';
      }
    });

    // Load pending abbreviations
    this.apiService.getPendingAbbreviations().subscribe({
      next: (response) => {
        this.pendingAbbreviations = response.data;
        this.isLoading = false;
      },
      error: (error: any) => {
        console.error('Error loading pending abbreviations:', error);
        this.error = 'Greška pri učitavanju skraćenica na čekanju';
        this.isLoading = false;
      }
    });
  }

  loadAllAbbreviations() {
    if (this.allAbbreviations.length > 0) return;

    this.isLoading = true;
    this.apiService.getModeratorAbbreviations().subscribe({
      next: (response) => {
        this.allAbbreviations = response.data;
        this.displayedAbbreviations = response.data; // Prikaži sve na početku
        this.isLoading = false;
      },
      error: (error: any) => {
        console.error('Error loading all abbreviations:', error);
        this.error = 'Greška pri učitavanju svih skraćenica';
        this.isLoading = false;
      }
    });
  }

  approveAbbreviation(abbreviation: PendingAbbreviation) {
    this.apiService.approveAbbreviation(abbreviation.id).subscribe({
      next: () => {
        this.notificationService.showSuccess(`skraćenica "${abbreviation.abbreviation}" je odobrena`);
        this.pendingAbbreviations = this.pendingAbbreviations.filter(a => a.id !== abbreviation.id);
        this.loadDashboardData(); // Refresh stats
      },
      error: (error: any) => {
        console.error('Error approving abbreviation:', error);
        this.notificationService.showError('Greška pri odobravanju skraćenice');
      }
    });
  }

  rejectAbbreviation(abbreviation: PendingAbbreviation) {
    this.apiService.rejectAbbreviation(abbreviation.id).subscribe({
      next: () => {
        this.notificationService.showSuccess(`skraćenica "${abbreviation.abbreviation}" je odbijena`);
        this.pendingAbbreviations = this.pendingAbbreviations.filter(a => a.id !== abbreviation.id);
        this.loadDashboardData(); // Refresh stats
      },
      error: (error: any) => {
        console.error('Error rejecting abbreviation:', error);
        this.notificationService.showError('Greška pri odbijanju skraćenice');
      }
    });
  }

  setActiveTab(tab: 'overview' | 'abbreviations' | 'moderation') {
    this.activeTab = tab;
    if (tab === 'abbreviations') {
      this.loadAllAbbreviations();
    }
  }

  goToAbbreviationsTab() {
    this.setActiveTab('abbreviations');
  }

  goToModerationTab() {
    this.setActiveTab('moderation');
  }

  goToHomepage() {
    this.router.navigate(['/']);
  }

  logout() {
    this.authService.logout().subscribe({
      next: () => {
        localStorage.removeItem('auth_token');
        this.notificationService.showSuccess('Uspješno ste se odjavili');
        this.router.navigate(['/auth']);
      },
      error: (error) => {
        console.error('Logout error:', error);
        // Force logout even if API call fails
        localStorage.removeItem('auth_token');
        this.router.navigate(['/auth']);
      }
    });
  }

  getRoleDisplayName(role: string): string {
    switch (role) {
      case 'admin': return 'Administrator';
      case 'moderator': return 'Moderator';
      case 'user': return 'Korisnik';
      default: return role;
    }
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('hr-HR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  getStatusClass(status: string): string {
    switch (status) {
      case 'pending': return 'status-pending';
      case 'approved': return 'status-approved';
      case 'rejected': return 'status-rejected';
      default: return 'status-unknown';
    }
  }

  getStatusText(status: string): string {
    switch (status) {
      case 'pending': return 'Na čekanju';
      case 'approved': return 'Odobreno';
      case 'rejected': return 'Odbijeno';
      default: return status;
    }
  }

  get filteredAbbreviations(): PendingAbbreviation[] {
    return this.displayedAbbreviations;
  }

  searchAbbreviations() {
    if (!this.abbreviationSearchTerm.trim()) {
      this.displayedAbbreviations = this.allAbbreviations;
    } else {
      const searchTerm = this.abbreviationSearchTerm.toLowerCase();
      this.displayedAbbreviations = this.allAbbreviations.filter(abbreviation =>
        abbreviation.abbreviation.toLowerCase().startsWith(searchTerm)
      );
    }
  }
}
