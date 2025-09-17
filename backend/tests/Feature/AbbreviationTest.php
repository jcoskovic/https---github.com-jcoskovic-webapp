<?php

namespace Tests\Feature;

use App\Models\Abbreviation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbbreviationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_abbreviation()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->postJson('/api/abbreviations', [
                'abbreviation' => 'TEST',
                'meaning' => 'Test Abbreviation',
                'description' => 'This is a test',
                'category' => 'Testing',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('abbreviations', [
            'abbreviation' => 'TEST',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_vote_on_abbreviation()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->postJson("/api/abbreviations/{$abbreviation->id}/vote", [
                'type' => 'up',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('votes', [
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
            'type' => 'up',
        ]);
    }
}
