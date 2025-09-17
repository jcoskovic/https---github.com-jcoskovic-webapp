import { AdminUser, AdminAbbreviation } from '../interfaces/admin.interface';

export class AdminFilterHelper {
  static filterUsers(users: AdminUser[], searchTerm: string): AdminUser[] {
    if (!searchTerm.trim()) {
      return users;
    }

    const term = searchTerm.toLowerCase();
    return users.filter(
      (user) =>
        user.name.toLowerCase().includes(term) ||
        user.email.toLowerCase().includes(term),
    );
  }

  static filterAbbreviations(
    abbreviations: AdminAbbreviation[],
    searchTerm: string,
    category: string,
  ): AdminAbbreviation[] {
    let filtered = abbreviations;

    // Filter by search term - only search by abbreviation name
    if (searchTerm.trim()) {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(
        (abbr) =>
          abbr.abbreviation.toLowerCase().startsWith(term)
      );
    }

    // Filter by category
    if (category) {
      filtered = filtered.filter((abbr) => abbr.category === category);
    }

    return filtered;
  }

  static getUniqueCategories(abbreviations: AdminAbbreviation[]): string[] {
    return [...new Set(abbreviations.map((a) => a.category))].sort();
  }

  static sortUsersByName(users: AdminUser[]): AdminUser[] {
    return [...users].sort((a, b) => a.name.localeCompare(b.name));
  }

  static sortAbbreviationsByDate(
    abbreviations: AdminAbbreviation[],
  ): AdminAbbreviation[] {
    return [...abbreviations].sort(
      (a, b) =>
        new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
    );
  }
}
