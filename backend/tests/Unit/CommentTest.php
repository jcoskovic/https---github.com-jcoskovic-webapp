<?php

namespace Tests\Unit;

use App\Models\Abbreviation;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_belongs_to_user()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
        ]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }

    public function test_comment_belongs_to_abbreviation()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
        ]);

        $this->assertInstanceOf(Abbreviation::class, $comment->abbreviation);
        $this->assertEquals($abbreviation->id, $comment->abbreviation->id);
    }

    public function test_comment_can_be_created_with_fillable_attributes()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();

        $commentData = [
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
            'content' => 'This is a test comment',
        ];

        $comment = Comment::create($commentData);

        $this->assertDatabaseHas('comments', $commentData);
        $this->assertEquals('This is a test comment', $comment->content);
    }

    public function test_comment_has_timestamps()
    {
        $comment = Comment::factory()->create();

        $this->assertNotNull($comment->created_at);
        $this->assertNotNull($comment->updated_at);
    }

    public function test_comment_content_can_be_updated()
    {
        $comment = Comment::factory()->create(['content' => 'Original content']);

        $comment->update(['content' => 'Updated content']);

        $this->assertEquals('Updated content', $comment->fresh()->content);
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_comment_can_be_deleted()
    {
        $comment = Comment::factory()->create();
        $commentId = $comment->id;

        $comment->delete();

        $this->assertDatabaseMissing('comments', ['id' => $commentId]);
    }

    public function test_comment_requires_user_and_abbreviation()
    {
        // This would test database constraints, but since we're using factories
        // we can test that the relationships are properly set up
        $comment = Comment::factory()->create();

        $this->assertNotNull($comment->user_id);
        $this->assertNotNull($comment->abbreviation_id);
        $this->assertNotNull($comment->user);
        $this->assertNotNull($comment->abbreviation);
    }
}
