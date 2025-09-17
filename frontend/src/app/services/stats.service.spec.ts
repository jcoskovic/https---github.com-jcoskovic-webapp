import { TestBed } from '@angular/core/testing';
import {
  HttpClientTestingModule,
  HttpTestingController,
} from '@angular/common/http/testing';

import { StatsService } from './stats.service';
import { AuthService } from './auth.service';
import { apiConfig } from '../config/api.config';

describe('StatsService', () => {
  let service: StatsService;
  let httpMock: HttpTestingController;
  let authService: jasmine.SpyObj<AuthService>;

  beforeEach(() => {
    const authSpy = jasmine.createSpyObj('AuthService', ['getToken']);

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [StatsService, { provide: AuthService, useValue: authSpy }],
    });
    service = TestBed.inject(StatsService);
    httpMock = TestBed.inject(HttpTestingController);
    authService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should get stats without token', () => {
    authService.getToken.and.returnValue('');

    const mockStats = {
      total_abbreviations: 100,
      total_votes: 50,
      total_comments: 200,
      total_categories: 10,
      recent_abbreviations: 5,
    };

    service.getStats().subscribe((response) => {
      expect(response.data).toEqual(mockStats);
    });

    const req = httpMock.expectOne(
      `${apiConfig.baseUrl}${apiConfig.endpoints.stats}`,
    );
    expect(req.request.method).toBe('GET');
    expect(req.request.headers.get('Authorization')).toBeNull();
    expect(req.request.headers.get('Content-Type')).toBe('application/json');
    req.flush({ status: 'success', data: mockStats });
  });

  it('should get stats with token', () => {
    const token = 'test-token';
    authService.getToken.and.returnValue(token);

    const mockStats = {
      total_abbreviations: 100,
      total_votes: 50,
      total_comments: 200,
      total_categories: 10,
      recent_abbreviations: 5,
    };

    service.getStats().subscribe((response) => {
      expect(response.data).toEqual(mockStats);
    });

    const req = httpMock.expectOne(
      `${apiConfig.baseUrl}${apiConfig.endpoints.stats}`,
    );
    expect(req.request.method).toBe('GET');
    expect(req.request.headers.get('Authorization')).toBe(`Bearer ${token}`);
    req.flush({ status: 'success', data: mockStats });
  });

  it('should get trending abbreviations', () => {
    authService.getToken.and.returnValue('test-token');

    const mockTrending = [
      {
        id: 1,
        abbreviation: 'API',
        meaning: 'Application Programming Interface',
        score: 0.95,
      },
      {
        id: 2,
        abbreviation: 'URL',
        meaning: 'Uniform Resource Locator',
        score: 0.85,
      },
    ];

    service.getTrendingAbbreviations().subscribe((response) => {
      expect(response.data).toEqual(mockTrending);
    });

    const req = httpMock.expectOne(
      `${apiConfig.baseUrl}${apiConfig.endpoints.mlTrending}`,
    );
    expect(req.request.method).toBe('GET');
    req.flush({ status: 'success', data: mockTrending });
  });

  it('should generate export PDF URL with options', () => {
    const options = {
      format: 'detailed' as const,
      category: 'Technology',
      department: 'IT',
    };

    const url = service.getExportPdfUrl(options);

    expect(url).toContain(apiConfig.endpoints.exportPdf);
    expect(url).toContain('format=detailed');
    expect(url).toContain('category=Technology');
    expect(url).toContain('department=IT');
  });

  it('should generate export PDF URL without options', () => {
    const options = { format: 'simple' as const };

    const url = service.getExportPdfUrl(options);

    expect(url).toContain(apiConfig.endpoints.exportPdf);
    expect(url).toContain('format=simple');
    expect(url).not.toContain('category=');
    expect(url).not.toContain('department=');
  });

  it('should handle error responses', () => {
    authService.getToken.and.returnValue('');

    service.getStats().subscribe({
      next: () => fail('Expected error'),
      error: (error) => {
        expect(error.status).toBe(500);
      },
    });

    const req = httpMock.expectOne(
      `${apiConfig.baseUrl}${apiConfig.endpoints.stats}`,
    );
    req.flush(
      { message: 'Server error' },
      { status: 500, statusText: 'Internal Server Error' },
    );
  });

  it('should handle missing token gracefully', () => {
    authService.getToken.and.returnValue('');

    const mockStats = {
      total_abbreviations: 100,
      total_votes: 50,
      total_comments: 200,
      total_categories: 10,
      recent_abbreviations: 5,
    };

    service.getStats().subscribe((response) => {
      expect(response.data).toEqual(mockStats);
    });

    const req = httpMock.expectOne(
      `${apiConfig.baseUrl}${apiConfig.endpoints.stats}`,
    );
    expect(req.request.headers.get('Authorization')).toBeNull();
    req.flush({ status: 'success', data: mockStats });
  });

  it('should set correct headers', () => {
    authService.getToken.and.returnValue('test-token');

    service.getStats().subscribe();

    const req = httpMock.expectOne(
      `${apiConfig.baseUrl}${apiConfig.endpoints.stats}`,
    );
    expect(req.request.headers.get('Content-Type')).toBe('application/json');
    expect(req.request.headers.get('Accept')).toBe('application/json');
    expect(req.request.headers.get('Authorization')).toBe('Bearer test-token');
    req.flush({ status: 'success', data: {} });
  });
});
