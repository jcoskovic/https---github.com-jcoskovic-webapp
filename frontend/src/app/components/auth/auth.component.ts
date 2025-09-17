import { Component, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { ApiService } from '../../services/api.service';
import { NotificationService } from '../../services/notification.service';
import { LoginRequest, RegisterRequest } from '../../interfaces/auth.interface';

@Component({
  selector: 'app-auth',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './auth.component.html',
  styleUrls: ['./auth.component.scss'],
})
export class AuthComponent {
  private authService = inject(AuthService);
  private apiService = inject(ApiService);
  private notificationService = inject(NotificationService);
  private router = inject(Router);

  showLogin = signal(true);
  showForgotPassword = signal(false);
  isLoading = signal(false);

  loginCredentials: LoginRequest = {
    email: '',
    password: '',
  };

  registerCredentials: RegisterRequest = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    department: '',
  };

  forgotPasswordEmail = '';

  // Switch between forms
  switchToRegister() {
    this.showLogin.set(false);
    this.showForgotPassword.set(false);
    this.resetForms();
  }

  switchToLogin() {
    this.showLogin.set(true);
    this.showForgotPassword.set(false);
    this.resetForms();
  }

  showForgotPasswordForm() {
    this.showLogin.set(false);
    this.showForgotPassword.set(true);
    this.resetForms();
  }

  private resetForms() {
    this.loginCredentials = { email: '', password: '' };
    this.registerCredentials = {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      department: '',
    };
    this.forgotPasswordEmail = '';
  }

  // Login method
  login() {
    if (!this.loginCredentials.email || !this.loginCredentials.password) {
      this.notificationService.showError('Molimo unesite sve potrebne podatke');
      return;
    }

    this.isLoading.set(true);

    this.apiService.login(this.loginCredentials).subscribe({
      next: (response) => {
        this.apiService.setToken(response.data.token);
        this.authService.setToken(response.data.token);
        this.authService.setCachedUser(response.data.user);

        this.notificationService.showSuccess('Uspješno ste se prijavili!');
        this.router.navigate(['/']);
        this.isLoading.set(false);
      },
      error: (error) => {
        let errorMessage = 'Greška pri prijavi';
        if (error.error?.message) {
          errorMessage = error.error.message;
        }
        this.notificationService.showError(errorMessage);
        this.isLoading.set(false);
      },
    });
  }

  // Register method
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
        'Lozinka mora imati minimalno 8 karaktera'
      );
      return;
    }

    this.isLoading.set(true);

    this.apiService.register(this.registerCredentials).subscribe({
      next: (response) => {
        this.apiService.setToken(response.data.token);
        this.authService.setToken(response.data.token);
        this.authService.setCachedUser(response.data.user);

        // Show different message based on whether email verification was sent
        if (response.data.email_verification_sent) {
          this.notificationService.showSuccess(
            'Uspješno ste se registrirali! Proverite email za potvrdu adrese.'
          );
        } else {
          this.notificationService.showSuccess(
            'Uspješno ste se registrirali i prijavili!'
          );
        }

        this.router.navigate(['/']);
        this.isLoading.set(false);
      },
      error: (error) => {
        let errorMessage = 'Greška pri registraciji';
        if (error.error?.message) {
          errorMessage = error.error.message;
        }
        this.notificationService.showError(errorMessage);
        this.isLoading.set(false);
      },
    });
  }

  // Forgot password method
  sendPasswordReset() {
    if (!this.forgotPasswordEmail) {
      this.notificationService.showError('Molimo unesite email adresu');
      return;
    }

    this.isLoading.set(true);

    this.authService.forgotPassword(this.forgotPasswordEmail).subscribe({
      next: () => {
        this.notificationService.showSuccess(
          'Link za resetovanje lozinke je poslat na vašu email adresu'
        );
        this.switchToLogin();
        this.isLoading.set(false);
      },
      error: (error) => {
        let errorMessage = 'Greška pri slanju linka';
        if (error.error?.message) {
          errorMessage = error.error.message;
        }
        this.notificationService.showError(errorMessage);
        this.isLoading.set(false);
      },
    });
  }
}
