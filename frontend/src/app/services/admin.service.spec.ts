import { TestBed } from '@angular/core/testing';
import {
  HttpClientTestingModule,
  HttpTestingController,
} from '@angular/common/http/testing';
import { AdminService } from './admin.service';
import {
  AdminUser,
  AdminAbbreviation,
  AdminStatistics,
} from '../interfaces/admin.interface';
import { UserRole } from '../enums/user-role.enum';
import { environment } from '../../environments/environment';

describe('AdminService', () => {
  let service: AdminService;
  let httpMock: HttpTestingController;
  const apiUrl = environment.apiUrl;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [AdminService],
    });
    service = TestBed.inject(AdminService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  describe('Statistics', () => {
    it('should get statistics', () => {
      const mockStats: AdminStatistics = {
        total_users: 100,
        total_abbreviations: 50,
        total_votes: 200,
        total_comments: 75,
        pending_abbreviations: 5,
        active_users_today: 20,
        top_categories: [
          { name: 'Technology', count: 15 },
          { name: 'Business', count: 10 },
        ],
      };

      service.getStatistics().subscribe((stats) => {
        expect(stats).toEqual(mockStats);
      });

      const req = httpMock.expectOne(`${apiUrl}/admin/statistics`);
      expect(req.request.method).toBe('GET');
      req.flush(mockStats);
    });
  });

  describe('User Management', () => {
    it('should get users', () => {
      const mockUsers: AdminUser[] = [
        {
          id: 1,
          name: 'John Doe',
          email: 'john@example.com',
          role: UserRole.USER,
          created_at: '2023-01-01T00:00:00Z',
          abbreviations_count: 5,
          votes_count: 10,
          comments_count: 3,
        },
      ];

      service.getUsers().subscribe((users) => {
        expect(users).toEqual(mockUsers);
      });

      const req = httpMock.expectOne(`${apiUrl}/admin/users`);
      expect(req.request.method).toBe('GET');
      req.flush(mockUsers);
    });

    it('should promote user', () => {
      const userId = 1;

      service.promoteUser(userId).subscribe((response) => {
        expect(response).toBeTruthy();
      });

      const req = httpMock.expectOne(`${apiUrl}/admin/users/${userId}/promote`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({});
      req.flush({ success: true });
    });

    it('should demote user', () => {
      const userId = 1;

      service.demoteUser(userId).subscribe((response) => {
        expect(response).toBeTruthy();
      });

      const req = httpMock.expectOne(`${apiUrl}/admin/users/${userId}/demote`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({});
      req.flush({ success: true });
    });

    it('should delete user', () => {
      const userId = 1;

      service.deleteUser(userId).subscribe((response) => {
        expect(response).toBeTruthy();
      });

      const req = httpMock.expectOne(`${apiUrl}/admin/users/${userId}`);
      expect(req.request.method).toBe('DELETE');
      req.flush({ success: true });
    });
  });

  describe('Abbreviation Management', () => {
    it('should get all abbreviations', () => {
      const mockAbbreviations: AdminAbbreviation[] = [
        {
          id: 1,
          abbreviation: 'API',
          meaning: 'Application Programming Interface',
          description: 'A set of protocols',
          category: 'Technology',
          user: {
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
          },
          votes_sum: 5,
          comments_count: 2,
          created_at: '2023-01-01T00:00:00Z',
          status: 'approved',
        },
      ];

      service.getAllAbbreviations().subscribe((abbreviations) => {
        expect(abbreviations).toEqual(mockAbbreviations);
      });

      const req = httpMock.expectOne(`${apiUrl}/admin/abbreviations`);
      expect(req.request.method).toBe('GET');
      req.flush(mockAbbreviations);
    });

    it('should delete abbreviation', () => {
      const abbreviationId = 1;

      service.deleteAbbreviation(abbreviationId).subscribe((response) => {
        expect(response).toBeTruthy();
      });

      const req = httpMock.expectOne(
        `${apiUrl}/admin/abbreviations/${abbreviationId}`,
      );
      expect(req.request.method).toBe('DELETE');
      req.flush({ success: true });
    });
  });

  describe('Moderation', () => {
    it('should get pending abbreviations', () => {
      const mockPending: AdminAbbreviation[] = [
        {
          id: 2,
          abbreviation: 'CPU',
          meaning: 'Central Processing Unit',
          category: 'Technology',
          user: {
            id: 2,
            name: 'Jane Smith',
            email: 'jane@example.com',
          },
          votes_sum: 0,
          comments_count: 0,
          created_at: '2023-01-02T00:00:00Z',
          status: 'pending',
        },
      ];

      service.getPendingAbbreviations().subscribe((pending) => {
        expect(pending).toEqual(mockPending);
      });

      const req = httpMock.expectOne(`${apiUrl}/admin/abbreviations/pending`);
      expect(req.request.method).toBe('GET');
      req.flush(mockPending);
    });

    it('should approve abbreviation', () => {
      const abbreviationId = 2;

      service.approveAbbreviation(abbreviationId).subscribe((response) => {
        expect(response).toBeTruthy();
      });

      const req = httpMock.expectOne(
        `${apiUrl}/admin/abbreviations/${abbreviationId}/approve`,
      );
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({});
      req.flush({ success: true });
    });

    it('should reject abbreviation', () => {
      const abbreviationId = 2;

      service.rejectAbbreviation(abbreviationId).subscribe((response) => {
        expect(response).toBeTruthy();
      });

      const req = httpMock.expectOne(
        `${apiUrl}/admin/abbreviations/${abbreviationId}/reject`,
      );
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({});
      req.flush({ success: true });
    });
  });

  describe('Batch Operations', () => {
    it('should delete multiple users', () => {
      const userIds = [1, 2, 3];

      service.deleteMultipleUsers(userIds).subscribe((response) => {
        expect(response).toBeTruthy();
      });

      const req = httpMock.expectOne(`${apiUrl}/admin/users/batch-delete`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({ userIds });
      req.flush({ success: true });
    });

    it('should approve multiple abbreviations', () => {
      const abbreviationIds = [1, 2, 3];

      service
        .approveMultipleAbbreviations(abbreviationIds)
        .subscribe((response) => {
          expect(response).toBeTruthy();
        });

      const req = httpMock.expectOne(
        `${apiUrl}/admin/abbreviations/batch-approve`,
      );
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({ abbreviationIds });
      req.flush({ success: true });
    });
  });
});
