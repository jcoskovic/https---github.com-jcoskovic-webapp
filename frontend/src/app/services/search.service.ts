import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, combineLatest } from 'rxjs';
import { map, debounceTime, distinctUntilChanged } from 'rxjs/operators';

export interface SearchFilters {
  searchTerm: string;
  category: string;
  sortBy: string;
  sortOrder: 'asc' | 'desc';
}

export interface PaginationState {
  currentPage: number;
  itemsPerPage: number;
  totalItems: number;
  totalPages: number;
}

@Injectable({
  providedIn: 'root',
})
export class SearchService {
  // Search and filter state
  private searchTermSubject = new BehaviorSubject<string>('');
  private categorySubject = new BehaviorSubject<string>('');
  private sortBySubject = new BehaviorSubject<string>('created_at');
  private sortOrderSubject = new BehaviorSubject<'asc' | 'desc'>('desc');

  // Pagination state
  private currentPageSubject = new BehaviorSubject<number>(1);
  private itemsPerPageSubject = new BehaviorSubject<number>(20);
  private totalItemsSubject = new BehaviorSubject<number>(0);

  // Public observables
  public searchTerm$ = this.searchTermSubject
    .asObservable()
    .pipe(debounceTime(300), distinctUntilChanged());

  public category$ = this.categorySubject.asObservable();
  public sortBy$ = this.sortBySubject.asObservable();
  public sortOrder$ = this.sortOrderSubject.asObservable();
  public currentPage$ = this.currentPageSubject.asObservable();
  public itemsPerPage$ = this.itemsPerPageSubject.asObservable();
  public totalItems$ = this.totalItemsSubject.asObservable();

  // Combined filters observable
  public filters$: Observable<SearchFilters> = combineLatest([
    this.searchTerm$,
    this.category$,
    this.sortBy$,
    this.sortOrder$,
  ]).pipe(
    map(([searchTerm, category, sortBy, sortOrder]) => ({
      searchTerm,
      category,
      sortBy,
      sortOrder,
    })),
  );

  // Combined pagination observable
  public pagination$: Observable<PaginationState> = combineLatest([
    this.currentPage$,
    this.itemsPerPage$,
    this.totalItems$,
  ]).pipe(
    map(([currentPage, itemsPerPage, totalItems]) => ({
      currentPage,
      itemsPerPage,
      totalItems,
      totalPages: Math.ceil(totalItems / itemsPerPage),
    })),
  );

  // Search methods
  setSearchTerm(term: string): void {
    this.searchTermSubject.next(term);
    this.resetToFirstPage();
  }

  setCategory(category: string): void {
    this.categorySubject.next(category);
    this.resetToFirstPage();
  }

  setSorting(sortBy: string, sortOrder: 'asc' | 'desc' = 'desc'): void {
    this.sortBySubject.next(sortBy);
    this.sortOrderSubject.next(sortOrder);
    this.resetToFirstPage();
  }

  toggleSortOrder(): void {
    const currentOrder = this.sortOrderSubject.value;
    this.sortOrderSubject.next(currentOrder === 'asc' ? 'desc' : 'asc');
    this.resetToFirstPage();
  }

  // Pagination methods
  setCurrentPage(page: number): void {
    const totalPages = Math.ceil(
      this.totalItemsSubject.value / this.itemsPerPageSubject.value,
    );
    if (page >= 1 && page <= totalPages) {
      this.currentPageSubject.next(page);
    }
  }

  setItemsPerPage(itemsPerPage: number): void {
    this.itemsPerPageSubject.next(itemsPerPage);
    this.resetToFirstPage();
  }

  setTotalItems(totalItems: number): void {
    this.totalItemsSubject.next(totalItems);
  }

  nextPage(): void {
    const currentPage = this.currentPageSubject.value;
    const totalPages = Math.ceil(
      this.totalItemsSubject.value / this.itemsPerPageSubject.value,
    );
    if (currentPage < totalPages) {
      this.currentPageSubject.next(currentPage + 1);
    }
  }

  previousPage(): void {
    const currentPage = this.currentPageSubject.value;
    if (currentPage > 1) {
      this.currentPageSubject.next(currentPage - 1);
    }
  }

  resetToFirstPage(): void {
    this.currentPageSubject.next(1);
  }

  // Utility methods
  getCurrentFilters(): SearchFilters {
    return {
      searchTerm: this.searchTermSubject.value,
      category: this.categorySubject.value,
      sortBy: this.sortBySubject.value,
      sortOrder: this.sortOrderSubject.value,
    };
  }

  getCurrentPagination(): PaginationState {
    const currentPage = this.currentPageSubject.value;
    const itemsPerPage = this.itemsPerPageSubject.value;
    const totalItems = this.totalItemsSubject.value;

    return {
      currentPage,
      itemsPerPage,
      totalItems,
      totalPages: Math.ceil(totalItems / itemsPerPage),
    };
  }

  // Reset all filters and pagination
  reset(): void {
    this.searchTermSubject.next('');
    this.categorySubject.next('');
    this.sortBySubject.next('created_at');
    this.sortOrderSubject.next('desc');
    this.currentPageSubject.next(1);
    this.itemsPerPageSubject.next(20);
    this.totalItemsSubject.next(0);
  }

  // Client-side filtering for arrays (when not using server-side pagination)
  filterItems<T>(
    items: T[],
    filterFn: (item: T, filters: SearchFilters) => boolean,
  ): T[] {
    const filters = this.getCurrentFilters();
    return items.filter((item) => filterFn(item, filters));
  }

  // Client-side sorting for arrays
  sortItems<T>(
    items: T[],
    sortFn: (a: T, b: T, sortBy: string, sortOrder: 'asc' | 'desc') => number,
  ): T[] {
    const { sortBy, sortOrder } = this.getCurrentFilters();
    return [...items].sort((a, b) => sortFn(a, b, sortBy, sortOrder));
  }

  // Client-side pagination for arrays
  paginateItems<T>(items: T[]): T[] {
    const { currentPage, itemsPerPage } = this.getCurrentPagination();
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    return items.slice(startIndex, endIndex);
  }

  getPaginationInfo(): { start: number; end: number; total: number } {
    const { currentPage, itemsPerPage, totalItems } =
      this.getCurrentPagination();
    const start = (currentPage - 1) * itemsPerPage + 1;
    const end = Math.min(currentPage * itemsPerPage, totalItems);

    return { start, end, total: totalItems };
  }

  getPageNumbers(): number[] {
    const { currentPage, totalPages } = this.getCurrentPagination();
    const pages: number[] = [];
    const maxVisible = 5;

    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    const endPage = Math.min(totalPages, startPage + maxVisible - 1);

    if (endPage - startPage + 1 < maxVisible) {
      startPage = Math.max(1, endPage - maxVisible + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
      pages.push(i);
    }

    return pages;
  }
}
