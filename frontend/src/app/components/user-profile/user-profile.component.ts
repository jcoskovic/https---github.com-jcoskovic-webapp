import { Component, Input, Output, EventEmitter, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../services/auth.service';
import { NotificationService } from '../../services/notification.service';
import { User } from '../../interfaces/user.interface';

@Component({
  selector: 'app-user-profile',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './user-profile.component.html',
  styleUrls: ['./user-profile.component.scss'],
})
export class UserProfileComponent {
  private authService = inject(AuthService);
  private notificationService = inject(NotificationService);

  @Input() showModal = false;
  @Input() user: User | null = null;
  @Output() closeProfileModal = new EventEmitter<void>();
  @Output() logoutUser = new EventEmitter<void>();

  showPasswordChange = false;
  isChangingPassword = false;

  passwordData = {
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  };

  closeModal() {
    this.showPasswordChange = false;
    this.resetPasswordForm();
    this.closeProfileModal.emit();
  }

  logout() {
    this.logoutUser.emit();
    this.closeModal();
  }

  togglePasswordChange() {
    this.showPasswordChange = !this.showPasswordChange;
    if (!this.showPasswordChange) {
      this.resetPasswordForm();
    }
  }

  resetPasswordForm() {
    this.passwordData = {
      currentPassword: '',
      newPassword: '',
      confirmPassword: '',
    };
  }

  changePassword() {
    // Validate form
    if (
      !this.passwordData.currentPassword ||
      !this.passwordData.newPassword ||
      !this.passwordData.confirmPassword
    ) {
      this.notificationService.showError('Molimo unesite sve potrebne podatke');
      return;
    }

    if (this.passwordData.newPassword !== this.passwordData.confirmPassword) {
      this.notificationService.showError(
        'Nova lozinka i potvrda se ne poklapaju',
      );
      return;
    }

    if (this.passwordData.newPassword.length < 8) {
      this.notificationService.showError(
        'Nova lozinka mora imati minimalno 8 karaktera',
      );
      return;
    }

    this.isChangingPassword = true;

    this.authService
      .changePassword({
        current_password: this.passwordData.currentPassword,
        new_password: this.passwordData.newPassword,
        new_password_confirmation: this.passwordData.confirmPassword,
      })
      .subscribe({
        next: () => {
          this.notificationService.showSuccess(
            'Lozinka je uspješno promijenjena',
          );
          this.togglePasswordChange();
          this.isChangingPassword = false;
        },
        error: () => {
          const errorMessage = 'Greška pri mijenjanju lozinke';
          this.notificationService.showError(errorMessage);
          this.isChangingPassword = false;
        },
      });
  }

  getRoleDisplayName(): string {
    if (!this.user?.role) return 'Nepoznato';

    switch (this.user.role) {
      case 'admin':
        return 'Administrator';
      case 'moderator':
        return 'Moderator';
      case 'user':
        return 'Korisnik';
      default:
        return 'Nepoznato';
    }
  }

  getRoleBadgeClass(): string {
    if (!this.user?.role) return 'role-unknown';

    switch (this.user.role) {
      case 'admin':
        return 'role-admin';
      case 'moderator':
        return 'role-moderator';
      case 'user':
        return 'role-user';
      default:
        return 'role-unknown';
    }
  }

  getEmailVerificationText(): string {
    if (!this.user) return '';
    return this.user.email_verified_at ? 'Potvrđen' : 'Nije potvrđen';
  }

  getEmailVerificationClass(): string {
    if (!this.user) return 'verification-unknown';
    return this.user.email_verified_at
      ? 'verification-verified'
      : 'verification-unverified';
  }
}
