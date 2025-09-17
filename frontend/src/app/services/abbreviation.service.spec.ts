import { TestBed } from '@angular/core/testing';
import {
  HttpClientTestingModule,
  HttpTestingController,
} from '@angular/common/http/testing';
import { AbbreviationService } from './abbreviation.service';
import { AuthService } from './auth.service';
import { apiConfig } from '../config/api.config';

describe('AbbreviationService', () => {
  let service: AbbreviationService;
  let httpMock: HttpTestingController;
  let authService: jasmine.SpyObj<AuthService>;

  beforeEach(() => {
    const authSpy = jasmine.createSpyObj('AuthService', ['getToken']);

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [
        AbbreviationService,
        { provide: AuthService, useValue: authSpy },
      ],
    });

    service = TestBed.inject(AbbreviationService);
    httpMock = TestBed.inject(HttpTestingController);
    authService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  describe('getAbbreviations', () => {
    it('should fetch abbreviations without parameters', () => {
      const mockResponse = {
        status: 'success',
        data: {
          data: [
            {
              id: 1,
              abbreviation: 'API',
              meaning: 'Application Programming Interface',
              description:
                'A set of protocols and tools for building software applications',
              category: 'Technology',
              created_at: '2024-01-01T00:00:00.000000Z',
            },
          ],
          current_page: 1,
          last_page: 1,
          per_page: 10,
          total: 1,
        },
      };

      authService.getToken.and.returnValue('mock-token');

      service.getAbbreviations().subscribe((response: unknown) => {
        const typedResponse = response as {
          status: string;
          data: { data: Array<{ abbreviation: string }> };
        };
        expect(typedResponse.status).toBe('success');
        expect(typedResponse.data.data.length).toBe(1);
        expect(typedResponse.data.data[0].abbreviation).toBe('API');
      });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.abbreviations}`,
      );
      expect(req.request.method).toBe('GET');
      expect(req.request.headers.get('Authorization')).toBe(
        'Bearer mock-token',
      );
      req.flush(mockResponse);
    });

    it('should fetch abbreviations with search parameters', () => {
      const params = {
        search: 'API',
        category: 'Technology',
        page: 2,
      };

      authService.getToken.and.returnValue('');

      service.getAbbreviations(params).subscribe();

      const expectedUrl = `${apiConfig.baseUrl}${apiConfig.endpoints.abbreviations}?search=API&category=Technology&page=2`;
      const req = httpMock.expectOne(expectedUrl);
      expect(req.request.method).toBe('GET');
      expect(req.request.headers.has('Authorization')).toBe(false);
      req.flush({
        status: 'success',
        data: {
          data: [],
          current_page: 2,
          last_page: 1,
          per_page: 10,
          total: 0,
        },
      });
    });
  });

  describe('getAbbreviation', () => {
    it('should fetch a single abbreviation by ID', () => {
      const abbreviationId = 1;
      const mockResponse = {
        status: 'success',
        data: {
          id: 1,
          abbreviation: 'API',
          meaning: 'Application Programming Interface',
          description:
            'A set of protocols and tools for building software applications',
          category: 'Technology',
        },
      };

      service.getAbbreviation(abbreviationId).subscribe((response: unknown) => {
        const typedResponse = response as {
          status: string;
          data: { id: number; abbreviation: string };
        };
        expect(typedResponse.status).toBe('success');
        expect(typedResponse.data.id).toBe(1);
        expect(typedResponse.data.abbreviation).toBe('API');
      });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.abbreviationById(abbreviationId)}`,
      );
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });
  });

  describe('createAbbreviation', () => {
    it('should create a new abbreviation', () => {
      const newAbbreviation = {
        abbreviation: 'REST',
        meaning: 'Representational State Transfer',
        description:
          'An architectural style for distributed hypermedia systems',
        category: 'Technology',
      };

      const mockResponse = {
        status: 'success',
        data: {
          id: 2,
          ...newAbbreviation,
          created_at: '2024-01-02T00:00:00.000000Z',
        },
      };

      authService.getToken.and.returnValue('mock-token');

      service
        .createAbbreviation(newAbbreviation)
        .subscribe((response: unknown) => {
          const typedResponse = response as {
            status: string;
            data: { id: number; abbreviation: string };
          };
          expect(typedResponse.status).toBe('success');
          expect(typedResponse.data.abbreviation).toBe(
            newAbbreviation.abbreviation,
          );
        });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.abbreviations}`,
      );
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newAbbreviation);
      expect(req.request.headers.get('Authorization')).toBe(
        'Bearer mock-token',
      );
      req.flush(mockResponse);
    });
  });

  describe('updateAbbreviation', () => {
    it('should update an existing abbreviation', () => {
      const abbreviationId = 1;
      const updateData = {
        meaning: 'Updated meaning',
        description: 'Updated description',
      };

      const mockResponse = {
        status: 'success',
        data: {
          id: abbreviationId,
          abbreviation: 'API',
          meaning: 'Updated meaning',
          description: 'Updated description',
          category: 'Technology',
        },
      };

      service
        .updateAbbreviation(abbreviationId, updateData)
        .subscribe((response: unknown) => {
          const typedResponse = response as {
            status: string;
            data: { meaning: string };
          };
          expect(typedResponse.status).toBe('success');
          expect(typedResponse.data.meaning).toBe('Updated meaning');
        });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.abbreviationById(abbreviationId)}`,
      );
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updateData);
      req.flush(mockResponse);
    });
  });

  describe('deleteAbbreviation', () => {
    it('should delete an abbreviation', () => {
      const abbreviationId = 1;
      const mockResponse = {
        status: 'success',
        data: null,
        message: 'Abbreviation deleted successfully',
      };

      service
        .deleteAbbreviation(abbreviationId)
        .subscribe((response: unknown) => {
          const typedResponse = response as { status: string };
          expect(typedResponse.status).toBe('success');
        });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.abbreviationById(abbreviationId)}`,
      );
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('voteAbbreviation', () => {
    it('should vote on an abbreviation', () => {
      const abbreviationId = 1;
      const voteType = 'up';

      const mockResponse = {
        status: 'success',
        data: {
          message: 'Vote recorded successfully',
          votes: { upvotes: 6, downvotes: 1, net_score: 5, user_vote: 'up' },
        },
      };

      service
        .voteAbbreviation(abbreviationId, voteType)
        .subscribe((response: unknown) => {
          const typedResponse = response as { status: string };
          expect(typedResponse.status).toBe('success');
        });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.abbreviationVote(abbreviationId)}`,
      );
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({ type: voteType });
      req.flush(mockResponse);
    });
  });

  describe('addComment', () => {
    it('should add a comment to an abbreviation', () => {
      const abbreviationId = 1;
      const content = 'This is a test comment';

      const mockResponse = {
        status: 'success',
        data: {
          id: 1,
          content: content,
          created_at: '2024-01-02T00:00:00.000000Z',
          user: { id: 1, name: 'Test User', email: 'test@example.com' },
        },
      };

      service
        .addComment(abbreviationId, content)
        .subscribe((response: unknown) => {
          const typedResponse = response as {
            status: string;
            data: { content: string };
          };
          expect(typedResponse.status).toBe('success');
          expect(typedResponse.data.content).toBe(content);
        });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.abbreviationComment(abbreviationId)}`,
      );
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({ content: content });
      req.flush(mockResponse);
    });
  });

  describe('getCategories', () => {
    it('should fetch all categories', () => {
      const mockResponse = {
        status: 'success',
        data: ['Technology', 'Medicine', 'Business', 'Science'],
      };

      service.getCategories().subscribe((response: unknown) => {
        const typedResponse = response as { status: string; data: string[] };
        expect(typedResponse.status).toBe('success');
        expect(typedResponse.data.length).toBe(4);
        expect(typedResponse.data).toContain('Technology');
      });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.categories}`,
      );
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });
  });

  describe('getSuggestions', () => {
    it('should get suggestions for an abbreviation', () => {
      const abbreviation = 'AI';
      const mockResponse = {
        status: 'success',
        data: {
          suggestions: [
            {
              type: 'original_meaning',
              meaning: 'Artificial Intelligence',
              description: 'Technology that simulates human intelligence',
              source: 'ml_service',
              confidence_score: 0.95,
            },
          ],
        },
      };

      service.getSuggestions(abbreviation).subscribe((response: unknown) => {
        const typedResponse = response as {
          status: string;
          data: { suggestions: unknown[] };
        };
        expect(typedResponse.status).toBe('success');
        expect(typedResponse.data.suggestions.length).toBe(1);
      });

      const expectedUrl = `${apiConfig.baseUrl}${apiConfig.endpoints.suggestions}?abbreviation=${abbreviation}`;
      const req = httpMock.expectOne(expectedUrl);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });
  });

  describe('Admin methods', () => {
    beforeEach(() => {
      authService.getToken.and.returnValue('admin-token');
    });

    it('should get admin statistics', () => {
      const mockStats = {
        status: 'success',
        data: {
          total_abbreviations: 100,
          total_users: 50,
          pending_abbreviations: 5,
        },
      };

      service.getAdminStatistics().subscribe((response: unknown) => {
        const typedResponse = response as {
          total_abbreviations: number;
          total_users: number;
        };
        expect(typedResponse.total_abbreviations).toBe(100);
        expect(typedResponse.total_users).toBe(50);
      });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.adminStatistics}`,
      );
      expect(req.request.method).toBe('GET');
      req.flush(mockStats);
    });

    it('should get all users', () => {
      const mockUsers = {
        status: 'success',
        data: [
          { id: 1, name: 'User 1', email: 'user1@example.com', role: 'user' },
          { id: 2, name: 'User 2', email: 'user2@example.com', role: 'admin' },
        ],
      };

      service.getUsers().subscribe((users: unknown) => {
        const typedUsers = users as Array<{ name: string }>;
        expect(typedUsers.length).toBe(2);
        expect(typedUsers[0].name).toBe('User 1');
      });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.adminUsers}`,
      );
      expect(req.request.method).toBe('GET');
      req.flush(mockUsers);
    });

    it('should promote a user', () => {
      const userId = 1;
      const mockResponse = {
        status: 'success',
        message: 'User promoted successfully',
      };

      service.promoteUser(userId).subscribe((response: unknown) => {
        const typedResponse = response as { status: string };
        expect(typedResponse.status).toBe('success');
      });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.adminUserPromote(userId)}`,
      );
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({});
      req.flush(mockResponse);
    });

    it('should delete a user', () => {
      const userId = 1;
      const mockResponse = {
        status: 'success',
        message: 'User deleted successfully',
      };

      service.deleteUser(userId).subscribe((response: unknown) => {
        const typedResponse = response as { status: string };
        expect(typedResponse.status).toBe('success');
      });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.adminUserDelete(userId)}`,
      );
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('error handling', () => {
    it('should handle HTTP errors gracefully', () => {
      service.getAbbreviations().subscribe({
        next: () => fail('Expected an error'),
        error: (error: unknown) => {
          const typedError = error as { status: number; statusText: string };
          expect(typedError.status).toBe(500);
          expect(typedError.statusText).toBe('Internal Server Error');
        },
      });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.abbreviations}`,
      );
      req.flush('Server Error', {
        status: 500,
        statusText: 'Internal Server Error',
      });
    });

    it('should handle unauthorized access', () => {
      service
        .createAbbreviation({
          abbreviation: 'TEST',
          meaning: 'Test abbreviation',
        })
        .subscribe({
          next: () => fail('Expected an error'),
          error: (error: unknown) => {
            const typedError = error as { status: number; statusText: string };
            expect(typedError.status).toBe(401);
            expect(typedError.statusText).toBe('Unauthorized');
          },
        });

      const req = httpMock.expectOne(
        `${apiConfig.baseUrl}${apiConfig.endpoints.abbreviations}`,
      );
      req.flush('Unauthorized', { status: 401, statusText: 'Unauthorized' });
    });
  });
});
