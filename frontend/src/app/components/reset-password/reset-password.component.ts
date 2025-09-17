import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { NotificationService } from '../../services/notification.service';

@Component({
  selector: 'app-reset-password',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './reset-password.component.html',
  styleUrls: ['./reset-password.component.scss'],
})
export class ResetPasswordComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private authService = inject(AuthService);
  private notificationService = inject(NotificationService);

  resetData = {
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
  };

  isSuccess = false;

  ngOnInit() {
    // Get token and email from URL params
    this.route.queryParams.subscribe((params) => {
      this.resetData.token = params['token'] || '';
      this.resetData.email = params['email'] || '';
    });
  }

  resetPassword() {
    if (
      !this.resetData.email ||
      !this.resetData.password ||
      !this.resetData.password_confirmation
    ) {
      this.notificationService.showError('Molimo unesite sve potrebne podatke');
      return;
    }

    if (this.resetData.password !== this.resetData.password_confirmation) {
      this.notificationService.showError('Lozinke se ne poklapaju');
      return;
    }

    if (this.resetData.password.length < 8) {
      this.notificationService.showError(
        'Lozinka mora imati minimalno 8 znakova',
      );
      return;
    }

    this.authService.resetPassword(this.resetData).subscribe({
      next: () => {
        this.isSuccess = true;
        this.notificationService.showSuccess('Lozinka je uspješno resetirana!');
      },
      error: () => {
        const errorMessage = 'Greška pri resetiranju lozinke';
        this.notificationService.showError(errorMessage);
      },
    });
  }

  goHome() {
    this.router.navigate(['/']);
  }
}
