<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    /**
     * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CommentFactory>
     */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'abbreviation_id',
        'content',
    ];

    /**
     * Get the user that made this comment
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
     * Get the abbreviation that was commented on
     */
    /**
     * @return BelongsTo<Abbreviation, self>
     */
    public function abbreviation(): BelongsTo
    {
        /** @var BelongsTo<Abbreviation, self> $relation */
        $relation = $this->belongsTo(Abbreviation::class);

        return $relation;
    }
}
