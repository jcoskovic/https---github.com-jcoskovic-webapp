import { Routes, CanActivateFn } from '@angular/router';
import { AbbreviationsComponent } from './components/abbreviations/abbreviations.component';
import { AdminDashboardComponent } from './components/admin/admin-dashboard.component';
import { ModeratorComponent } from './components/moderator/moderator.component';
import { ResetPasswordComponent } from './components/reset-password/reset-password.component';
import { EmailVerificationComponent } from './components/email-verification/email-verification.component';
import { AuthComponent } from './components/auth/auth.component';
import { AuthGuard } from './guards/auth.guard';
import { NoAuthGuard } from './guards/no-auth.guard';
import { RoleHelper } from './enums/user-role.enum';
import { inject } from '@angular/core';
import { AuthService } from './services/auth.service';
import { ApiService } from './services/api.service';
import { Router } from '@angular/router';
import { map, catchError, of } from 'rxjs';

// Admin guard function that checks for admin privileges only
export const adminGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const apiService = inject(ApiService);
  const router = inject(Router);

  // If not authenticated, redirect to home
  if (!authService.isAuthenticated()) {
    router.navigate(['/']);
    return false;
  }

  // Get current user from API to ensure we have fresh data
  return apiService.getCurrentUser().pipe(
    map((response) => {
      const user = response.data.user;
      if (user && RoleHelper.hasAdminPrivileges(user.role)) {
        return true;
      } else {
        router.navigate(['/']);
        return false;
      }
    }),
    catchError(() => {
      router.navigate(['/']);
      return of(false);
    }),
  );
};

// Moderator guard function that checks for moderator or admin privileges
export const moderatorGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const apiService = inject(ApiService);
  const router = inject(Router);

  // If not authenticated, redirect to home
  if (!authService.isAuthenticated()) {
    router.navigate(['/']);
    return false;
  }

  // Get current user from API to ensure we have fresh data
  return apiService.getCurrentUser().pipe(
    map((response) => {
      const user = response.data.user;
      if (user && RoleHelper.canModerateContent(user.role)) {
        return true;
      } else {
        router.navigate(['/']);
        return false;
      }
    }),
    catchError(() => {
      router.navigate(['/']);
      return of(false);
    }),
  );
};

export const routes: Routes = [
  { 
    path: '', 
    component: AbbreviationsComponent,
    canActivate: [AuthGuard]
  },
  {
    path: 'auth',
    component: AuthComponent,
    canActivate: [NoAuthGuard]
  },
  {
    path: 'admin',
    component: AdminDashboardComponent,
    canActivate: [adminGuard],
  },
  {
    path: 'moderator',
    component: ModeratorComponent,
    canActivate: [moderatorGuard],
  },
  { 
    path: 'reset-password', 
    component: ResetPasswordComponent,
    canActivate: [NoAuthGuard]
  },
  { 
    path: 'verify-email', 
    component: EmailVerificationComponent 
  },
  { path: '**', redirectTo: 'auth' },
];
