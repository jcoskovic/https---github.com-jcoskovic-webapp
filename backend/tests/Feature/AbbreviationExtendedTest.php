<?php

namespace Tests\Feature;

use App\Models\Abbreviation;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbbreviationExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_abbreviations()
    {
        // Create approved abbreviations
        Abbreviation::factory()->count(3)->create(['status' => 'approved']);

        // Create pending abbreviations (should not be visible)
        Abbreviation::factory()->count(2)->create(['status' => 'pending']);

        $response = $this->getJson('/api/abbreviations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'abbreviation',
                            'meaning',
                            'category',
                            'status',
                            'created_at',
                            'updated_at',
                            'user' => [
                                'id',
                                'name',
                            ],
                            'votes',
                            'comments',
                        ],
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('data.total', 3); // Only approved abbreviations should be visible

        // Verify all returned abbreviations are approved
        $data = $response->json('data.data');
        foreach ($data as $abbreviation) {
            $this->assertEquals('approved', $abbreviation['status']);
        }
    }

    public function test_guest_can_view_single_abbreviation()
    {
        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);

        $response = $this->getJson("/api/abbreviations/{$abbreviation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'abbreviation',
                    'meaning',
                    'user',
                    'votes',
                    'comments',
                ],
            ]);
    }

    public function test_user_can_add_comment()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->postJson("/api/abbreviations/{$abbreviation->id}/comments", [
                'content' => 'This is a test comment',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
            'content' => 'This is a test comment',
        ]);
    }

    public function test_user_can_delete_own_comment()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);

        $comment = Comment::create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
            'content' => 'Test comment to delete',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_user_cannot_delete_others_comment()
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        $token = auth('api')->login($user2);

        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);

        $comment = Comment::create([
            'user_id' => $user1->id,
            'abbreviation_id' => $abbreviation->id,
            'content' => 'User1 comment',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(403); // Forbidden

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_user_can_update_own_abbreviation()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $abbreviation = Abbreviation::factory()->create([
            'user_id' => $user->id,
            'abbreviation' => 'OLD',
            'meaning' => 'Old meaning',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->putJson("/api/abbreviations/{$abbreviation->id}", [
                'abbreviation' => 'NEW',
                'meaning' => 'New meaning',
                'description' => 'Updated description',
                'category' => 'Updated',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('abbreviations', [
            'id' => $abbreviation->id,
            'abbreviation' => 'NEW',
            'meaning' => 'New meaning',
        ]);
    }

    public function test_user_cannot_update_others_abbreviation()
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        $token = auth('api')->login($user2);

        $abbreviation = Abbreviation::factory()->create([
            'user_id' => $user1->id,
            'abbreviation' => 'PROTECTED',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->putJson("/api/abbreviations/{$abbreviation->id}", [
                'abbreviation' => 'HACKED',
                'meaning' => 'Hacked meaning',
            ]);

        $response->assertStatus(403); // Forbidden
    }

    public function test_user_can_get_abbreviation_comments()
    {
        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);

        // Create some comments
        Comment::factory()->count(3)->create([
            'abbreviation_id' => $abbreviation->id,
        ]);

        $response = $this->getJson("/api/abbreviations/{$abbreviation->id}/comments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'user',
                        'created_at',
                    ],
                ],
            ]);
    }
}
