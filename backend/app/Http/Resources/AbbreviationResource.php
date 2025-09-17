<?php

namespace App\Http\Resources;

use App\Models\Abbreviation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbbreviationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Abbreviation $abbr */
        $abbr = $this->resource;
        $user = $request->user();
        $userVote = null;

        if ($user && $abbr->relationLoaded('votes')) {
            $userVoteRecord = $abbr->votes->where('user_id', $user->id)->first();
            $userVote = $userVoteRecord?->type;
        }

        $upvotes = $abbr->relationLoaded('votes') ? $abbr->votes->where('type', 'up')->count() : 0;
        $downvotes = $abbr->relationLoaded('votes') ? $abbr->votes->where('type', 'down')->count() : 0;

        return [
            'id' => $abbr->id,
            'abbreviation' => $abbr->abbreviation,
            'meaning' => $abbr->meaning,
            'description' => $abbr->description,
            'category' => $abbr->category,
            'status' => $abbr->status,
            'user_id' => $abbr->user_id,
            'created_at' => $abbr->created_at,
            'updated_at' => $abbr->updated_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'votes' => VoteResource::collection($this->whenLoaded('votes')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'votes_count' => $abbr->votes_count ?? ($abbr->relationLoaded('votes') ? $abbr->votes->count() : 0),
            'comments_count' => $abbr->comments_count ?? ($abbr->relationLoaded('comments') ? $abbr->comments->count() : 0),
            'upvotes_count' => $upvotes,
            'downvotes_count' => $downvotes,
            'votes_sum' => $upvotes - $downvotes,  // Net score
            'user_vote' => $userVote,  // Current user's vote
        ];
    }
}
