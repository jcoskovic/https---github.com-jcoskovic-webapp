<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /**
     * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\UserFactory>
     */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'department',
        'role',
        'password_reset_token',
        'password_reset_expires',
        'email_verification_token',
        'email_verification_token_expires',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'password_reset_token',
        'password_reset_expires',
        'email_verification_token',
        'email_verification_token_expires',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
        'password_reset_expires' => 'datetime',
        'email_verification_token_expires' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    /**
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get abbreviations created by this user
     */
    /**
     * @return HasMany<Abbreviation, self>
     */
    public function abbreviations(): HasMany
    {
        /** @var HasMany<Abbreviation, self> $relation */
        $relation = $this->hasMany(Abbreviation::class);

        return $relation;
    }

    /**
     * Get votes by this user
     */
    /**
     * @return HasMany<Vote, self>
     */
    public function votes(): HasMany
    {
        /** @var HasMany<Vote, self> $relation */
        $relation = $this->hasMany(Vote::class);

        return $relation;
    }

    /**
     * Get comments by this user
     */
    /**
     * @return HasMany<Comment, self>
     */
    public function comments(): HasMany
    {
        /** @var HasMany<Comment, self> $relation */
        $relation = $this->hasMany(Comment::class);

        return $relation;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if user is moderator or admin
     */
    public function isModerator(): bool
    {
        return $this->role && $this->role->hasModeratorPrivileges();
    }

    /**
     * Check if user can manage other users
     */
    public function canManageUsers(): bool
    {
        return $this->role && $this->role->canManageUsers();
    }

    /**
     * Check if user can moderate content
     */
    public function canModerateContent(): bool
    {
        return $this->role && $this->role->canModerateContent();
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return $this->role ? $this->role->getDisplayName() : 'Nepoznato';
    }

    /**
     * Check if user's email is verified
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(): bool
    {
        return $this->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_token_expires' => null,
        ]);
    }

    /**
     * Generate email verification token
     */
    public function generateEmailVerificationToken(): string
    {
        $token = str()->random(60);
        $this->update([
            'email_verification_token' => $token,
            'email_verification_token_expires' => now()->addHours(24),
        ]);

        return $token;
    }
}
