<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmailVerificationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private $unverifiedUser;

    private $verifiedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test creating abbreviation without verified email
     */
    public function test_create_abbreviation_requires_verified_email()
    {
        $token = JWTAuth::fromUser($this->unverifiedUser);

        $response = $this->postJson('/api/abbreviations', [
            'abbreviation' => 'TEST',
            'meaning' => 'Test meaning',
            'category' => 'Technology',
            'description' => 'Test description',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'email_verification_required' => true,
            ])
            ->assertJsonFragment([
                'message' => 'Email adresa mora biti potvrđena prije pristupanja ovom sadržaju',
            ]);
    }

    /**
     * Test creating abbreviation with verified email succeeds
     */
    public function test_create_abbreviation_with_verified_email_succeeds()
    {
        $token = JWTAuth::fromUser($this->verifiedUser);

        $response = $this->postJson('/api/abbreviations', [
            'abbreviation' => 'TEST',
            'meaning' => 'Test meaning',
            'category' => 'Technology',
            'description' => 'Test description',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        // Should not get 403 forbidden
        $response->assertStatus(201);
        $this->assertDatabaseHas('abbreviations', [
            'abbreviation' => 'TEST',
            'meaning' => 'Test meaning',
        ]);
    }

    /**
     * Test voting requires verified email
     */
    public function test_voting_requires_verified_email()
    {
        $abbreviation = \App\Models\Abbreviation::factory()->create();
        $token = JWTAuth::fromUser($this->unverifiedUser);

        $response = $this->postJson("/api/abbreviations/{$abbreviation->id}/vote", [
            'type' => 'upvote',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'email_verification_required' => true,
            ]);
    }

    /**
     * Test commenting requires verified email
     */
    public function test_commenting_requires_verified_email()
    {
        $abbreviation = \App\Models\Abbreviation::factory()->create();
        $token = JWTAuth::fromUser($this->unverifiedUser);

        $response = $this->postJson("/api/abbreviations/{$abbreviation->id}/comments", [
            'content' => 'Test comment',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'email_verification_required' => true,
            ]);
    }

    /**
     * Test basic auth routes don't require verification
     */
    public function test_basic_auth_routes_work_without_verification()
    {
        $token = JWTAuth::fromUser($this->unverifiedUser);

        // Test /me endpoint
        $response = $this->getJson('/api/me', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);

        // Test logout endpoint
        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test admin routes require verified email
     */
    public function test_admin_routes_require_verified_email()
    {
        $adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => null,
        ]);

        $token = JWTAuth::fromUser($adminUser);

        $response = $this->getJson('/api/admin/statistics', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'email_verification_required' => true,
            ]);
    }

    /**
     * Test export routes require verified email
     */
    public function test_export_requires_verified_email()
    {
        $token = JWTAuth::fromUser($this->unverifiedUser);

        $response = $this->getJson('/api/export/pdf', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'email_verification_required' => true,
            ]);
    }

    /**
     * Test public routes still work without authentication
     */
    public function test_public_routes_work_without_auth()
    {
        // Test getting abbreviations (public endpoint)
        $response = $this->getJson('/api/abbreviations');
        $response->assertStatus(200);

        // Test getting categories (public endpoint)
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200);
    }
}
