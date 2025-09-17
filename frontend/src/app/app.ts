import { Component, signal, OnInit, inject } from '@angular/core';
import {
  RouterOutlet,
  RouterLink,
  Router,
  NavigationStart,
  NavigationEnd,
  NavigationCancel,
  NavigationError,
} from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from './services/auth.service';
import { NotificationService } from './services/notification.service';
import { UserProfileComponent } from './components/user-profile/user-profile.component';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, RouterLink, CommonModule, UserProfileComponent],
  templateUrl: './app.html',
  styleUrls: ['./app.scss'],
})
export class App implements OnInit {
  authService = inject(AuthService);
  private notificationService = inject(NotificationService);
  private router = inject(Router);

  protected readonly title = signal('abbrevio');
  showUserProfileModal = false;
  // Prevents auth-state flicker during route transitions
  authUiLoading = false;

  ngOnInit() {
    this.handleRouteTransitions();
    this.validateAuthentication();
  }

  isOnAdminPage(): boolean {
    return this.router.url.includes('/admin');
  }

  isOnModeratorPage(): boolean {
    return this.router.url.includes('/moderator');
  }

  isOnHomePage(): boolean {
    return this.router.url === '/' || this.router.url === '';
  }

  private validateAuthentication() {
    if (this.authService.isAuthenticated()) {
      this.authService.getCurrentUser().subscribe({
        next: (response) => {
          // User is authenticated and cached
          if (response.status === 'success' && response.data.user) {
            this.authService.setCachedUser(response.data.user);
          }
        },
        error: () => {
          this.authService.setToken('');
        },
      });
    }
  }

  private handleRouteTransitions() {
    this.router.events.subscribe((event) => {
      if (event instanceof NavigationStart) {
        this.authUiLoading = true;
      }

      if (
        event instanceof NavigationEnd ||
        event instanceof NavigationCancel ||
        event instanceof NavigationError
      ) {
        // Small timeout to ensure auth checks complete before toggling UI
        setTimeout(() => {
          this.authUiLoading = false;
        }, 0);
      }
    });
  }

  logout() {
    this.authService.logout().subscribe({
      next: () => {
        this.authService.setToken('');
        this.notificationService.showSuccess('Uspješno ste se odjavili');
        this.router.navigate(['/auth']);
      },
      error: () => {
        this.authService.setToken('');
        this.router.navigate(['/auth']);
      },
    });
  }

  goToHomepage() {
    this.router.navigate(['/']);
  }

  showUserProfile() {
    this.showUserProfileModal = true;
  }

  hideUserProfile() {
    this.showUserProfileModal = false;
  }

  getCurrentUser() {
    return this.authService.getCachedUser();
  }
}
