import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from './auth.service';
import { ApiResponse, ExportOptions } from '../interfaces';
import { Recommendation } from '../interfaces/ml.interface';
import { apiConfig, buildUrl } from '../config/api.config';

export interface Stats {
  total_abbreviations: number;
  total_votes: number;
  total_comments: number;
  total_categories: number;
  recent_abbreviations: number;
}

@Injectable({
  providedIn: 'root',
})
export class StatsService {
  private http = inject(HttpClient);
  private authService = inject(AuthService);

  private getHeaders(): HttpHeaders {
    let headers = new HttpHeaders({
      'Content-Type': 'application/json',
      Accept: 'application/json',
    });

    const token = this.authService.getToken();
    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`);
    }

    return headers;
  }

  getStats(): Observable<ApiResponse<Stats>> {
    return this.http.get<ApiResponse<Stats>>(
      buildUrl(apiConfig.endpoints.stats),
      {
        headers: this.getHeaders(),
      },
    );
  }

  getTrendingAbbreviations(): Observable<ApiResponse<Recommendation[]>> {
    return this.http.get<ApiResponse<Recommendation[]>>(
      buildUrl(apiConfig.endpoints.mlTrending),
      {
        headers: this.getHeaders(),
      },
    );
  }

  getExportPdfUrl(options: ExportOptions): string {
    const params = new URLSearchParams();
    if (options.format) params.append('format', options.format);
    if (options.category) params.append('category', options.category);
    if (options.department) params.append('department', options.department);

    return `${apiConfig.baseUrl}${apiConfig.endpoints.exportPdf}?${params.toString()}`;
  }

}
