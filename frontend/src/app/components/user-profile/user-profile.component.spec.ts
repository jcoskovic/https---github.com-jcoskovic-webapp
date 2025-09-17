import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { FormsModule } from '@angular/forms';
import { of, throwError } from 'rxjs';

import { UserProfileComponent } from './user-profile.component';
import { AuthService } from '../../services/auth.service';
import { NotificationService } from '../../services/notification.service';
import { UserRole } from '../../enums/user-role.enum';

describe('UserProfileComponent', () => {
  let component: UserProfileComponent;
  let fixture: ComponentFixture<UserProfileComponent>;
  let authService: jasmine.SpyObj<AuthService>;
  let notificationService: jasmine.SpyObj<NotificationService>;

  const mockUser = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    department: 'IT',
    role: UserRole.USER,
    email_verified_at: '2024-01-01T00:00:00Z',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  };

  beforeEach(async () => {
    const authSpy = jasmine.createSpyObj('AuthService', ['changePassword']);
    const notificationSpy = jasmine.createSpyObj('NotificationService', [
      'showSuccess',
      'showError',
    ]);

    await TestBed.configureTestingModule({
      imports: [UserProfileComponent, HttpClientTestingModule, FormsModule],
      providers: [
        { provide: AuthService, useValue: authSpy },
        { provide: NotificationService, useValue: notificationSpy },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(UserProfileComponent);
    component = fixture.componentInstance;
    authService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
    notificationService = TestBed.inject(
      NotificationService,
    ) as jasmine.SpyObj<NotificationService>;

    component.user = mockUser;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should close modal and emit event', () => {
    spyOn(component.closeProfileModal, 'emit');
    component.showPasswordChange = true;
    component.passwordData.currentPassword = 'test';

    component.closeModal();

    expect(component.showPasswordChange).toBe(false);
    expect(component.passwordData.currentPassword).toBe('');
    expect(component.closeProfileModal.emit).toHaveBeenCalled();
  });

  it('should logout and emit event', () => {
    spyOn(component.logoutUser, 'emit');
    spyOn(component, 'closeModal');

    component.logout();

    expect(component.logoutUser.emit).toHaveBeenCalled();
    expect(component.closeModal).toHaveBeenCalled();
  });

  it('should toggle password change form', () => {
    expect(component.showPasswordChange).toBe(false);

    component.togglePasswordChange();
    expect(component.showPasswordChange).toBe(true);

    component.passwordData.currentPassword = 'test';
    component.togglePasswordChange();
    expect(component.showPasswordChange).toBe(false);
    expect(component.passwordData.currentPassword).toBe('');
  });

  it('should reset password form', () => {
    component.passwordData = {
      currentPassword: 'old',
      newPassword: 'new',
      confirmPassword: 'confirm',
    };

    component.resetPasswordForm();

    expect(component.passwordData.currentPassword).toBe('');
    expect(component.passwordData.newPassword).toBe('');
    expect(component.passwordData.confirmPassword).toBe('');
  });

  it('should validate password change form - missing data', () => {
    component.passwordData = {
      currentPassword: '',
      newPassword: 'password123',
      confirmPassword: 'password123',
    };

    component.changePassword();

    expect(notificationService.showError).toHaveBeenCalledWith(
      'Molimo unesite sve potrebne podatke',
    );
    expect(authService.changePassword).not.toHaveBeenCalled();
  });

  it('should validate password confirmation mismatch', () => {
    component.passwordData = {
      currentPassword: 'oldpassword',
      newPassword: 'newpassword123',
      confirmPassword: 'different',
    };

    component.changePassword();

    expect(notificationService.showError).toHaveBeenCalledWith(
      'Nova lozinka i potvrda se ne poklapaju',
    );
    expect(authService.changePassword).not.toHaveBeenCalled();
  });

  it('should validate minimum password length', () => {
    component.passwordData = {
      currentPassword: 'oldpassword',
      newPassword: '123',
      confirmPassword: '123',
    };

    component.changePassword();

    expect(notificationService.showError).toHaveBeenCalledWith(
      'Nova lozinka mora imati minimalno 8 karaktera',
    );
    expect(authService.changePassword).not.toHaveBeenCalled();
  });

  it('should change password successfully', () => {
    authService.changePassword.and.returnValue(
      of({
        status: 'success',
        data: null,
      }),
    );
    spyOn(component, 'togglePasswordChange');

    component.passwordData = {
      currentPassword: 'oldpassword',
      newPassword: 'newpassword123',
      confirmPassword: 'newpassword123',
    };

    component.changePassword();

    expect(authService.changePassword).toHaveBeenCalledWith({
      current_password: 'oldpassword',
      new_password: 'newpassword123',
      new_password_confirmation: 'newpassword123',
    });
    expect(notificationService.showSuccess).toHaveBeenCalledWith(
      'Lozinka je uspješno promijenjena',
    );
    expect(component.togglePasswordChange).toHaveBeenCalled();
    expect(component.isChangingPassword).toBe(false);
  });

  it('should handle password change error', () => {
    authService.changePassword.and.returnValue(
      throwError(() => ({ error: { message: 'Wrong password' } })),
    );

    component.passwordData = {
      currentPassword: 'wrongpassword',
      newPassword: 'newpassword123',
      confirmPassword: 'newpassword123',
    };
    component.isChangingPassword = true;

    component.changePassword();

    expect(notificationService.showError).toHaveBeenCalledWith(
      'Greška pri mijenjanju lozinke',
    );
    expect(component.isChangingPassword).toBe(false);
  });

  it('should get role display name for admin', () => {
    component.user = { ...mockUser, role: UserRole.ADMIN };
    expect(component.getRoleDisplayName()).toBe('Administrator');
  });

  it('should get role display name for moderator', () => {
    component.user = { ...mockUser, role: UserRole.MODERATOR };
    expect(component.getRoleDisplayName()).toBe('Moderator');
  });

  it('should get role display name for user', () => {
    component.user = { ...mockUser, role: UserRole.USER };
    expect(component.getRoleDisplayName()).toBe('Korisnik');
  });

  it('should get role display name for unknown role', () => {
    component.user = { ...mockUser, role: 'unknown' as never };
    expect(component.getRoleDisplayName()).toBe('Nepoznato');
  });

  it('should get role display name when user is null', () => {
    component.user = null;
    expect(component.getRoleDisplayName()).toBe('Nepoznato');
  });

  it('should get role badge class for admin', () => {
    component.user = { ...mockUser, role: UserRole.ADMIN };
    expect(component.getRoleBadgeClass()).toBe('role-admin');
  });

  it('should get role badge class for moderator', () => {
    component.user = { ...mockUser, role: UserRole.MODERATOR };
    expect(component.getRoleBadgeClass()).toBe('role-moderator');
  });

  it('should get role badge class for user', () => {
    component.user = { ...mockUser, role: UserRole.USER };
    expect(component.getRoleBadgeClass()).toBe('role-user');
  });

  it('should get role badge class for unknown role', () => {
    component.user = { ...mockUser, role: 'unknown' as never };
    expect(component.getRoleBadgeClass()).toBe('role-unknown');
  });

  it('should get role badge class when user is null', () => {
    component.user = null;
    expect(component.getRoleBadgeClass()).toBe('role-unknown');
  });

  it('should get email verification text for verified email', () => {
    component.user = mockUser;
    expect(component.getEmailVerificationText()).toBe('Potvrđen');
  });

  it('should get email verification text for unverified email', () => {
    component.user = { ...mockUser, email_verified_at: null };
    expect(component.getEmailVerificationText()).toBe('Nije potvrđen');
  });

  it('should get email verification text when user is null', () => {
    component.user = null;
    expect(component.getEmailVerificationText()).toBe('');
  });

  it('should get email verification class for verified email', () => {
    component.user = mockUser;
    expect(component.getEmailVerificationClass()).toBe('verification-verified');
  });

  it('should get email verification class for unverified email', () => {
    component.user = { ...mockUser, email_verified_at: null };
    expect(component.getEmailVerificationClass()).toBe(
      'verification-unverified',
    );
  });

  it('should get email verification class when user is null', () => {
    component.user = null;
    expect(component.getEmailVerificationClass()).toBe('verification-unknown');
  });

  it('should set loading state during password change', () => {
    authService.changePassword.and.returnValue(
      of({
        status: 'success',
        data: null,
      }),
    );

    component.passwordData = {
      currentPassword: 'oldpassword',
      newPassword: 'newpassword123',
      confirmPassword: 'newpassword123',
    };

    expect(component.isChangingPassword).toBe(false);

    component.changePassword();

    // Loading state should be reset after success
    expect(component.isChangingPassword).toBe(false);
  });

  it('should handle input properties', () => {
    component.showModal = true;
    component.user = mockUser;

    expect(component.showModal).toBe(true);
    expect(component.user).toEqual(
      jasmine.objectContaining({
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
      }),
    );
  });
});
