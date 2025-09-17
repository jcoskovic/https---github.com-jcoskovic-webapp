<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Abbreviation extends Model
{
    /**
     * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\AbbreviationFactory>
     */
    use HasFactory;

    protected $fillable = [
        'abbreviation',
        'meaning',
        'description',
        'category',
        'user_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that created this abbreviation
     */
    /**
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<User, self> $relation */
        $relation = $this->belongsTo(User::class);

        return $relation;
    }

    /**
     * Get all votes for this abbreviation
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
     * Get all comments for this abbreviation
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
     * Scope to filter by department
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeByDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }

    /**
     * Scope to search abbreviations
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('abbreviation', 'like', "%{$term}%")
            ->orWhere('meaning', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }
}
