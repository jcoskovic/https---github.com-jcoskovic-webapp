import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { debounceTime, distinctUntilChanged, switchMap } from 'rxjs/operators';
import { Subject, of } from 'rxjs';

interface Abbreviation {
  id: number;
  abbreviation: string;
  meaning: string;
  description?: string;
  department?: string;
  category?: string;
  votes_count: number;
  user: {
    id: number;
    name: string;
  };
  created_at: string;
}

interface SearchResponse {
  data: {
    data: Abbreviation[];
    current_page: number;
    last_page: number;
    total: number;
  };
}

@Component({
  selector: 'app-search',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './search.component.html',
  styleUrls: ['./search.component.scss'],
})
export class SearchComponent implements OnInit {
  private http = inject(HttpClient);

  searchTerm = '';
  selectedCategory = '';
  selectedDepartment = '';
  searchResults: Abbreviation[] = [];
  categories: string[] = [];
  departments: string[] = [];
  loading = false;
  currentPage = 1;
  totalPages = 1;
  totalResults = 0;

  private searchSubject = new Subject<string>();

  ngOnInit() {
    this.loadCategories();
    this.loadDepartments();
    this.setupSearch();
  }

  setupSearch() {
    this.searchSubject
      .pipe(
        debounceTime(300),
        distinctUntilChanged(),
        switchMap((term) => {
          if (!term.trim()) {
            return of({
              data: { data: [], current_page: 1, last_page: 1, total: 0 },
            });
          }
          return this.performSearch(term);
        }),
      )
      .subscribe({
        next: (response: SearchResponse) => {
          this.searchResults = response.data.data;
          this.currentPage = response.data.current_page;
          this.totalPages = response.data.last_page;
          this.totalResults = response.data.total;
          this.loading = false;
        },
        error: () => {
          this.loading = false;
        },
      });
  }

  onSearchInput(event: Event) {
    const input = event.target as HTMLInputElement;
    this.searchSubject.next(input.value);
  }

  performSearch(term: string, page = 1) {
    let url = `http://localhost:8000/api/abbreviations?search=${encodeURIComponent(term)}&page=${page}`;

    if (this.selectedCategory) {
      url += `&category=${encodeURIComponent(this.selectedCategory)}`;
    }

    if (this.selectedDepartment) {
      url += `&department=${encodeURIComponent(this.selectedDepartment)}`;
    }

    return this.http.get<SearchResponse>(url);
  }

  clearSearch() {
    this.searchTerm = '';
    this.searchResults = [];
    this.totalResults = 0;
    this.currentPage = 1;
    this.totalPages = 1;
  }

  onCategoryChange() {
    if (this.searchTerm) {
      this.loading = true;
      this.searchSubject.next(this.searchTerm);
    }
  }

  onDepartmentChange() {
    if (this.searchTerm) {
      this.loading = true;
      this.searchSubject.next(this.searchTerm);
    }
  }

  goToPage(page: number) {
    if (page >= 1 && page <= this.totalPages && this.searchTerm) {
      this.loading = true;
      this.performSearch(this.searchTerm, page).subscribe({
        next: (response: SearchResponse) => {
          this.searchResults = response.data.data;
          this.currentPage = response.data.current_page;
          this.totalPages = response.data.last_page;
          this.totalResults = response.data.total;
          this.loading = false;
        },
        error: () => {
          this.loading = false;
        },
      });
    }
  }

  private loadCategories() {
    this.http
      .get<{ data: string[] }>('http://localhost:8000/api/categories')
      .subscribe({
        next: (response) => {
          this.categories = response.data;
        },
        error: () => {
          // Handle category loading error silently
        },
      });
  }

  private loadDepartments() {
    // This would need to be implemented on the backend
    // For now, we'll use hardcoded departments
    this.departments = ['IT', 'HR', 'Marketing', 'Sales', 'Finance'];
  }
}
