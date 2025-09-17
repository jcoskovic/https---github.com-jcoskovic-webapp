<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case USER = 'user';

    /**
     * Get all role values
     */
    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get role display name in Serbian
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::MODERATOR => 'Moderator',
            self::USER => 'Korisnik',
        };
    }

    /**
     * Check if role has admin privileges
     */
    public function hasAdminPrivileges(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if role has moderator privileges (includes admin)
     */
    public function hasModeratorPrivileges(): bool
    {
        return in_array($this, [self::ADMIN, self::MODERATOR]);
    }

    /**
     * Check if role can manage users
     */
    public function canManageUsers(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if role can moderate content
     */
    public function canModerateContent(): bool
    {
        return in_array($this, [self::ADMIN, self::MODERATOR]);
    }
}
