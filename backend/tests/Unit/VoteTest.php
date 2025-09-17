<?php

namespace Tests\Unit;

use App\Models\Abbreviation;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_vote_belongs_to_user()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();
        $vote = Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
        ]);

        $this->assertInstanceOf(User::class, $vote->user);
        $this->assertEquals($user->id, $vote->user->id);
    }

    public function test_vote_belongs_to_abbreviation()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();
        $vote = Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
        ]);

        $this->assertInstanceOf(Abbreviation::class, $vote->abbreviation);
        $this->assertEquals($abbreviation->id, $vote->abbreviation->id);
    }

    public function test_vote_can_be_created_with_fillable_attributes()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();

        $voteData = [
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
            'type' => 'up',
        ];

        $vote = Vote::create($voteData);

        $this->assertDatabaseHas('votes', $voteData);
        $this->assertEquals('up', $vote->type);
    }

    public function test_vote_can_be_upvote()
    {
        $vote = Vote::factory()->create(['type' => 'up']);

        $this->assertEquals('up', $vote->type);
        $this->assertDatabaseHas('votes', [
            'id' => $vote->id,
            'type' => 'up',
        ]);
    }

    public function test_vote_can_be_downvote()
    {
        $vote = Vote::factory()->create(['type' => 'down']);

        $this->assertEquals('down', $vote->type);
        $this->assertDatabaseHas('votes', [
            'id' => $vote->id,
            'type' => 'down',
        ]);
    }

    public function test_vote_has_timestamps()
    {
        $vote = Vote::factory()->create();

        $this->assertNotNull($vote->created_at);
        $this->assertNotNull($vote->updated_at);
    }

    public function test_vote_type_can_be_updated()
    {
        $vote = Vote::factory()->create(['type' => 'up']);

        $vote->update(['type' => 'down']);

        $this->assertEquals('down', $vote->fresh()->type);
        $this->assertDatabaseHas('votes', [
            'id' => $vote->id,
            'type' => 'down',
        ]);
    }

    public function test_vote_can_be_deleted()
    {
        $vote = Vote::factory()->create();
        $voteId = $vote->id;

        $vote->delete();

        $this->assertDatabaseMissing('votes', ['id' => $voteId]);
    }

    public function test_vote_requires_user_and_abbreviation()
    {
        $vote = Vote::factory()->create();

        $this->assertNotNull($vote->user_id);
        $this->assertNotNull($vote->abbreviation_id);
        $this->assertNotNull($vote->user);
        $this->assertNotNull($vote->abbreviation);
    }

    public function test_user_can_have_multiple_votes_on_different_abbreviations()
    {
        $user = User::factory()->create();
        $abbreviation1 = Abbreviation::factory()->create();
        $abbreviation2 = Abbreviation::factory()->create();

        $vote1 = Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation1->id,
            'type' => 'up',
        ]);

        $vote2 = Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation2->id,
            'type' => 'down',
        ]);

        $this->assertEquals($user->id, $vote1->user_id);
        $this->assertEquals($user->id, $vote2->user_id);
        $this->assertEquals($abbreviation1->id, $vote1->abbreviation_id);
        $this->assertEquals($abbreviation2->id, $vote2->abbreviation_id);
    }

    public function test_abbreviation_can_have_multiple_votes_from_different_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();

        $vote1 = Vote::factory()->create([
            'user_id' => $user1->id,
            'abbreviation_id' => $abbreviation->id,
            'type' => 'up',
        ]);

        $vote2 = Vote::factory()->create([
            'user_id' => $user2->id,
            'abbreviation_id' => $abbreviation->id,
            'type' => 'down',
        ]);

        $this->assertEquals($abbreviation->id, $vote1->abbreviation_id);
        $this->assertEquals($abbreviation->id, $vote2->abbreviation_id);
        $this->assertEquals($user1->id, $vote1->user_id);
        $this->assertEquals($user2->id, $vote2->user_id);
    }
}
