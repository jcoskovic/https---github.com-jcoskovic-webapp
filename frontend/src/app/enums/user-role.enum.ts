export enum UserRole {
  ADMIN = 'admin',
  MODERATOR = 'moderator',
  USER = 'user',
}

export class RoleHelper {
  /**
   * Get role display name in Serbian
   */
  static getDisplayName(role: UserRole | string): string {
    switch (role) {
      case UserRole.ADMIN:
        return 'Administrator';
      case UserRole.MODERATOR:
        return 'Moderator';
      case UserRole.USER:
        return 'Korisnik';
      default:
        return 'Nepoznato';
    }
  }

  /**
   * Check if role has admin privileges
   */
  static hasAdminPrivileges(role: UserRole | string): boolean {
    return role === UserRole.ADMIN;
  }

  /**
   * Check if role has moderator privileges (includes admin)
   */
  static hasModeratorPrivileges(role: UserRole | string): boolean {
    return role === UserRole.ADMIN || role === UserRole.MODERATOR;
  }

  /**
   * Check if role can manage users
   */
  static canManageUsers(role: UserRole | string): boolean {
    return role === UserRole.ADMIN;
  }

  /**
   * Check if role can moderate content
   */
  static canModerateContent(role: UserRole | string): boolean {
    return role === UserRole.ADMIN || role === UserRole.MODERATOR;
  }

  /**
   * Get all roles for select dropdown
   */
  static getAllRoles(): { value: UserRole; label: string }[] {
    return [
      { value: UserRole.USER, label: this.getDisplayName(UserRole.USER) },
      {
        value: UserRole.MODERATOR,
        label: this.getDisplayName(UserRole.MODERATOR),
      },
      { value: UserRole.ADMIN, label: this.getDisplayName(UserRole.ADMIN) },
    ];
  }

  /**
   * Get role badge CSS class
   */
  static getRoleBadgeClass(role: UserRole | string): string {
    switch (role) {
      case UserRole.ADMIN:
        return 'role-admin';
      case UserRole.MODERATOR:
        return 'role-moderator';
      case UserRole.USER:
        return 'role-user';
      default:
        return 'role-unknown';
    }
  }
}
