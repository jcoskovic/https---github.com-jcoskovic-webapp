import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import {
  FormBuilder,
  FormGroup,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { AuthService } from '../../services/auth.service';
import { NotificationService } from '../../services/notification.service';

@Component({
  selector: 'app-email-verification',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './email-verification.component.html',
  styleUrls: ['./email-verification.component.scss'],
})
export class EmailVerificationComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private authService = inject(AuthService);
  private notificationService = inject(NotificationService);
  private fb = inject(FormBuilder);

  isLoading = false;
  isVerifying = false;
  isResending = false;
  verificationSuccess = false;
  verificationError = '';
  resendForm: FormGroup;
  email = '';
  token = '';

  constructor() {
    this.resendForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
    });
  }

  ngOnInit(): void {
    // Get email and token from query parameters
    this.route.queryParams.subscribe((params) => {
      this.email = params['email'] || '';
      this.token = params['token'] || '';

      if (this.email) {
        this.resendForm.patchValue({ email: this.email });
      }

      // If we have both email and token, automatically verify
      if (this.email && this.token) {
        this.verifyEmail();
      }
    });
  }

  verifyEmail(): void {
    if (!this.email || !this.token) {
      this.verificationError = 'Neispravni podaci za potvrdu email adrese';
      return;
    }

    this.isVerifying = true;
    this.verificationError = '';

    this.authService
      .verifyEmail({ email: this.email, token: this.token })
      .subscribe({
        next: (response) => {
          this.isVerifying = false;
          if (response.status === 'success') {
            this.verificationSuccess = true;
            // Refresh user data to update email verification status
            this.authService.getCurrentUser().subscribe({
              next: () => {
                // User data refreshed, email verification status should be updated
                setTimeout(() => {
                  this.router.navigate(['/']);
                }, 3000);
              },
              error: () => {
                // Even if refresh fails, still redirect
                setTimeout(() => {
                  this.router.navigate(['/']);
                }, 3000);
              },
            });
          }
        },
        error: () => {
          this.isVerifying = false;
          this.verificationError = 'Greška pri potvrdi email adrese';
        },
      });
  }

  resendVerification(): void {
    if (this.resendForm.invalid) {
      return;
    }

    const email = this.resendForm.get('email')?.value;
    this.isResending = true;

    this.authService.resendVerification(email).subscribe({
      next: (response) => {
        this.isResending = false;
        if (response.status === 'success') {
          // Show success message (could use a snackbar or toast)
          alert('Link za potvrdu je ponovo poslat na vaš email');
        }
      },
      error: () => {
        this.isResending = false;
        const errorMessage = 'Greška pri slanju linka za potvrdu';
        this.notificationService.showError(errorMessage);
      },
    });
  }

  navigateToLogin(): void {
    // Refresh user data before navigating to ensure email verification status is updated
    if (this.authService.isAuthenticated()) {
      this.authService.getCurrentUser().subscribe({
        next: () => {
          this.router.navigate(['/']);
        },
        error: () => {
          this.router.navigate(['/']);
        },
      });
    } else {
      this.router.navigate(['/']);
    }
  }
}
