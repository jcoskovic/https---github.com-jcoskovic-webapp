<?php

namespace App\Http\Resources;

use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Vote $vote */
        $vote = $this->resource;

        return [
            'id' => $vote->id,
            'user_id' => $vote->user_id,
            'type' => $vote->type,
            'created_at' => $vote->created_at,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
