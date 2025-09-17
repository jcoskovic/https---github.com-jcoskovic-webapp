<?php

namespace Tests\Unit;

use App\Models\Abbreviation;
use App\Models\Comment;
use App\Models\User;
use App\Models\Vote;
use App\Services\UserDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class UserDataServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserDataService $userDataService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userDataService = new UserDataService;
    }

    public function test_get_user_data_returns_null_for_nonexistent_user()
    {
        $result = $this->userDataService->getUserData(999);

        $this->assertNull($result);
    }

    public function test_get_user_data_returns_basic_user_info()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'department' => 'Engineering',
        ]);

        $result = $this->userDataService->getUserData($user->id);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result['user_id']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('Engineering', $result['department']);
        $this->assertIsArray($result['search_history']);
        $this->assertIsArray($result['viewed_abbreviations']);
        $this->assertIsArray($result['voted_abbreviations']);
        $this->assertIsArray($result['common_categories']);
        $this->assertIsArray($result['interactions']);
    }

    public function test_get_user_data_includes_votes_in_interactions()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();

        Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
            'type' => 'up',
        ]);

        $result = $this->userDataService->getUserData($user->id);

        $this->assertNotEmpty($result['interactions']);
        $this->assertEquals('vote', $result['interactions'][0]['type']);
        $this->assertEquals($abbreviation->id, $result['interactions'][0]['abbreviation_id']);
        $this->assertEquals(['vote_type' => 'up'], $result['interactions'][0]['metadata']);
    }

    public function test_get_user_data_includes_comments_in_interactions()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();

        Comment::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
            'content' => 'Test comment',
        ]);

        $result = $this->userDataService->getUserData($user->id);

        $this->assertNotEmpty($result['interactions']);
        $this->assertEquals('comment', $result['interactions'][0]['type']);
        $this->assertEquals($abbreviation->id, $result['interactions'][0]['abbreviation_id']);
        $this->assertEquals(['content_length' => 12], $result['interactions'][0]['metadata']);
    }

    public function test_get_user_data_includes_viewed_abbreviations()
    {
        $user = User::factory()->create();
        $abbreviation1 = Abbreviation::factory()->create();
        $abbreviation2 = Abbreviation::factory()->create();

        Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation1->id,
            'type' => 'up',
        ]);

        Comment::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation2->id,
            'content' => 'Test comment',
        ]);

        $result = $this->userDataService->getUserData($user->id);

        $this->assertCount(2, $result['viewed_abbreviations']);
        Assert::assertContains($abbreviation1->id, $result['viewed_abbreviations']);
        Assert::assertContains($abbreviation2->id, $result['viewed_abbreviations']);
    }

    public function test_get_user_data_includes_voted_abbreviations()
    {
        $user = User::factory()->create();
        $abbreviation1 = Abbreviation::factory()->create();
        $abbreviation2 = Abbreviation::factory()->create();

        Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation1->id,
            'type' => 'up',
        ]);

        Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation2->id,
            'type' => 'down',
        ]);

        $result = $this->userDataService->getUserData($user->id);

        $this->assertCount(2, $result['voted_abbreviations']);
        Assert::assertContains($abbreviation1->id, $result['voted_abbreviations']);
        Assert::assertContains($abbreviation2->id, $result['voted_abbreviations']);
    }

    public function test_get_user_data_includes_common_categories()
    {
        $user = User::factory()->create();
        $abbreviation1 = Abbreviation::factory()->create(['category' => 'Technology']);
        $abbreviation2 = Abbreviation::factory()->create(['category' => 'Technology']);
        $abbreviation3 = Abbreviation::factory()->create(['category' => 'Science']);

        Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation1->id,
            'type' => 'up',
        ]);

        Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation2->id,
            'type' => 'up',
        ]);

        Comment::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation3->id,
            'content' => 'Test',
        ]);

        $result = $this->userDataService->getUserData($user->id);

        Assert::assertContains('Technology', $result['common_categories']);
        Assert::assertContains('Science', $result['common_categories']);
    }

    public function test_get_user_data_limits_interactions_to_100()
    {
        $user = User::factory()->create();

        // Create 120 votes (more than the 100 limit)
        for ($i = 0; $i < 120; $i++) {
            $abbreviation = Abbreviation::factory()->create();
            Vote::factory()->create([
                'user_id' => $user->id,
                'abbreviation_id' => $abbreviation->id,
                'type' => 'up',
            ]);
        }

        $result = $this->userDataService->getUserData($user->id);

        $this->assertCount(100, $result['interactions']);
    }

    public function test_get_user_data_internal_returns_null_for_nonexistent_user()
    {
        $result = $this->userDataService->getUserDataInternal(999);

        $this->assertNull($result);
    }

    public function test_get_user_data_internal_returns_detailed_user_info()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'department' => 'Engineering',
        ]);

        $result = $this->userDataService->getUserDataInternal($user->id);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result['user_id']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('Engineering', $result['department']);
        $this->assertIsArray($result['votes']);
        $this->assertIsArray($result['comments']);
        $this->assertIsArray($result['search_history']);
        $this->assertIsArray($result['viewed_abbreviations']);
        $this->assertIsArray($result['voted_abbreviations']);
        $this->assertIsArray($result['common_categories']);
        $this->assertIsArray($result['interactions']);
    }

    public function test_get_user_data_internal_includes_vote_details()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();

        Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
            'type' => 'up',
        ]);

        $result = $this->userDataService->getUserDataInternal($user->id);

        $this->assertNotEmpty($result['votes']);
        $this->assertEquals($abbreviation->id, $result['votes'][0]['abbreviation_id']);
        $this->assertEquals('up', $result['votes'][0]['type']);
    }

    public function test_get_user_data_internal_includes_comment_details()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create();

        Comment::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation->id,
            'content' => 'Test comment content',
        ]);

        $result = $this->userDataService->getUserDataInternal($user->id);

        $this->assertNotEmpty($result['comments']);
        $this->assertEquals($abbreviation->id, $result['comments'][0]['abbreviation_id']);
        $this->assertEquals('Test comment content', $result['comments'][0]['content']);
    }

    public function test_get_user_data_internal_includes_combined_interactions()
    {
        $user = User::factory()->create();
        $abbreviation1 = Abbreviation::factory()->create();
        $abbreviation2 = Abbreviation::factory()->create();

        Vote::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation1->id,
            'type' => 'up',
        ]);

        Comment::factory()->create([
            'user_id' => $user->id,
            'abbreviation_id' => $abbreviation2->id,
            'content' => 'Test comment',
        ]);

        $result = $this->userDataService->getUserDataInternal($user->id);

        $this->assertCount(2, $result['interactions']);

        // Find vote interaction
        $voteInteraction = collect($result['interactions'])->firstWhere('interaction_type', 'up');
        $this->assertNotNull($voteInteraction);
        $this->assertEquals($abbreviation1->id, $voteInteraction['abbreviation_id']);

        // Find comment interaction
        $commentInteraction = collect($result['interactions'])->firstWhere('interaction_type', 'comment');
        $this->assertNotNull($commentInteraction);
        $this->assertEquals($abbreviation2->id, $commentInteraction['abbreviation_id']);
    }

    public function test_get_user_data_internal_limits_interactions_to_10()
    {
        $user = User::factory()->create();

        // Create 15 votes (more than the 10 limit)
        for ($i = 0; $i < 15; $i++) {
            $abbreviation = Abbreviation::factory()->create();
            Vote::factory()->create([
                'user_id' => $user->id,
                'abbreviation_id' => $abbreviation->id,
                'type' => 'up',
            ]);
        }

        $result = $this->userDataService->getUserDataInternal($user->id);

        $this->assertLessThanOrEqual(10, count($result['interactions']));
    }

    public function test_get_user_data_handles_user_without_department()
    {
        $user = User::factory()->create([
            'department' => null,
        ]);

        $result = $this->userDataService->getUserData($user->id);

        $this->assertEquals('', $result['department']);
    }

    public function test_get_user_data_internal_handles_user_without_department()
    {
        $user = User::factory()->create([
            'department' => null,
        ]);

        $result = $this->userDataService->getUserDataInternal($user->id);

        $this->assertNull($result['department']);
    }
}
