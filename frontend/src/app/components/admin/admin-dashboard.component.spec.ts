import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { of, throwError } from 'rxjs';

import { AdminDashboardComponent } from './admin-dashboard.component';
import { AuthService } from '../../services/auth.service';
import { AdminService } from '../../services/admin.service';
import { NotificationService } from '../../services/notification.service';
import { Router } from '@angular/router';
import { UserRole } from '../../enums/user-role.enum';
import {
  AdminUser,
  AdminAbbreviation,
  AdminStatistics,
} from '../../interfaces/admin.interface';
import { User } from '../../interfaces/user.interface';

describe('AdminDashboardComponent', () => {
  let component: AdminDashboardComponent;
  let fixture: ComponentFixture<AdminDashboardComponent>;
  let authService: jasmine.SpyObj<AuthService>;
  let adminService: jasmine.SpyObj<AdminService>;
  let notificationService: jasmine.SpyObj<NotificationService>;
  let router: jasmine.SpyObj<Router>;

  const mockUser: User = {
    id: 1,
    name: 'Admin',
    email: 'admin@test.com',
    role: UserRole.ADMIN,
    created_at: '2025-01-01',
    updated_at: '2025-01-01',
  };

  const mockAdminUser: AdminUser = {
    id: 1,
    name: 'Admin',
    email: 'admin@test.com',
    role: UserRole.ADMIN,
    created_at: '2025-01-01',
  };

  const mockStatistics: AdminStatistics = {
    total_users: 100,
    total_abbreviations: 50,
    total_votes: 200,
    total_comments: 150,
    pending_abbreviations: 5,
    active_users_today: 10,
    top_categories: [
      { name: 'Technology', count: 25 },
      { name: 'Business', count: 15 },
    ],
  };

  const mockUsers: AdminUser[] = [
    mockAdminUser,
    {
      id: 2,
      name: 'User',
      email: 'user@test.com',
      role: UserRole.USER,
      created_at: '2025-01-01',
    },
  ];

  const mockAbbreviations: AdminAbbreviation[] = [
    {
      id: 1,
      abbreviation: 'API',
      meaning: 'Application Programming Interface',
      category: 'Technology',
      description: 'A set of protocols',
      user: {
        id: 1,
        name: 'Admin',
        email: 'admin@test.com',
      },
      votes_sum: 10,
      comments_count: 5,
      status: 'approved',
      created_at: '2025-01-01',
    },
  ];

  beforeEach(async () => {
    const authSpy = jasmine.createSpyObj('AuthService', [
      'isAdmin',
      'getCurrentUser',
      'setToken',
    ]);
    const adminSpy = jasmine.createSpyObj('AdminService', [
      'getStatistics',
      'getUsers',
      'getAllAbbreviations',
      'getPendingAbbreviations',
      'promoteUser',
      'demoteUser',
      'deleteUser',
      'deleteAbbreviation',
      'approveAbbreviation',
      'rejectAbbreviation',
    ]);
    const notificationSpy = jasmine.createSpyObj('NotificationService', [
      'showError',
      'showSuccess',
    ]);
    const routerSpy = jasmine.createSpyObj('Router', ['navigate']);

    await TestBed.configureTestingModule({
      imports: [
        AdminDashboardComponent,
        HttpClientTestingModule,
        RouterTestingModule,
      ],
      providers: [
        { provide: AuthService, useValue: authSpy },
        { provide: AdminService, useValue: adminSpy },
        { provide: NotificationService, useValue: notificationSpy },
        { provide: Router, useValue: routerSpy },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AdminDashboardComponent);
    component = fixture.componentInstance;
    authService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
    adminService = TestBed.inject(AdminService) as jasmine.SpyObj<AdminService>;
    notificationService = TestBed.inject(
      NotificationService,
    ) as jasmine.SpyObj<NotificationService>;
    router = TestBed.inject(Router) as jasmine.SpyObj<Router>;

    // Setup default mock returns
    authService.isAdmin.and.returnValue(true);
    authService.getCurrentUser.and.returnValue(
      of({
        status: 'success',
        data: { user: mockUser },
      }),
    );
    adminService.getStatistics.and.returnValue(of(mockStatistics));
    adminService.getUsers.and.returnValue(of(mockUsers));
    adminService.getAllAbbreviations.and.returnValue(of(mockAbbreviations));
    adminService.getPendingAbbreviations.and.returnValue(of(mockAbbreviations));
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should redirect non-admin users', () => {
    authService.isAdmin.and.returnValue(false);

    component.ngOnInit();

    expect(notificationService.showError).toHaveBeenCalledWith(
      'Nemate dozvolu za pristup admin panelu',
    );
    expect(router.navigate).toHaveBeenCalledWith(['/']);
  });

  it('should load current user on init', () => {
    component.ngOnInit();

    expect(authService.getCurrentUser).toHaveBeenCalled();
    expect(component.currentUser).toEqual(mockUser);
  });

  it('should load statistics on init', () => {
    component.ngOnInit();

    expect(adminService.getStatistics).toHaveBeenCalled();
    expect(component.statistics).toEqual(mockStatistics);
    expect(component.isLoadingStats).toBe(false);
  });

  it('should handle statistics loading error', () => {
    adminService.getStatistics.and.returnValue(
      throwError({ error: 'Stats error' }),
    );

    component.ngOnInit();

    expect(notificationService.showError).toHaveBeenCalledWith(
      'Greška pri učitavanju statistika',
    );
    expect(component.isLoadingStats).toBe(false);
  });

  it('should handle user loading error', () => {
    authService.getCurrentUser.and.returnValue(
      throwError({ error: 'User error' }),
    );

    component.ngOnInit();

    expect(component.currentUser).toBeNull();
  });

  it('should set active tab and load data', () => {
    component.setActiveTab('users');

    expect(component.activeTab).toBe('users');
    expect(adminService.getUsers).toHaveBeenCalled();
  });

  it('should load abbreviations when switching to abbreviations tab', () => {
    component.setActiveTab('abbreviations');

    expect(component.activeTab).toBe('abbreviations');
    expect(adminService.getAllAbbreviations).toHaveBeenCalled();
  });

  it('should load pending abbreviations when switching to moderation tab', () => {
    component.setActiveTab('moderation');

    expect(component.activeTab).toBe('moderation');
    expect(adminService.getPendingAbbreviations).toHaveBeenCalled();
  });

  it('should promote user successfully', () => {
    adminService.promoteUser.and.returnValue(of({}));
    spyOn(component, 'loadUsers' as never);

    component.promoteUser(1);

    expect(adminService.promoteUser).toHaveBeenCalledWith(1);
    expect(notificationService.showSuccess).toHaveBeenCalledWith(
      'Korisnik je uspješno unapređen',
    );
  });

  it('should handle promote user error', () => {
    adminService.promoteUser.and.returnValue(
      throwError({ error: 'Promote error' }),
    );

    component.promoteUser(1);

    expect(notificationService.showError).toHaveBeenCalledWith(
      'Greška pri unapređivanju korisnika',
    );
  });

  it('should demote user successfully', () => {
    adminService.demoteUser.and.returnValue(of({}));
    spyOn(component, 'loadUsers' as never);

    component.demoteUser(1);

    expect(adminService.demoteUser).toHaveBeenCalledWith(1);
    expect(notificationService.showSuccess).toHaveBeenCalledWith(
      'Korisnik je uspješno snižen',
    );
  });

  it('should handle demote user error', () => {
    adminService.demoteUser.and.returnValue(
      throwError({ error: 'Demote error' }),
    );

    component.demoteUser(1);

    expect(notificationService.showError).toHaveBeenCalledWith(
      'Greška pri snižavanju korisnika',
    );
  });

  it('should show confirmation for user deletion', () => {
    component.users = mockUsers;
    spyOn(component, 'showConfirmation' as never);

    component.deleteUser(1);

    expect(component['showConfirmation']).toHaveBeenCalled();
  });

  it('should delete user after confirmation', () => {
    adminService.deleteUser.and.returnValue(of({}));
    spyOn(component, 'loadUsers' as never);
    component.users = mockUsers;

    component.deleteUser(1);
    component.confirmAction();

    expect(notificationService.showSuccess).toHaveBeenCalledWith(
      'Korisnik je uspješno obrisan',
    );
  });

  it('should show confirmation for abbreviation deletion', () => {
    component.abbreviations = mockAbbreviations;
    spyOn(component, 'showConfirmation' as never);

    component.deleteAbbreviation(1);

    expect(component['showConfirmation']).toHaveBeenCalled();
  });

  it('should approve abbreviation successfully', () => {
    adminService.approveAbbreviation.and.returnValue(of({}));
    spyOn(component, 'loadPendingAbbreviations' as never);
    spyOn(component, 'loadStatistics' as never);

    component.approveAbbreviation(1);

    expect(adminService.approveAbbreviation).toHaveBeenCalledWith(1);
    expect(notificationService.showSuccess).toHaveBeenCalledWith(
      'skraćenica je odobrena',
    );
  });

  it('should reject abbreviation successfully', () => {
    adminService.rejectAbbreviation.and.returnValue(of({}));
    spyOn(component, 'loadPendingAbbreviations' as never);
    spyOn(component, 'loadStatistics' as never);

    component.rejectAbbreviation(1);

    expect(adminService.rejectAbbreviation).toHaveBeenCalledWith(1);
    expect(notificationService.showSuccess).toHaveBeenCalledWith(
      'skraćenica je odbijena',
    );
  });

  it('should navigate to homepage', () => {
    component.goToHomepage();

    expect(router.navigate).toHaveBeenCalledWith(['/']);
  });

  it('should logout successfully', () => {
    component.logout();

    expect(authService.setToken).toHaveBeenCalledWith('');
    expect(notificationService.showSuccess).toHaveBeenCalledWith(
      'Uspješno ste se odjavili',
    );
    expect(router.navigate).toHaveBeenCalledWith(['/']);
  });

  it('should filter users by search term', () => {
    component.users = mockUsers;
    component.userSearchTerm = 'Admin';

    const filtered = component.filteredUsers;

    expect(filtered.length).toBe(1);
    expect(filtered[0].name).toBe('Admin');
  });

  it('should filter abbreviations by search term and category', () => {
    component.abbreviations = mockAbbreviations;
    component.abbreviationSearchTerm = 'API';
    component.selectedCategory = 'Technology';

    const filtered = component.filteredAbbreviations;

    expect(filtered.length).toBe(1);
    expect(filtered[0].abbreviation).toBe('API');
  });

  it('should return correct role display name', () => {
    expect(component.getRoleDisplayName(UserRole.ADMIN)).toBe('Administrator');
    expect(component.getRoleDisplayName(UserRole.MODERATOR)).toBe('Moderator');
    expect(component.getRoleDisplayName(UserRole.USER)).toBe('Korisnik');
  });

  it('should check if user is admin', () => {
    expect(component.isUserAdmin(UserRole.ADMIN)).toBe(true);
    expect(component.isUserAdmin(UserRole.USER)).toBe(false);
  });

  it('should check if user is moderator', () => {
    expect(component.isUserModerator(UserRole.MODERATOR)).toBe(true);
    expect(component.isUserModerator(UserRole.USER)).toBe(false);
  });

  it('should check if user can perform actions', () => {
    component.currentUser = mockUser;

    expect(component.canPerformUserAction(UserRole.USER)).toBe(true);
    expect(component.canPerformUserAction(UserRole.MODERATOR)).toBe(true);
  });

  it('should truncate text correctly', () => {
    const longText = 'This is a very long text that should be truncated';
    const truncated = component.truncateText(longText, 20);

    expect(truncated.length).toBeLessThanOrEqual(23); // 20 + '...'
  });

  it('should format date correctly', () => {
    const date = '2025-01-01T00:00:00Z';
    const formatted = component.formatDate(date);

    expect(formatted).toBeDefined();
    expect(typeof formatted).toBe('string');
  });

  it('should close confirmation modal', () => {
    component.showConfirmModal = true;

    component.closeConfirmModal();

    expect(component.showConfirmModal).toBe(false);
    expect(component.confirmationData.title).toBe('');
    expect(component.confirmationData.message).toBe('');
  });

  it('should get confirmation modal title and message', () => {
    component.confirmationData = {
      title: 'Test Title',
      message: 'Test Message',
      action: () => {
        // Test action
      },
    };

    expect(component.confirmModalTitle).toBe('Test Title');
    expect(component.confirmModalMessage).toBe('Test Message');
  });

  it('should handle loading states correctly', () => {
    expect(component.isLoadingStats).toBe(true);
    expect(component.isLoadingUsers).toBe(false);
    expect(component.isLoadingAbbreviations).toBe(false);
    expect(component.isLoadingPending).toBe(false);
  });

  it('should initialize with default values', () => {
    expect(component.activeTab).toBe('overview');
    expect(component.currentUser).toBeNull();
    expect(component.users).toEqual([]);
    expect(component.abbreviations).toEqual([]);
    expect(component.pendingAbbreviations).toEqual([]);
    expect(component.userSearchTerm).toBe('');
    expect(component.abbreviationSearchTerm).toBe('');
    expect(component.selectedCategory).toBe('');
    expect(component.availableCategories).toEqual([]);
    expect(component.showConfirmModal).toBe(false);
  });
});
