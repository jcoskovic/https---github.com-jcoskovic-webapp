<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Comment $comment */
        $comment = $this->resource;

        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'user_id' => $comment->user_id,
            'abbreviation_id' => $comment->abbreviation_id,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
