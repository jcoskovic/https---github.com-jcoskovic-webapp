import { UserRole } from '../enums/user-role.enum';

export class AdminUtilsHelper {
  static formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleString('sr-RS', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  static formatDateShort(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('sr-RS');
  }

  static getStatusDisplayName(status: string): string {
    const statusMap: Record<string, string> = {
      approved: 'Odobreno',
      pending: 'Na čekanju',
      rejected: 'Odbijeno',
    };
    return statusMap[status] || status;
  }

  static getStatusClass(status: string): string {
    const statusClassMap: Record<string, string> = {
      approved: 'status-approved',
      pending: 'status-pending',
      rejected: 'status-rejected',
    };
    return statusClassMap[status] || 'status-unknown';
  }

  static getVotesClass(votes: number): string {
    if (votes > 0) return 'positive';
    if (votes < 0) return 'negative';
    return 'neutral';
  }

  static truncateText(text: string, maxLength = 50): string {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
  }

  static canPerformUserAction(
    currentUserRole: UserRole,
    targetUserRole: UserRole,
  ): boolean {
    // Admins can manage everyone except other admins
    if (currentUserRole === UserRole.ADMIN) {
      return targetUserRole !== UserRole.ADMIN;
    }

    // Moderators can only manage regular users
    if (currentUserRole === UserRole.MODERATOR) {
      return targetUserRole === UserRole.USER;
    }

    return false;
  }

  static generateConfirmationMessage(action: string, itemName: string): string {
    const actionMap: Record<string, string> = {
      delete: `Jeste li sigurni da želite obrisati ${itemName}? Ova akcija se ne može poništiti.`,
      promote: `Jeste li sigurni da želite unaprediti korisnika ${itemName}?`,
      demote: `Jeste li sigurni da želite sniziti prava korisniku ${itemName}?`,
      approve: `Jeste li sigurni da želite odobriti skraćenicu ${itemName}?`,
      reject: `Jeste li sigurni da želite odbiti skraćenicu ${itemName}?`,
    };

    return (
      actionMap[action] ||
      `Jeste li sigurni da želite izvršiti ovu akciju na ${itemName}?`
    );
  }
}
