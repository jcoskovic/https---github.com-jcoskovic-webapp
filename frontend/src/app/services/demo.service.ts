import { Injectable } from '@angular/core';
import { Observable, of } from 'rxjs';
import { delay } from 'rxjs/operators';

interface DemoSuggestion {
  abbreviation: string;
  meaning: string;
  croatian_meaning: string;
}

interface DemoAbbreviation {
  id: number;
  abbreviation: string;
  meaning: string;
  croatian_meaning: string;
  description: string;
  created_at: string;
  votes_count: number;
  user_vote: null | number;
}

interface DemoUser {
  id: number;
  name: string;
  email: string;
  role: string;
  created_at: string;
  email_verified_at: string;
}

interface DemoStatisticsResponse {
  total_abbreviations: number;
  total_users: number;
  total_votes: number;
  trending_abbreviations: DemoAbbreviation[];
}

@Injectable({
  providedIn: 'root',
})
export class DemoService {
  // Demo abbreviations data
  private demoAbbreviations = [
    {
      id: 1,
      abbreviation: 'API',
      meaning: 'Application Programming Interface',
      croatian_meaning: 'Sučelje za programiranje aplikacija',
      description:
        'Set of protocols and tools for building software applications',
      created_at: '2024-01-15T10:00:00Z',
      votes_count: 15,
      user_vote: null,
    },
    {
      id: 2,
      abbreviation: 'HTML',
      meaning: 'HyperText Markup Language',
      croatian_meaning: 'Hipertekstni označavajući jezik',
      description: 'Standard markup language for creating web pages',
      created_at: '2024-01-16T11:30:00Z',
      votes_count: 23,
      user_vote: 1,
    },
    {
      id: 3,
      abbreviation: 'CSS',
      meaning: 'Cascading Style Sheets',
      croatian_meaning: 'Kaskadni stilski listovi',
      description:
        'Style sheet language used for describing presentation of HTML',
      created_at: '2024-01-17T09:15:00Z',
      votes_count: 18,
      user_vote: -1,
    },
    {
      id: 4,
      abbreviation: 'JS',
      meaning: 'JavaScript',
      croatian_meaning: 'JavaScript',
      description: 'Programming language for web development',
      created_at: '2024-01-18T14:45:00Z',
      votes_count: 31,
      user_vote: null,
    },
    {
      id: 5,
      abbreviation: 'SQL',
      meaning: 'Structured Query Language',
      croatian_meaning: 'Strukturni jezik za upite',
      description: 'Domain-specific language for managing relational databases',
      created_at: '2024-01-19T08:20:00Z',
      votes_count: 12,
      user_vote: 1,
    },
  ];

  // Demo user data
  private demoUser = {
    id: 1,
    name: 'Demo User',
    email: 'demo@abbrevio.com',
    role: 'user',
    created_at: '2024-01-01T00:00:00Z',
    email_verified_at: '2024-01-01T00:00:00Z',
  };

  getDemoAbbreviations(): Observable<{
    status: string;
    data: {
      abbreviations: DemoAbbreviation[];
      total: number;
      per_page: number;
      current_page: number;
    };
  }> {
    return of({
      status: 'success',
      data: {
        abbreviations: this.demoAbbreviations,
        total: this.demoAbbreviations.length,
        per_page: 10,
        current_page: 1,
      },
    }).pipe(delay(500)); // Simulate network delay
  }

  getDemoUser(): Observable<{ status: string; data: { user: DemoUser } }> {
    return of({
      status: 'success',
      data: {
        user: this.demoUser,
      },
    }).pipe(delay(300));
  }

  getDemoLogin(): Observable<{
    status: string;
    data: { user: DemoUser; token: string };
    message: string;
  }> {
    return of({
      status: 'success',
      data: {
        user: this.demoUser,
        token: 'demo-token-12345',
      },
      message: 'Successfully logged in (demo mode)',
    }).pipe(delay(800));
  }

  getDemoSuggestions(): Observable<{
    status: string;
    data: { suggestions: DemoSuggestion[] };
  }> {
    const suggestions = [
      {
        abbreviation: 'AI',
        meaning: 'Artificial Intelligence',
        croatian_meaning: 'Umjetna inteligencija',
      },
      {
        abbreviation: 'ML',
        meaning: 'Machine Learning',
        croatian_meaning: 'Strojno učenje',
      },
      {
        abbreviation: 'UI',
        meaning: 'User Interface',
        croatian_meaning: 'Korisničko sučelje',
      },
      {
        abbreviation: 'UX',
        meaning: 'User Experience',
        croatian_meaning: 'Korisničko iskustvo',
      },
    ];

    return of({
      status: 'success',
      data: {
        suggestions: suggestions.slice(0, 3), // Return 3 suggestions
      },
    }).pipe(delay(600));
  }

  getDemoStats(): Observable<{ status: string; data: DemoStatisticsResponse }> {
    return of({
      status: 'success',
      data: {
        total_abbreviations: 127,
        total_users: 45,
        total_votes: 892,
        trending_abbreviations: this.demoAbbreviations.slice(0, 3),
      },
    }).pipe(delay(400));
  }
}
