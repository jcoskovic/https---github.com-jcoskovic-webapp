import { TestBed } from '@angular/core/testing';
import {
  HttpClientTestingModule,
  HttpTestingController,
} from '@angular/common/http/testing';
import { AuthService } from './auth.service';
import { UserRole } from '../enums/user-role.enum';
import { User } from '../interfaces/user.interface';

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;

  // Valid JWT token for testing (expires in year 2030)
  const validJwtToken =
    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IlRlc3QgVXNlciIsImlhdCI6MTUxNjIzOTAyMiwiZXhwIjoxODkzNDU2MDAwfQ.4oSWwVXxGe0WjJrT_VfvGR7A1-QnU-1IGf_dF5KnG6g';

  const mockUser: User = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    role: UserRole.USER,
    created_at: '2025-01-01',
    updated_at: '2025-01-01',
  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [AuthService],
    });
    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
    localStorage.clear();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should set and get token', () => {
    const token = 'test-token';
    service.setToken(token);
    expect(service.getToken()).toBe(token);
  });

  it('should store token in localStorage', () => {
    const token = 'test-token';
    service.setToken(token);
    expect(localStorage.getItem('auth_token')).toBe(token);
  });

  it('should remove token when setting empty string', () => {
    service.setToken('initial-token');
    service.setToken('');
    expect(localStorage.getItem('auth_token')).toBeNull();
  });

  it('should check if user is authenticated', () => {
    expect(service.isAuthenticated()).toBe(false);

    service.setToken(validJwtToken);
    expect(service.isAuthenticated()).toBe(true);
  });

  it('should check admin role correctly', () => {
    service.setCachedUser({ ...mockUser, role: UserRole.ADMIN });
    expect(service.isAdmin()).toBe(true);

    service.setCachedUser({ ...mockUser, role: UserRole.USER });
    expect(service.isAdmin()).toBe(false);
  });

  it('should check moderator role correctly', () => {
    service.setCachedUser({ ...mockUser, role: UserRole.MODERATOR });
    expect(service.isModerator()).toBe(true);

    service.setCachedUser({ ...mockUser, role: UserRole.USER });
    expect(service.isModerator()).toBe(false);
  });

  it('should check email verification status', () => {
    service.setCachedUser({ ...mockUser, email_verified_at: '2025-01-01' });
    expect(service.isEmailVerified()).toBe(true);

    service.setCachedUser({ ...mockUser, email_verified_at: null });
    expect(service.isEmailVerified()).toBe(false);
  });

  it('should get cached user', () => {
    service.setCachedUser(mockUser);
    expect(service.getCachedUser()).toEqual(mockUser);
  });

  it('should handle null cached user', () => {
    service.setCachedUser(null);
    expect(service.getCachedUser()).toBeNull();
    expect(service.isAdmin()).toBe(false);
    expect(service.isModerator()).toBe(false);
    expect(service.isEmailVerified()).toBe(false);
  });

  it('should make login HTTP request', () => {
    const loginData = {
      email: 'test@example.com',
      password: 'password123',
    };

    service.login(loginData).subscribe();

    const req = httpMock.expectOne('http://localhost:8000/api/login');
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(loginData);
    req.flush({
      status: 'success',
      data: { token: 'mock-token', user: mockUser },
    });
  });

  it('should make register HTTP request', () => {
    const registerData = {
      name: 'New User',
      email: 'newuser@example.com',
      password: 'password123',
      password_confirmation: 'password123',
    };

    service.register(registerData).subscribe();

    const req = httpMock.expectOne('http://localhost:8000/api/register');
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(registerData);
    req.flush({
      status: 'success',
      data: { token: 'mock-token', user: mockUser },
    });
  });

  it('should make logout HTTP request', () => {
    service.logout().subscribe();

    const req = httpMock.expectOne('http://localhost:8000/api/logout');
    expect(req.request.method).toBe('POST');
    req.flush({ status: 'success', data: null });
  });

  it('should make getCurrentUser HTTP request', () => {
    service.getCurrentUser().subscribe();

    const req = httpMock.expectOne('http://localhost:8000/api/me');
    expect(req.request.method).toBe('GET');
    req.flush({ status: 'success', data: { user: mockUser } });
  });

  it('should handle expired tokens', () => {
    // Create an expired token
    const expiredToken =
      'header.' +
      btoa(JSON.stringify({ exp: Math.floor(Date.now() / 1000) - 3600 })) +
      '.signature';
    service.setToken(expiredToken);
    expect(service.isAuthenticated()).toBe(false);
  });

  it('should handle invalid JWT tokens', () => {
    service.setToken('invalid-token');
    expect(service.isAuthenticated()).toBe(false); // Invalid tokens should return false
  });

  it('should load token from localStorage on initialization', () => {
    // Clear any existing token first
    service.setToken('');
    localStorage.setItem('auth_token', validJwtToken);

    // Create a new service instance by destroying and recreating the TestBed
    TestBed.resetTestingModule();
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [AuthService],
    });
    const newService = TestBed.inject(AuthService);
    expect(newService.getToken()).toBe(validJwtToken);
  });
});
