import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export interface ExportOptions {
  format: 'pdf';
  includeFields: string[];
  abbreviation_ids?: number[];
  filters?: {
    category?: string;
    searchTerm?: string;
    dateRange?: {
      from: string;
      to: string;
    };
    minVotes?: number;
    status?: string;
  };
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
}

export interface ExportProgress {
  isExporting: boolean;
  progress: number;
  message: string;
  downloadUrl?: string;
}

@Injectable({
  providedIn: 'root',
})
export class ExportService {
  private http = inject(HttpClient);

  private apiUrl = environment.apiUrl;

  // Export abbreviations to PDF
  exportToPDF(options: Partial<ExportOptions> = {}): Observable<Blob> {
    const defaultOptions: ExportOptions = {
      format: 'pdf',
      includeFields: [
        'abbreviation',
        'meaning',
        'category',
        'description',
        'votes_sum',
      ],
      ...options,
    };

    // Build query parameters for GET request
    let params = '';
    const queryParams: string[] = [];

    if (defaultOptions.abbreviation_ids && defaultOptions.abbreviation_ids.length > 0) {
      defaultOptions.abbreviation_ids.forEach(id => {
        queryParams.push(`abbreviation_ids[]=${id}`);
      });
    } else {
      if (defaultOptions.filters?.searchTerm) {
        queryParams.push(`search=${encodeURIComponent(defaultOptions.filters.searchTerm)}`);
      }

      if (defaultOptions.filters?.category) {
        queryParams.push(`category=${encodeURIComponent(defaultOptions.filters.category)}`);
      }
    }

    // Default to simple format for now
    queryParams.push('format=simple');

    if (queryParams.length > 0) {
      params = '?' + queryParams.join('&');
    }

    return this.http
      .get(`${this.apiUrl}/export/pdf${params}`, {
        responseType: 'blob',
        headers: this.getAuthHeaders().set('Accept', 'application/pdf'),
      })
      .pipe(
        catchError(() => {
          return throwError(
            () =>
              new Error('PDF export nije uspješan. Molimo pokušajte ponovo.'),
          );
        }),
      );
  }

  // Generic export method
  exportAbbreviations(options: ExportOptions): Observable<Blob> {
    switch (options.format) {
      case 'pdf':
        return this.exportToPDF(options);
      default:
        return throwError(() => new Error('Nepodržani format exporta. Podržan je samo PDF format.'));
    }
  }

  // Download blob as file
  downloadBlob(blob: Blob, filename: string): void {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  }

  // Generate filename based on options
  generateFilename(format: string, filters?: ExportOptions['filters']): string {
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
    let filename = `abbrevio_export_${timestamp}`;

    if (filters?.category) {
      filename += `_${filters.category.replace(/\s+/g, '_')}`;
    }

    if (filters?.searchTerm) {
      filename += `_search_${filters.searchTerm.replace(/\s+/g, '_')}`;
    }

    return `${filename}.${format}`;
  }

  // Get authorization headers
  private getAuthHeaders(): HttpHeaders {
    const token = localStorage.getItem('auth_token');
    let headers = new HttpHeaders();

    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`);
    }

    return headers;
  }
}
