<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    /**
     * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\VoteFactory>
     */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'abbreviation_id',
        'type', // 'up' or 'down'
    ];

    /**
     * Get the user that made this vote
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
     * Get the abbreviation that was voted on
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
